<?php

namespace App\Console\Commands;

use App\Models\Click;
use App\Models\Link;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Data retention housekeeping:
 *  - deletes raw click rows older than each user's plan retention window
 *    (daily rollups are kept forever — charts never lose history)
 *  - removes expired guest links
 */
class PruneClicks extends Command
{
    protected $signature = 'app:prune-clicks';

    protected $description = 'Prune raw click data past plan retention and clean up expired guest links';

    public function handle(): int
    {
        $totalDeleted = 0;

        User::query()->chunkById(200, function ($users) use (&$totalDeleted) {
            foreach ($users as $user) {
                $days = $user->currentPlan()->limit('retention_days');
                if ($days < 0) {
                    continue; // unlimited retention
                }
                $days = max(7, $days ?: 30);
                $deleted = Click::where('user_id', $user->id)
                    ->where('created_at', '<', now()->subDays($days))
                    ->limit(50000)
                    ->delete();
                $totalDeleted += $deleted;
            }
        });

        // Guest links past expiry are removed entirely.
        $guestLinks = Link::whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays(7))
            ->where('meta->guest', true)
            ->get();
        foreach ($guestLinks as $link) {
            app(\App\Services\LinkService::class)->delete($link);
        }

        $this->info("Pruned {$totalDeleted} raw clicks, removed {$guestLinks->count()} expired guest links.");

        return self::SUCCESS;
    }
}
