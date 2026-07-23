<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a single user their weekly performance recap by email.
 * Dispatched in bulk by the digests:send command.
 */
class SendWeeklyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public User $user)
    {
    }

    public function handle(): void
    {
        $user = $this->user->fresh();
        if (! $user) {
            return;
        }

        // Respect opt-out.
        $prefs = $user->notification_prefs ?? [];
        if (array_key_exists('email_product', $prefs) && $prefs['email_product'] === false) {
            return;
        }

        $linkIds = $user->links()->pluck('id');
        if ($linkIds->isEmpty()) {
            return;
        }

        $from = now()->subDays(7)->startOfDay();
        $to = now();

        // New clicks in the last 7 days across the user's links.
        $newClicks = (int) DB::table('clicks')
            ->whereIn('link_id', $linkIds)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->count();

        // Links created in the last 7 days.
        $newLinks = (int) $user->links()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->count();

        // Top link overall by total clicks.
        $topLink = $user->links()
            ->orderByDesc('clicks_count')
            ->first(['alias', 'domain_id', 'title', 'clicks_count']);

        $stats = [
            'newClicks' => $newClicks,
            'newLinks' => $newLinks,
            'totalLinks' => (int) $linkIds->count(),
            'topLink' => $topLink,
            'from' => $from,
            'to' => $to,
        ];

        try {
            Mail::send('emails.digest', ['user' => $user, 'stats' => $stats], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('Your weekly :site recap', ['site' => site_name()]));
            });

            $user->forceFill(['last_digest_at' => now()])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
