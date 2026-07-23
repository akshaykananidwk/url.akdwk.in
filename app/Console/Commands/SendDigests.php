<?php

namespace App\Console\Commands;

use App\Jobs\SendWeeklyDigest;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Queues the weekly recap email for every eligible user.
 * Scheduling is wired elsewhere (the app scheduler).
 */
class SendDigests extends Command
{
    protected $signature = 'digests:send';

    protected $description = 'Dispatch the weekly recap email to eligible users';

    public function handle(): int
    {
        $cutoff = now()->subDays(6);
        $dispatched = 0;

        User::whereNull('suspended_at')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_digest_at')
                    ->orWhere('last_digest_at', '<', $cutoff);
            })
            ->whereHas('links')
            ->chunkById(200, function ($users) use (&$dispatched) {
                foreach ($users as $user) {
                    SendWeeklyDigest::dispatch($user);
                    $dispatched++;
                }
            });

        $this->info("Queued {$dispatched} weekly digests.");

        return self::SUCCESS;
    }
}
