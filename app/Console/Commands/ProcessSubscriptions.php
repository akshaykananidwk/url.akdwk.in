<?php

namespace App\Console\Commands;

use App\Mail\DunningNotice;
use App\Models\Subscription;
use App\Services\PlanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Subscription lifecycle:
 *  - subscriptions past their end date move to past_due and the user gets
 *    dunning emails (3 attempts, one per day)
 *  - after the grace period the plan downgrades to the default/free plan
 *  - cancelled subscriptions downgrade at period end without emails
 */
class ProcessSubscriptions extends Command
{
    protected $signature = 'app:process-subscriptions';

    protected $description = 'Expire lapsed subscriptions, send dunning emails, downgrade after grace period';

    public function handle(PlanService $plans): int
    {
        $graceDays = (int) setting('dunning_grace_days', 3);

        // Active subscriptions past due.
        Subscription::where('status', 'active')
            ->where('cycle', '!=', 'lifetime')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->each(function (Subscription $sub) {
                $sub->update(['status' => $sub->auto_renew ? 'past_due' : 'expired']);
            });

        // Dunning: one email per day while past_due.
        Subscription::with('user', 'plan')
            ->where('status', 'past_due')
            ->where('dunning_attempts', '<', $graceDays)
            ->each(function (Subscription $sub) {
                $lastAttemptAt = $sub->updated_at;
                if ($sub->dunning_attempts > 0 && $lastAttemptAt->gt(now()->subDay())) {
                    return;
                }
                $sub->increment('dunning_attempts');
                try {
                    Mail::to($sub->user->email)->send(new DunningNotice($sub, $sub->dunning_attempts));
                } catch (\Throwable $e) {
                    report($e);
                }
            });

        // Grace period exhausted → downgrade.
        Subscription::with('user')
            ->whereIn('status', ['past_due', 'expired'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now()->subDays($graceDays))
            ->each(function (Subscription $sub) use ($plans) {
                if ($sub->user && $sub->user->plan_id === $sub->plan_id) {
                    $plans->downgradeToFree($sub->user);
                    $this->info("Downgraded user #{$sub->user_id} (subscription #{$sub->id}).");
                }
                $sub->update(['status' => 'expired']);
            });

        return self::SUCCESS;
    }
}
