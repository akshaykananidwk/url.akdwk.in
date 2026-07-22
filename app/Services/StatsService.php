<?php

namespace App\Services;

use App\Models\Click;
use App\Models\ClickRollup;
use App\Models\Link;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * All analytics queries run against the daily rollup table, so charts stay
 * fast regardless of raw click volume. The raw clicks table is only used for
 * the live feed and gets pruned by the retention command.
 */
class StatsService
{
    /** Daily click series between two dates, keyed by Y-m-d (gaps filled with zeroes). */
    public function series(?Link $link, ?int $userId, Carbon $from, Carbon $to): array
    {
        $query = ClickRollup::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        if ($link) {
            $query->where('link_id', $link->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $rows = $query->selectRaw('date, sum(clicks) as clicks, sum(uniques) as uniques, sum(qr_scans) as qr_scans')
            ->groupBy('date')->get()->keyBy(fn ($r) => Carbon::parse($r->date)->toDateString());

        $out = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $row = $rows->get($key);
            $out[$key] = [
                'clicks' => (int) ($row->clicks ?? 0),
                'uniques' => (int) ($row->uniques ?? 0),
                'qr_scans' => (int) ($row->qr_scans ?? 0),
            ];
        }

        return $out;
    }

    /** Aggregated breakdown for one dimension (country, os, browser, device, referer, language, city, region, isp, hour). */
    public function breakdown(?Link $link, ?int $userId, Carbon $from, Carbon $to, string $dimension, int $limit = 12): Collection
    {
        $query = ClickRollup::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        if ($link) {
            $query->where('link_id', $link->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $totals = [];
        $query->select('breakdown')->chunk(500, function ($rows) use (&$totals, $dimension) {
            foreach ($rows as $row) {
                foreach (($row->breakdown[$dimension] ?? []) as $key => $count) {
                    $totals[$key] = ($totals[$key] ?? 0) + $count;
                }
            }
        });

        arsort($totals);

        return collect($totals)->take($limit)->map(fn ($count, $key) => ['key' => (string) $key, 'count' => $count])->values();
    }

    /** Totals for a range. */
    public function totals(?Link $link, ?int $userId, Carbon $from, Carbon $to): array
    {
        $query = ClickRollup::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        if ($link) {
            $query->where('link_id', $link->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $row = $query->selectRaw('coalesce(sum(clicks),0) as clicks, coalesce(sum(uniques),0) as uniques, coalesce(sum(qr_scans),0) as qr_scans')->first();

        return ['clicks' => (int) $row->clicks, 'uniques' => (int) $row->uniques, 'qr_scans' => (int) $row->qr_scans];
    }

    /** Hour-of-day × day-of-week heatmap from raw clicks (bounded by retention). */
    public function heatmap(?Link $link, ?int $userId, Carbon $from, Carbon $to): array
    {
        $query = Click::query()->whereBetween('created_at', [$from, $to->copy()->endOfDay()]);
        if ($link) {
            $query->where('link_id', $link->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $driver = $query->getConnection()->getDriverName();
        [$dowExpr, $hourExpr] = $driver === 'sqlite'
            ? ["cast(strftime('%w', created_at) as integer)", "cast(strftime('%H', created_at) as integer)"]
            : ['dayofweek(created_at) - 1', 'hour(created_at)'];

        $rows = $query->selectRaw("$dowExpr as dow, $hourExpr as hr, count(*) as c")
            ->groupBy('dow', 'hr')->get();

        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        foreach ($rows as $row) {
            $grid[(int) $row->dow][(int) $row->hr] = (int) $row->c;
        }

        return $grid;
    }

    /** Latest raw clicks for the live feed. */
    public function liveFeed(?Link $link, ?int $userId, int $afterId = 0, int $limit = 25): Collection
    {
        $query = Click::query()->orderByDesc('id')->limit($limit);
        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
        }
        if ($link) {
            $query->where('link_id', $link->id);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /** Clicks recorded this calendar month for a user (plan quota checks). */
    public function clicksThisMonth(int $userId): int
    {
        return (int) ClickRollup::where('user_id', $userId)
            ->where('date', '>=', now()->startOfMonth()->toDateString())
            ->sum('clicks');
    }
}
