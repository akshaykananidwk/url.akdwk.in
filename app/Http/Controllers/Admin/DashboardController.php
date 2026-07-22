<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\ClickRollup;
use App\Models\Link;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $from = now()->subDays(29)->startOfDay();

        // Signup + revenue series for the last 30 days.
        $signups = User::where('created_at', '>=', $from)
            ->get()->groupBy(fn ($u) => $u->created_at->toDateString())->map->count();
        $revenue = Payment::where('status', 'completed')->where('paid_at', '>=', $from)
            ->get()->groupBy(fn ($p) => $p->paid_at->toDateString())->map(fn ($g) => round($g->sum('total'), 2));
        $clicks = ClickRollup::where('date', '>=', $from->toDateString())
            ->selectRaw('date, sum(clicks) as c')->groupBy('date')->pluck('c', 'date');

        $series = [];
        for ($d = $from->copy(); $d->lte(now()); $d->addDay()) {
            $key = $d->toDateString();
            $series[$key] = [
                'signups' => (int) ($signups[$key] ?? 0),
                'revenue' => (float) ($revenue[$key] ?? 0),
                'clicks' => (int) ($clicks[$key] ?? 0),
            ];
        }

        return view('admin.dashboard', [
            'kpis' => [
                'users' => User::count(),
                'links' => Link::count(),
                'clicks' => (int) Link::sum('clicks_count'),
                'revenue' => (float) Payment::where('status', 'completed')->sum('total'),
                'revenue_month' => (float) Payment::where('status', 'completed')->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
                'pending_payments' => Payment::where('status', 'pending')->whereNotNull('gateway_reference')->whereIn('gateway', ['upi', 'bank'])->count(),
                'open_reports' => \App\Models\AbuseReport::where('status', 'open')->count(),
            ],
            'series' => $series,
            'recentUsers' => User::orderByDesc('created_at')->limit(6)->get(),
            'recentPayments' => Payment::with('user', 'plan')->orderByDesc('created_at')->limit(6)->get(),
        ]);
    }
}
