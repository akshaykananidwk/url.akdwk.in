<?php

namespace App\Http\Controllers;

use App\Services\BotService;
use Illuminate\Http\Request;

/**
 * Inbound webhooks for the chat bots. All are CSRF-exempt (see bootstrap/app.php
 * webhooks/* exclusion) and verify authenticity per provider.
 */
class BotController extends Controller
{
    public function __construct(protected BotService $bots)
    {
    }

    /**
     * Telegram webhook. Set it once with:
     *   https://api.telegram.org/bot<token>/setWebhook?url=<APP_URL>/webhooks/telegram/<secret>
     * The <secret> path segment must equal the telegram_webhook_secret setting.
     */
    public function telegram(Request $request, string $secret)
    {
        abort_unless(hash_equals((string) setting('telegram_webhook_secret'), $secret), 403);

        $msg = $request->input('message') ?? $request->input('edited_message');
        $chatId = $msg['chat']['id'] ?? null;
        $text = $msg['text'] ?? '';
        if ($chatId && $text !== '') {
            $name = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? '')) ?: ($msg['from']['username'] ?? null);
            $reply = $this->bots->handleMessage('telegram', (string) $chatId, $text, $name);
            $this->bots->sendTelegram((string) $chatId, $reply);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Slack slash command (e.g. /shorten https://…). Configure the command's
     * Request URL to <APP_URL>/webhooks/slack/command. Verified with the app's
     * signing secret (Admin → Settings → Bots).
     */
    public function slack(Request $request)
    {
        $this->verifySlack($request);

        $text = (string) $request->input('text', '');
        $userId = (string) $request->input('user_id', '');
        $userName = (string) $request->input('user_name', '');

        $reply = $this->bots->handleMessage('slack', $userId, $text ?: '/help', $userName);

        return response()->json(['response_type' => 'ephemeral', 'text' => $reply]);
    }

    protected function verifySlack(Request $request): void
    {
        $secret = setting('slack_signing_secret');
        if (! $secret) {
            return; // not configured — accept (dev)
        }
        $ts = (string) $request->header('X-Slack-Request-Timestamp');
        if (! $ts || abs(time() - (int) $ts) > 300) {
            abort(403, 'Stale request');
        }
        $sig = 'v0=' . hash_hmac('sha256', 'v0:' . $ts . ':' . $request->getContent(), $secret);
        if (! hash_equals($sig, (string) $request->header('X-Slack-Signature'))) {
            abort(403, 'Bad signature');
        }
    }

    /**
     * Discord interactions endpoint. Handles the PING handshake and a
     * /shorten slash command. Verified with the app's Ed25519 public key.
     */
    public function discord(Request $request)
    {
        $this->verifyDiscord($request);
        $body = $request->json()->all();
        $type = $body['type'] ?? null;

        // 1 = PING → 1 = PONG
        if ($type === 1) {
            return response()->json(['type' => 1]);
        }

        // 2 = APPLICATION_COMMAND
        if ($type === 2) {
            $options = collect($body['data']['options'] ?? []);
            $url = (string) ($options->firstWhere('name', 'url')['value'] ?? '');
            $user = $body['member']['user'] ?? $body['user'] ?? [];
            $externalId = (string) ($user['id'] ?? '');
            $name = $user['username'] ?? null;

            $reply = $this->bots->handleMessage('discord', $externalId, $url ?: '/help', $name);

            return response()->json(['type' => 4, 'data' => ['content' => $reply, 'flags' => 64]]);
        }

        return response()->json(['type' => 4, 'data' => ['content' => 'Unsupported', 'flags' => 64]]);
    }

    protected function verifyDiscord(Request $request): void
    {
        $key = setting('discord_public_key');
        if (! $key) {
            return;
        }
        $sig = (string) $request->header('X-Signature-Ed25519');
        $ts = (string) $request->header('X-Signature-Timestamp');
        $ok = false;
        try {
            if (function_exists('sodium_crypto_sign_verify_detached') && $sig && $ts) {
                $ok = sodium_crypto_sign_verify_detached(
                    sodium_hex2bin($sig),
                    $ts . $request->getContent(),
                    sodium_hex2bin($key)
                );
            }
        } catch (\Throwable) {
            $ok = false;
        }
        abort_unless($ok, 401, 'Invalid request signature');
    }
}
