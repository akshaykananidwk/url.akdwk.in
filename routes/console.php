<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks — driven by a single system cron entry:
|   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
|--------------------------------------------------------------------------
*/

// Heartbeat so the admin panel can show cron health.
Schedule::call(fn () => setting_set('cron_last_run', now()->toIso8601String()))
    ->everyMinute()->name('cron-heartbeat');

// Retention: prune raw clicks past each plan's retention window and clean
// up expired guest links. Daily rollups are kept forever.
Schedule::command('app:prune-clicks')->dailyAt('03:10');

// Subscription lifecycle: expire lapsed plans, dunning emails, downgrades.
Schedule::command('app:process-subscriptions')->hourly();

// Queue hygiene.
Schedule::command('queue:prune-failed --hours=168')->daily();
