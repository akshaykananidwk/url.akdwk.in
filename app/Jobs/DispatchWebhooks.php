<?php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

/**
 * Delivers an event to all matching webhooks of a user.
 * Payloads are signed with HMAC-SHA256 (X-Signature header) so receivers
 * can verify authenticity. Endpoints failing 20 times are auto-disabled.
 */
class DispatchWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(
        public int $userId,
        public string $event,
        public array $payload,
    ) {
    }

    public function handle(): void
    {
        $hooks = Webhook::where('user_id', $this->userId)
            ->where('active', true)
            ->get()
            ->filter(fn (Webhook $h) => in_array($this->event, $h->events ?? [], true));

        foreach ($hooks as $hook) {
            $body = json_encode(['event' => $this->event, 'data' => $this->payload, 'sent_at' => now()->toIso8601String()]);
            $signature = hash_hmac('sha256', $body, $hook->secret);

            try {
                $res = Http::timeout(5)
                    ->withHeaders(['X-Signature' => $signature, 'Content-Type' => 'application/json'])
                    ->withBody($body, 'application/json')
                    ->post($hook->url);

                if ($res->successful()) {
                    if ($hook->failures > 0) {
                        $hook->update(['failures' => 0]);
                    }
                    continue;
                }
            } catch (\Throwable) {
                // fall through to failure accounting
            }

            $hook->increment('failures');
            if ($hook->failures >= 20) {
                $hook->update(['active' => false]);
            }
        }
    }
}
