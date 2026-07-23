<?php

namespace App\Console\Commands;

use App\Mail\ClickReport;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Emails opted-in users a stats digest for the given period.
 * Scheduled weekly; users opt in via Account → Notifications.
 */
class SendClickReports extends Command
{
    protected $signature = 'app:send-click-reports {--days=7}';

    protected $description = 'Email a click stats digest to opted-in users';

    public function handle(StatsService $stats): int
    {
        $days = (int) $this->option('days');
        $from = now()->subDays($days)->startOfDay();
        $to = now()->endOfDay();
        $sent = 0;

        User::whereNull('suspended_at')->chunkById(200, function ($users) use ($stats, $from, $to, $days, &$sent) {
            foreach ($users as $user) {
                $prefs = $user->notification_prefs ?? [];
                if (empty($prefs['email_link_reports'])) {
                    continue;
                }
                $totals = $stats->totals(null, $user->id, $from, $to);
                if ($totals['clicks'] === 0) {
                    continue; // nothing to report
                }
                $top = $user->links()->orderByDesc('clicks_count')->limit(5)->get(['alias', 'domain_id', 'title', 'clicks_count']);
                try {
                    Mail::to($user->email)->send(new ClickReport($user, $totals, $top, $days));
                    $sent++;
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        $this->info("Sent {$sent} reports.");

        return self::SUCCESS;
    }
}
