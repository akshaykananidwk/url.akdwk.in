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

// Link health checks — ping destinations and flag dead links.
Schedule::command('app:check-link-health')->dailyAt('04:00');

// Weekly click report digest to opted-in users (Mondays 8am).
Schedule::command('app:send-click-reports --days=7')->weeklyOn(1, '08:00');

// Weekly recap email (batch #6) — per-user 7-day recap, opt-out honoured.
Schedule::command('digests:send')->weeklyOn(1, '08:30');
