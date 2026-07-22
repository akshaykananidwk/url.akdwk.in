<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PlanLimits;
use App\Services\StatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected StatsService $stats,
        protected PlanLimits $limits,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 7;
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $series = $this->stats->series(null, $user->id, $from, $to);
        $totals = $this->stats->totals(null, $user->id, $from, $to);
        $today = $this->stats->totals(null, $user->id, now()->startOfDay(), $to);

        $topLinks = $user->links()->active()
            ->orderByDesc('clicks_count')->limit(5)->get();
        $recentLinks = $user->links()
            ->orderByDesc('created_at')->limit(5)->get();

        return view('user.dashboard', [
            'kpis' => [
                'links' => $user->links()->count(),
                'clicks' => $user->links()->sum('clicks_count'),
                'today' => $today['clicks'],
                'spaces' => $user->spaces()->count(),
                'domains' => $user->domains()->count(),
                'pixels' => $user->pixels()->count(),
            ],
            'series' => $series,
            'totals' => $totals,
            'days' => $days,
            'topLinks' => $topLinks,
            'recentLinks' => $recentLinks,
            'usage' => $this->limits->usageSummary($user),
            'plan' => $user->currentPlan(),
        ]);
    }
}
