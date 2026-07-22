<?php

namespace App\Services\Support;

/**
 * RFC 6238 TOTP implementation (SHA1, 6 digits, 30s period) — compatible with
 * Google Authenticator, Authy, 1Password, etc. Dependency-free.
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 32): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }

        return $secret;
    }

    public static function code(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, 30);
        $key = self::base32Decode($secret);
        $binary = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF) % 1000000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    /** Verify with ±1 window drift tolerance. */
    public static function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        foreach ([-1, 0, 1] as $drift) {
            if (hash_equals(self::code($secret, time() + $drift * 30), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function provisioningUri(string $secret, string $email, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $email)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    private static function base32Decode(string $input): string
    {
        $input = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $input));
        $buffer = 0;
        $bits = 0;
        $output = '';
        foreach (str_split($input) as $char) {
            $buffer = ($buffer << 5) | strpos(self::ALPHABET, $char);
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
