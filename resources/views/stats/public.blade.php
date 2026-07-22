@extends('layouts.landing')

@section('title', __('Stats for :alias', ['alias' => $link->alias]) . ' — ' . site_name())

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@php
    $trend = function (string $key) use ($totals, $previous) {
        $prev = (int) ($previous[$key] ?? 0);
        if ($prev <= 0) {
            return null;
        }
        return (int) round((($totals[$key] ?? 0) - $prev) / $prev * 100);
    };
    $destinationHost = parse_url($link->destination, PHP_URL_HOST) ?: $link->destination;
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-10 space-y-5">

    {{-- Header --}}
    <div class="card card-pad flex flex-col sm:flex-row sm:items-center gap-4">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon name="chart" class="h-6 w-6"/>
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ $link->shortUrl() }}" target="_blank" rel="noopener" class="font-bold text-lg text-brand-600 hover:underline truncate">{{ $link->shortUrl() }}</a>
                <button class="btn-ghost btn-sm" onclick="copyText(@js($link->shortUrl()))" aria-label="{{ __('Copy link') }}">
                    <x-icon name="copy" class="h-4 w-4"/>
                </button>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 truncate">{{ __('Destination') }}: {{ $destinationHost }}</p>
        </div>
        {{-- Range pills --}}
        <div class="flex rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-0.5 text-xs font-medium self-start sm:self-center">
            @foreach([7, 30, 90] as $d)
                <a href="{{ route('stats.public', ['alias' => $link->alias, 'range' => $d]) }}"
                   class="px-3 py-2 rounded-lg min-h-[36px] inline-flex items-center {{ ($range['preset'] ?? '30') == (string) $d ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __(':n days', ['n' => $d]) }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-stat-card :label="__('Clicks')" :value="format_number($totals['clicks'])" icon="chart" :trend="$trend('clicks')"/>
        <x-stat-card :label="__('Unique visitors')" :value="format_number($totals['uniques'])" icon="users" :trend="$trend('uniques')"/>
        <x-stat-card :label="__('QR scans')" :value="format_number($totals['qr_scans'])" icon="qr" :trend="$trend('qr_scans')"/>
    </div>

    {{-- Clicks chart --}}
    <div class="card card-pad">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="font-semibold">{{ __('Clicks over time') }}</h2>
            <span class="text-xs text-slate-400">{{ $from->translatedFormat('M j, Y') }} – {{ $to->translatedFormat('M j, Y') }}</span>
        </div>
        <div class="h-56 sm:h-72"><canvas id="publicClicksChart"></canvas></div>
    </div>

    {{-- Breakdowns --}}
    <div class="grid sm:grid-cols-2 gap-5">
        @foreach([
            'country' => __('Top countries'),
            'referer' => __('Top referrers'),
            'os' => __('Operating systems'),
            'browser' => __('Browsers'),
            'device' => __('Devices'),
        ] as $dimension => $heading)
            @php($rows = $breakdowns[$dimension] ?? collect())
            <div class="card card-pad">
                <h2 class="font-semibold mb-4">{{ $heading }}</h2>
                @if($rows->isEmpty())
                    <p class="text-sm text-slate-400 py-4 text-center">{{ __('No data for this period.') }}</p>
                @else
                    @php($max = max(1, (int) $rows->max('count')))
                    <div class="space-y-3">
                        @foreach($rows->take(8) as $row)
                            <div>
                                <div class="flex items-center justify-between gap-3 text-sm mb-1">
                                    <span class="truncate text-slate-600 dark:text-slate-300">{{ $row['key'] !== '' ? $row['key'] : __('Unknown') }}</span>
                                    <span class="font-semibold shrink-0">{{ format_number($row['count']) }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div class="h-full rounded-full bg-brand-500" style="width: {{ max(2, (int) round($row['count'] / $max * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        {{-- CTA card --}}
        <div class="card card-pad flex flex-col items-center justify-center text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                <x-icon name="bolt" class="h-6 w-6"/>
            </span>
            <h2 class="mt-3 font-semibold">{{ __('Want stats like this for your links?') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Create free short links with real-time analytics on :site.', ['site' => site_name()]) }}</p>
            <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Get started free') }}</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const series = @json($series);
        makeLineChart('publicClicksChart', Object.keys(series), [
            { label: @json(__('Clicks')), data: Object.values(series).map(r => r.clicks) },
            { label: @json(__('Unique')), data: Object.values(series).map(r => r.uniques), color: '#22c55e' },
        ]);
    });
</script>
@endpush
