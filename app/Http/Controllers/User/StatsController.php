<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\PlanLimits;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StatsController extends Controller
{
    public const DIMENSIONS = ['country', 'city', 'region', 'referer', 'language', 'os', 'browser', 'device', 'isp'];

    public function __construct(
        protected StatsService $stats,
        protected PlanLimits $limits,
    ) {
    }

    /** Global (account-wide) statistics. */
    public function global(Request $request)
    {
        [$from, $to, $range] = $this->range($request);
        $userId = $request->user()->id;

        return view('user.stats.show', $this->payload(null, $userId, $from, $to, $range) + ['link' => null]);
    }

    /** Per-link statistics. */
    public function link(Request $request, Link $link)
    {
        Gate::authorize('view', $link);
        [$from, $to, $range] = $this->range($request);

        return view('user.stats.show', $this->payload($link, null, $from, $to, $range) + ['link' => $link]);
    }

    /** JSON feed for the real-time live click widget. */
    public function live(Request $request)
    {
        $link = null;
        if ($request->filled('link_id')) {
            $link = Link::findOrFail($request->query('link_id'));
            Gate::authorize('view', $link);
        }

        $rows = $this->stats->liveFeed($link, $link ? null : $request->user()->id, (int) $request->query('after', 0));

        return response()->json($rows->map(fn ($c) => [
            'id' => $c->id,
            'country' => $c->country,
            'city' => $c->city,
            'os' => $c->os,
            'browser' => $c->browser,
            'device' => $c->device,
            'referer' => $c->referer_host ?: 'direct',
            'is_qr' => $c->is_qr,
            'at' => $c->created_at->diffForHumans(),
        ]));
    }

    public function exportCsv(Request $request, Link $link)
    {
        Gate::authorize('view', $link);
        abort_unless($this->limits->hasFeature($request->user(), 'export'), 403, __('Export is not available on your plan.'));
        [$from, $to] = $this->range($request);

        $series = $this->stats->series($link, null, $from, $to);

        return response()->streamDownload(function () use ($series) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'clicks', 'uniques', 'qr_scans']);
            foreach ($series as $date => $row) {
                fputcsv($out, [$date, $row['clicks'], $row['uniques'], $row['qr_scans']]);
            }
            fclose($out);
        }, 'stats-' . $link->alias . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request, Link $link)
    {
        Gate::authorize('view', $link);
        abort_unless($this->limits->hasFeature($request->user(), 'export'), 403);
        [$from, $to, $range] = $this->range($request);
        $data = $this->payload($link, null, $from, $to, $range);

        $html = view('stats.pdf', $data + ['link' => $link])->render();
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename=stats-' . $link->alias . '.pdf',
        ]);
    }

    /** Public stats page (/stats/{alias}) when the owner enabled sharing. */
    public function publicStats(Request $request, string $alias)
    {
        $link = Link::where('alias', $alias)->whereNull('domain_id')->firstOrFail();
        abort_unless($link->public_stats, 404);
        [$from, $to, $range] = $this->range($request);

        return view('stats.public', $this->payload($link, null, $from, $to, $range) + ['link' => $link]);
    }

    /* ------------------------------------------------------------------ */

    protected function payload(?Link $link, ?int $userId, Carbon $from, Carbon $to, array $range): array
    {
        $series = $this->stats->series($link, $userId, $from, $to);
        $totals = $this->stats->totals($link, $userId, $from, $to);

        // Compare with the previous period of equal length.
        $days = $from->diffInDays($to) + 1;
        $prev = $this->stats->totals($link, $userId, $from->copy()->subDays($days), $from->copy()->subDay());

        $breakdowns = [];
        foreach (self::DIMENSIONS as $dim) {
            $breakdowns[$dim] = $this->stats->breakdown($link, $userId, $from, $to, $dim);
        }

        return [
            'series' => $series,
            'totals' => $totals,
            'previous' => $prev,
            'breakdowns' => $breakdowns,
            'heatmap' => $this->stats->heatmap($link, $userId, $from, $to),
            'from' => $from,
            'to' => $to,
            'range' => $range,
        ];
    }

    protected function range(Request $request): array
    {
        $preset = $request->query('range', '30');
        if ($preset === 'custom' && $request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->query('from'))->startOfDay();
            $to = Carbon::parse($request->query('to'))->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            $from = $from->max(now()->subYears(2));
        } else {
            $days = in_array((int) $preset, [7, 30, 90], true) ? (int) $preset : 30;
            $from = now()->subDays($days - 1)->startOfDay();
            $to = now()->endOfDay();
            $preset = (string) $days;
        }

        return [$from, $to, ['preset' => $preset, 'from' => $from->toDateString(), 'to' => $to->toDateString()]];
    }
}
