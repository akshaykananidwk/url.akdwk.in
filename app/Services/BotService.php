<?php

namespace App\Services;

use App\Models\IntegrationAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Shared logic for the chat bots (Telegram / Slack / Discord).
 *
 * Account linking: a signed-in user generates a one-time code from the
 * Integrations page; sending it to the bot (e.g. "/start CODE") ties that chat
 * identity to their account. After that, any URL they send is shortened under
 * their account and the short link is returned.
 */
class BotService
{
    public function __construct(protected LinkService $links)
    {
    }

    /* ------------------------------------------------------------ linking */

    /** Generate a short-lived link code for a user + provider. */
    public function makeLinkCode(User $user, string $provider): string
    {
        $code = strtoupper(Str::random(8));
        Cache::put($this->codeKey($provider, $code), $user->id, now()->addMinutes(15));

        return $code;
    }

    /** Resolve a link code → user id (consumes it). */
    public function consumeLinkCode(string $provider, string $code): ?int
    {
        $key = $this->codeKey($provider, strtoupper(trim($code)));
        $userId = Cache::pull($key);

        return $userId ? (int) $userId : null;
    }

    protected function codeKey(string $provider, string $code): string
    {
        return "botlink:{$provider}:{$code}";
    }

    public function accountFor(string $provider, string $externalId): ?IntegrationAccount
    {
        return IntegrationAccount::where('provider', $provider)->where('external_id', (string) $externalId)->first();
    }

    public function link(int $userId, string $provider, string $externalId, ?string $name = null): IntegrationAccount
    {
        return IntegrationAccount::updateOrCreate(
            ['provider' => $provider, 'external_id' => (string) $externalId],
            ['user_id' => $userId, 'external_name' => $name]
        );
    }

    /* ---------------------------------------------------------- shortening */

    /**
     * Turn free-form message text into a reply. Handles /start linking and URL
     * shortening. Returns the reply string to send back to the chat.
     */
    public function handleMessage(string $provider, string $externalId, string $text, ?string $name = null): string
    {
        $text = trim($text);

        // /start [CODE] — link the account
        if (Str::startsWith($text, '/start')) {
            $code = trim(Str::after($text, '/start'));
            if ($code === '') {
                return __("Welcome to :site! To connect your account, open Integrations on the website and tap \"Connect\", then send me the code you get.", ['site' => site_name()]);
            }
            $userId = $this->consumeLinkCode($provider, $code);
            if (! $userId) {
                return __('That code is invalid or expired. Generate a new one from the Integrations page.');
            }
            $this->link($userId, $provider, $externalId, $name);

            return __('✅ Connected! Send me any link and I will shorten it for you.');
        }

        if (Str::startsWith($text, '/help')) {
            return __("Send me a URL and I'll shorten it. Use /start CODE to connect your account.");
        }

        // Must be linked to shorten.
        $account = $this->accountFor($provider, $externalId);
        if (! $account) {
            return __("You're not connected yet. Open Integrations on :site, tap Connect, and send me /start CODE.", ['site' => site_name()]);
        }

        // Extract the first URL from the message.
        if (! preg_match('~https?://\S+~i', $text, $m)) {
            return __('Send me a link (starting with http:// or https://) and I will shorten it.');
        }

        try {
            $link = $this->links->create($account->user, ['destination' => $m[0]]);

            return $link->shortUrl();
        } catch (ValidationException $e) {
            return collect($e->errors())->flatten()->first() ?: __('Could not shorten that link.');
        } catch (\Throwable $e) {
            report($e);

            return __('Something went wrong. Please try again.');
        }
    }

    /* ------------------------------------------------------------ senders */

    public function sendTelegram(string $chatId, string $text): void
    {
        $token = setting('telegram_bot_token');
        if (! $token) {
            return;
        }
        Http::timeout(6)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => false,
        ]);
    }
}
