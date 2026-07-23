<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Payloadless Web Push (VAPID) using only PHP + openssl — no external packages.
 *
 * We send notifications with no encrypted body; the service worker shows a
 * default notification on receipt. That keeps us free of the AES-GCM / HKDF
 * message-encryption machinery while still supporting VAPID authentication.
 */
class WebPushService
{
    /**
     * Return the VAPID keypair, generating and persisting one on first use.
     *
     * @return array{public: string, private: string}
     *   public  = base64url uncompressed P-256 point (applicationServerKey)
     *   private = PEM-encoded EC private key
     */
    public function keys(): array
    {
        $public = setting('vapid_public');
        $private = setting('vapid_private');

        if ($public && $private) {
            return ['public' => $public, 'private' => $private];
        }

        $res = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if ($res === false) {
            throw new \RuntimeException('Unable to generate VAPID keypair.');
        }

        openssl_pkey_export($res, $privatePem);
        $details = openssl_pkey_get_details($res);

        // Uncompressed point: 0x04 || X(32) || Y(32)
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $point = "\x04" . $x . $y;

        $publicB64 = self::b64url($point);

        setting_set('vapid_public', $publicB64);
        setting_set('vapid_private', $privatePem);

        return ['public' => $publicB64, 'private' => $privatePem];
    }

    /** The base64url applicationServerKey the browser needs. */
    public function publicKey(): string
    {
        return $this->keys()['public'];
    }

    /**
     * Send a payloadless push to a single subscription.
     * Deletes the subscription on 404/410 (gone). Returns success.
     */
    public function sendTo(PushSubscription $sub): bool
    {
        try {
            $keys = $this->keys();
            $endpoint = $sub->endpoint;

            $parts = parse_url($endpoint);
            if (empty($parts['scheme']) || empty($parts['host'])) {
                return false;
            }
            $aud = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

            $adminEmail = setting('admin_email') ?: ('admin@' . (parse_url(url('/'), PHP_URL_HOST) ?: 'localhost'));

            $header = ['typ' => 'JWT', 'alg' => 'ES256'];
            $claims = [
                'aud' => $aud,
                'exp' => time() + 43200, // 12h
                'sub' => 'mailto:' . $adminEmail,
            ];

            $signingInput = self::b64url(json_encode($header)) . '.' . self::b64url(json_encode($claims));

            $pkey = openssl_pkey_get_private($keys['private']);
            if ($pkey === false) {
                return false;
            }
            $der = '';
            if (! openssl_sign($signingInput, $der, $pkey, OPENSSL_ALGO_SHA256)) {
                return false;
            }
            $jose = self::der2raw($der);
            $jwt = $signingInput . '.' . self::b64url($jose);

            $response = Http::withHeaders([
                'Authorization' => 'vapid t=' . $jwt . ', k=' . $keys['public'],
                'TTL' => '2419200',
                'Content-Length' => '0',
            ])->timeout(8)->withBody('', 'application/octet-stream')->post($endpoint);

            if (in_array($response->status(), [404, 410], true)) {
                $sub->delete();

                return false;
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Web push send failed: ' . $e->getMessage());

            return false;
        }
    }

    /** Send to all of a user's subscriptions; return the number sent OK. */
    public function broadcast(User $user): int
    {
        $sent = 0;
        foreach ($user->pushSubscriptions()->get() as $sub) {
            if ($this->sendTo($sub)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** URL-safe base64 without padding. */
    private static function b64url(string $bin): string
    {
        return strtr(rtrim(base64_encode($bin), '='), '+/', '-_');
    }

    /**
     * Convert a DER-encoded ECDSA signature (SEQUENCE of two INTEGERs) into the
     * raw 64-byte JOSE r||s form, each integer left-padded to 32 bytes.
     */
    private static function der2raw(string $der): string
    {
        $offset = 0;
        $len = strlen($der);

        $readByte = function () use ($der, &$offset) {
            return ord($der[$offset++]);
        };

        // SEQUENCE
        if ($readByte() !== 0x30) {
            throw new \RuntimeException('Invalid ECDSA signature: missing SEQUENCE.');
        }
        // sequence length (assume short form; ECDSA P-256 sigs are well under 128)
        $seqLen = $readByte();
        if ($seqLen & 0x80) {
            // long form length — skip the length-of-length bytes
            $n = $seqLen & 0x7f;
            $seqLen = 0;
            for ($i = 0; $i < $n; $i++) {
                $seqLen = ($seqLen << 8) | $readByte();
            }
        }

        $readInt = function () use ($der, &$offset, $readByte, $len): string {
            if ($offset >= $len || $readByte() !== 0x02) {
                throw new \RuntimeException('Invalid ECDSA signature: missing INTEGER.');
            }
            $intLen = $readByte();
            $bytes = substr($der, $offset, $intLen);
            $offset += $intLen;
            // Strip a leading zero byte that only exists to keep the value positive.
            $bytes = ltrim($bytes, "\0");
            // Left-pad to 32 bytes.
            return str_pad($bytes, 32, "\0", STR_PAD_LEFT);
        };

        $r = $readInt();
        $s = $readInt();

        return $r . $s;
    }
}
