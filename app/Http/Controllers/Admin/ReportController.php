<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $revenueByMonth = Payment::where('status', 'completed')
            ->where('paid_at', '>=', now()->subMonths(12)->startOfMonth())
            ->get()
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))
            ->map(fn ($g) => round($g->sum('total'), 2));

        $signupsByMonth = User::where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->get()
            ->groupBy(fn ($u) => $u->created_at->format('Y-m'))
            ->map->count();

        return view('admin.reports.index', [
            'months' => $months,
            'revenue' => $months->mapWithKeys(fn ($m) => [$m => (float) ($revenueByMonth[$m] ?? 0)]),
            'signups' => $months->mapWithKeys(fn ($m) => [$m => (int) ($signupsByMonth[$m] ?? 0)]),
            'byGateway' => Payment::where('status', 'completed')
                ->selectRaw('gateway, count(*) as count, sum(total) as total')
                ->groupBy('gateway')->orderByDesc('total')->get(),
            'byPlan' => Payment::where('status', 'completed')
                ->selectRaw('plan_id, count(*) as count, sum(total) as total')
                ->groupBy('plan_id')->with('plan')->orderByDesc('total')->get(),
            'topUsers' => User::withCount('links')->withSum('links', 'clicks_count')
                ->orderByDesc('links_sum_clicks_count')->limit(15)->get(),
        ]);
    }

    public function auditLog(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');
        if ($request->filled('action')) {
            $query->where('action', 'like', $request->query('action') . '%');
        }

        return view('admin.reports.audit', ['logs' => $query->paginate(30)->withQueryString()]);
    }
}
