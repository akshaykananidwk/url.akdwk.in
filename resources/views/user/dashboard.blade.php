@extends('layouts.app')

@section('title', __('Dashboard') . ' — ' . site_name())
@section('page-title', __('Dashboard'))

@section('content')
<div class="space-y-5">

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
        <x-stat-card :label="__('Total links')" :value="format_number($kpis['links'])" icon="link"/>
        <x-stat-card :label="__('Total clicks')" :value="format_number($kpis['clicks'])" icon="chart"/>
        <x-stat-card :label="__('Clicks today')" :value="format_number($kpis['today'])" icon="bolt"/>
        <x-stat-card :label="__('Spaces')" :value="format_number($kpis['spaces'])" icon="folder"/>
        <x-stat-card :label="__('Domains')" :value="format_number($kpis['domains'])" icon="globe"/>
        <x-stat-card :label="__('Pixels')" :value="format_number($kpis['pixels'])" icon="target"/>
    </div>

    {{-- Clicks chart --}}
    <div class="card card-pad">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="font-semibold">{{ __('Clicks') }}</h2>
            <div class="flex rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-0.5 text-xs font-medium">
                @foreach([7, 30, 90] as $d)
                    <a href="{{ route('dashboard', ['days' => $d]) }}"
                       class="px-3 py-2 rounded-lg min-h-[36px] inline-flex items-center {{ $days === $d ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300' }}">
                        {{ __(':n days', ['n' => $d]) }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="h-56 sm:h-72"><canvas id="clicksChart"></canvas></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        {{-- Top links --}}
        <div class="card">
            <div class="card-pad !pb-0 flex items-center justify-between">
                <h2 class="font-semibold">{{ __('Top performing links') }}</h2>
                <a href="{{ route('links.index', ['sort' => 'clicks_count']) }}" class="text-sm text-brand-600 hover:underline">{{ __('View all') }}</a>
            </div>
            <div class="p-4 sm:p-6 space-y-2">
                @forelse($topLinks as $link)
                    <a href="{{ route('stats.link', $link) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/60 min-h-touch">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400 text-xs font-bold">{{ $loop->iteration }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $link->title ?: $link->alias }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $link->shortUrl() }}</span>
                        </span>
                        <span class="badge-brand shrink-0">{{ format_number($link->clicks_count) }} {{ __('clicks') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500 py-6 text-center">{{ __('No clicks yet — share your first link!') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Recent links --}}
        <div class="card">
            <div class="card-pad !pb-0 flex items-center justify-between">
                <h2 class="font-semibold">{{ __('Recent links') }}</h2>
                <a href="{{ route('links.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('View all') }}</a>
            </div>
            <div class="p-4 sm:p-6 space-y-2">
                @forelse($recentLinks as $link)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                        <span class="min-w-0 flex-1">
                            <a href="{{ route('links.edit', $link) }}" class="block truncate text-sm font-medium hover:text-brand-600">{{ $link->shortUrl() }}</a>
                            <span class="block truncate text-xs text-slate-500">{{ $link->destination }}</span>
                        </span>
                        <button class="btn-ghost btn-sm shrink-0" onclick="copyText(@js($link->shortUrl()))" aria-label="{{ __('Copy link') }}">
                            <x-icon name="copy" class="h-4 w-4"/>
                        </button>
                    </div>
                @empty
                    <x-empty-state :title="__('No links yet')" :description="__('Create your first short link to get started.')">
                        <a href="{{ route('links.create') }}" class="btn-primary btn-sm">{{ __('Create link') }}</a>
                    </x-empty-state>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Plan usage --}}
    <div class="card card-pad">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">{{ __('Plan usage') }} — {{ $plan->name }}</h2>
            <a href="{{ route('billing.plans') }}" class="text-sm text-brand-600 hover:underline">{{ __('Upgrade') }}</a>
        </div>
        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-4">
            @foreach($usage as $row)
                <div>
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="text-slate-600 dark:text-slate-300">{{ __($row['label']) }}</span>
                        <span class="font-medium">
                            {{ format_number($row['used']) }} / {{ $row['unlimited'] ? '∞' : format_number($row['limit']) }}
                        </span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full {{ $row['percent'] >= 90 ? 'bg-rose-500' : 'bg-brand-500' }}" style="width: {{ $row['unlimited'] ? 4 : max(2, $row['percent']) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const series = @json($series);
        makeLineChart('clicksChart', Object.keys(series), [
            { label: @json(__('Clicks')), data: Object.values(series).map(r => r.clicks) },
            { label: @json(__('Unique')), data: Object.values(series).map(r => r.uniques), color: '#22c55e' },
        ]);
    });
</script>
@endpush
