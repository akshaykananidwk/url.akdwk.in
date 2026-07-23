<?php

namespace App\Jobs;

use App\Models\AlertChannel;
use App\Models\Link;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

/**
 * Pushes a click notification to a user's Slack / Discord / Telegram channels.
 * Fired from the click_recorded hook: instantly (if enabled) or when a link
 * crosses a click milestone.
 */
class SendClickAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(
        public int $userId,
        public int $linkId,
        public string $reason, // 'instant' | 'milestone'
        public int $totalClicks,
    ) {
    }

    public function handle(): void
    {
        $link = Link::find($this->linkId);
        if (! $link) {
            return;
        }

        $channels = AlertChannel::where('user_id', $this->userId)->where('active', true)->get();
        if ($channels->isEmpty()) {
            return;
        }

        $short = $link->shortUrl();
        $title = $link->title ?: $link->alias;
        $text = $this->reason === 'milestone'
            ? "🎉 *{$title}* just reached *{$this->totalClicks}* clicks!\n{$short}"
            : "👆 New click on *{$title}* ({$this->totalClicks} total)\n{$short}";

        foreach ($channels as $channel) {
            try {
                match ($channel->type) {
                    'slack' => Http::timeout(5)->post($channel->target, ['text' => str_replace('*', '*', $text)]),
                    'discord' => Http::timeout(5)->post($channel->target, ['content' => $text]),
                    'telegram' => $this->telegram($channel->target, $text),
                    default => null,
                };
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** Telegram target stored as "botToken|chatId". */
    protected function telegram(string $target, string $text): void
    {
        [$token, $chatId] = array_pad(explode('|', $target, 2), 2, null);
        if (! $token || ! $chatId) {
            return;
        }
        Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => str_replace('*', '', $text),
            'disable_web_page_preview' => false,
        ]);
    }
}
