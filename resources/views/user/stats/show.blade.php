@extends('layouts.app')

@php
    $baseUrl = $link ? route('stats.link', $link) : route('stats.global');
    $rangeQuery = 'range=' . $range['preset'] . '&from=' . $range['from'] . '&to=' . $range['to'];
    $pct = function ($current, $previous) {
        return $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : null;
    };
    $dimTitles = [
        'country' => __('Countries'), 'referer' => __('Referrers'), 'os' => __('Platforms'),
        'browser' => __('Browsers'), 'language' => __('Languages'), 'city' => __('Cities'),
        'region' => __('Regions'), 'isp' => __('ISPs'),
    ];
    $heatMax = max(1, max(array_map('max', $heatmap)));
    $dayNames = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];
@endphp

@section('title', ($link ? __('Statistics') . ' — ' . $link->alias : __('Global statistics')) . ' — ' . site_name())
@section('page-title', $link ? __('Statistics') : __('Global statistics'))

@section('content')
<div class="space-y-5">

    {{-- Header: title + range picker --}}
    <div class="card card-pad !py-4 space-y-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="min-w-0 flex-1">
                @if($link)
                    <p class="font-semibold truncate">{{ $link->shortUrl() }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $link->destination }}</p>
                @else
                    <p class="font-semibold">{{ __('Global statistics') }}</p>
                    <p class="text-xs text-slate-500">{{ __('All links in your account') }}</p>
                @endif
            </div>
            @if($link)
                <a href="{{ route('stats.export.csv', $link) . '?' . $rangeQuery }}" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4"/> CSV</a>
                <a href="{{ route('stats.export.pdf', $link) . '?' . $rangeQuery }}" class="btn-secondary btn-sm"><x-icon name="doc" class="h-4 w-4"/> PDF</a>
                @if($link->public_stats)
                    <button type="button" class="btn-secondary btn-sm" onclick="copyText(@js(route('stats.public', $link->alias)))">
                        <x-icon name="share" class="h-4 w-4"/> {{ __('Share') }}
                    </button>
                @endif
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="flex rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-0.5 text-xs font-medium">
                @foreach([7, 30, 90] as $d)
                    <a href="{{ $baseUrl }}?range={{ $d }}"
                       class="px-3 py-2 rounded-lg min-h-[36px] inline-flex items-center {{ $range['preset'] === (string) $d ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300' }}">
                        {{ __(':n days', ['n' => $d]) }}
                    </a>
                @endforeach
            </div>
            <form method="GET" action="{{ $baseUrl }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" value="{{ $range['from'] }}" class="input !w-auto" aria-label="{{ __('From') }}">
                <input type="date" name="to" value="{{ $range['to'] }}" class="input !w-auto" aria-label="{{ __('To') }}">
                <button type="submit" class="btn-secondary btn-sm">{{ __('Apply') }}</button>
            </form>
        </div>
    </div>

    {{-- KPI row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-stat-card :label="__('Clicks')" :value="format_number($totals['clicks'])" icon="chart" :trend="$pct($totals['clicks'], $previous['clicks'])"/>
        <x-stat-card :label="__('Unique visitors')" :value="format_number($totals['uniques'])" icon="users" :trend="$pct($totals['uniques'], $previous['uniques'])"/>
        <x-stat-card :label="__('QR scans')" :value="format_number($totals['qr_scans'])" icon="qr" :trend="$pct($totals['qr_scans'], $previous['qr_scans'])"/>
    </div>

    {{-- Series chart --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Clicks over time') }}</h2>
        <div class="h-56 sm:h-72"><canvas id="statsChart"></canvas></div>
    </div>

    {{-- Breakdowns --}}
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($dimTitles as $dim => $title)
            <div class="card card-pad">
                <h2 class="font-semibold mb-3">{{ $title }}</h2>
                @php $dimMax = max(1, (int) ($breakdowns[$dim]->max('count') ?? 0)); @endphp
                <div class="space-y-2.5">
                    @forelse($breakdowns[$dim] as $row)
                        @php
                            $label = $row['key'];
                            if ($dim === 'country' && strlen($row['key']) === 2 && class_exists(\Locale::class)) {
                                $label = \Locale::getDisplayRegion('-' . strtoupper($row['key']), app()->getLocale()) ?: $row['key'];
                            }
                        @endphp
                        <div>
                            <div class="flex justify-between gap-2 text-sm mb-1">
                                <span class="truncate text-slate-600 dark:text-slate-300">{{ $label }}</span>
                                <span class="font-medium shrink-0">{{ format_number($row['count']) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ max(2, round($row['count'] / $dimMax * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 py-4 text-center">{{ __('No data for this period.') }}</p>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- Devices doughnut --}}
        <div class="card card-pad">
            <h2 class="font-semibold mb-3">{{ __('Devices') }}</h2>
            @if($breakdowns['device']->isEmpty())
                <p class="text-sm text-slate-500 py-4 text-center">{{ __('No data for this period.') }}</p>
            @else
                <div class="h-56"><canvas id="deviceChart"></canvas></div>
            @endif
        </div>

        {{-- Live feed --}}
        <div class="card card-pad" x-data="liveFeed()" x-init="start()">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">{{ __('Live clicks') }}</h2>
                <span class="badge-green"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> {{ __('Live') }}</span>
            </div>
            <div class="space-y-2">
                <template x-for="row in rows" :key="row.id">
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800/60 text-sm">
                        <x-icon name="device" class="h-4 w-4 shrink-0 text-slate-400"/>
                        <span class="min-w-0 flex-1 truncate">
                            <span class="font-medium" x-text="row.country || @js(__('Unknown'))"></span>
                            <span class="text-slate-500" x-text="' · ' + (row.browser || '?') + ' · ' + (row.device || '?')"></span>
                            <span class="block text-xs text-slate-500 truncate" x-text="row.referer"></span>
                        </span>
                        <span class="text-xs text-slate-400 shrink-0" x-text="row.at"></span>
                    </div>
                </template>
            </div>
            <p x-show="rows.length === 0" class="text-sm text-slate-500 py-4 text-center">{{ __('Waiting for clicks…') }}</p>
        </div>
    </div>

    {{-- Hourly heatmap --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Clicks by hour and weekday') }}</h2>
        <div class="overflow-x-auto">
            <div class="min-w-[680px]">
                <div class="grid gap-1" style="grid-template-columns: 3rem repeat(24, minmax(0, 1fr));">
                    <div></div>
                    @for($h = 0; $h < 24; $h++)
                        <div class="text-[10px] text-slate-400 text-center">{{ $h % 3 === 0 ? $h : '' }}</div>
                    @endfor
                    @foreach($heatmap as $dow => $hours)
                        <div class="text-xs text-slate-500 flex items-center">{{ $dayNames[$dow] }}</div>
                        @foreach($hours as $hour => $count)
                            @if($count > 0)
                                <div class="h-5 rounded-sm bg-brand-600" style="opacity: {{ round(0.2 + 0.8 * $count / $heatMax, 2) }}"
                                     title="{{ $dayNames[$dow] }} {{ $hour }}:00 — {{ format_number($count) }} {{ __('clicks') }}"></div>
                            @else
                                <div class="h-5 rounded-sm bg-slate-100 dark:bg-slate-800"></div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live click feed: polls the JSON endpoint every 5 seconds using an id cursor.
    window.liveFeed = () => ({
        rows: [],
        after: 0,
        start() {
            this.poll();
            setInterval(() => this.poll(), 5000);
        },
        async poll() {
            try {
                const url = new URL(@js(route('stats.live')));
                @if($link) url.searchParams.set('link_id', @js($link->id)); @endif
                url.searchParams.set('after', this.after);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (data.length) {
                    this.after = Math.max(this.after, ...data.map(r => r.id));
                    this.rows = [...data, ...this.rows].slice(0, 20);
                }
            } catch (e) { /* offline — retry on next tick */ }
        },
    });

    document.addEventListener('DOMContentLoaded', () => {
        const series = @json($series);
        makeLineChart('statsChart', Object.keys(series), [
            { label: @json(__('Clicks')), data: Object.values(series).map(r => r.clicks) },
            { label: @json(__('Unique')), data: Object.values(series).map(r => r.uniques), color: '#22c55e' },
            { label: @json(__('QR scans')), data: Object.values(series).map(r => r.qr_scans), color: '#f59e0b' },
        ]);

        const devices = @json($breakdowns['device']);
        if (devices.length) {
            makeDoughnut('deviceChart', devices.map(d => d.key), devices.map(d => d.count));
        }
    });
</script>
@endpush
