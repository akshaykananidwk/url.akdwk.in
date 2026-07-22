@extends('layouts.admin')

@section('title', __('Admin dashboard') . ' — ' . site_name())
@section('page-title', __('Dashboard'))

@section('content')
<div class="space-y-5">

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
        <x-stat-card :label="__('Users')" :value="format_number($kpis['users'])" icon="users"/>
        <x-stat-card :label="__('Links')" :value="format_number($kpis['links'])" icon="link"/>
        <x-stat-card :label="__('Clicks')" :value="format_number($kpis['clicks'])" icon="chart"/>
        <x-stat-card :label="__('Revenue')" :value="format_money($kpis['revenue'])" icon="card"/>
        <a href="{{ route('admin.payments.index', ['manual' => 1]) }}" class="block">
            <x-stat-card :label="__('Pending payments')" :value="format_number($kpis['pending_payments'])" icon="clock" class="h-full hover:ring-brand-400"/>
        </a>
        <a href="{{ route('admin.abuse.index', ['status' => 'open']) }}" class="block">
            <x-stat-card :label="__('Open reports')" :value="format_number($kpis['open_reports'])" icon="warning" class="h-full hover:ring-brand-400"/>
        </a>
    </div>

    {{-- 30-day charts --}}
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">{{ __('Signups & clicks') }}</h2>
                <span class="text-xs text-slate-500">{{ __('Last 30 days') }}</span>
            </div>
            <div class="h-56 sm:h-64"><canvas id="growthChart"></canvas></div>
        </div>
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">{{ __('Revenue') }}</h2>
                <span class="badge-brand">{{ __('This month') }}: {{ format_money($kpis['revenue_month']) }}</span>
            </div>
            <div class="h-56 sm:h-64"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        {{-- Recent users --}}
        <div class="card">
            <div class="card-pad !pb-0 flex items-center justify-between">
                <h2 class="font-semibold">{{ __('Recent users') }}</h2>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('View all') }}</a>
            </div>
            <div class="p-4 sm:p-6 space-y-1">
                @forelse($recentUsers as $u)
                    <a href="{{ route('admin.users.edit', $u) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/60 min-h-touch">
                        <img src="{{ $u->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full shrink-0">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $u->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $u->email }}</span>
                        </span>
                        <span class="text-xs text-slate-400 shrink-0">{{ $u->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-500 py-6 text-center">{{ __('No users yet.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Recent payments --}}
        <div class="card">
            <div class="card-pad !pb-0 flex items-center justify-between">
                <h2 class="font-semibold">{{ __('Recent payments') }}</h2>
                <a href="{{ route('admin.payments.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('View all') }}</a>
            </div>
            <div class="p-4 sm:p-6 space-y-1">
                @forelse($recentPayments as $p)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/60 min-h-touch">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $p->user?->name ?? __('Deleted user') }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $p->plan?->name ?? '—' }} · {{ $p->gateway }}</span>
                        </span>
                        <span class="text-sm font-semibold shrink-0">{{ format_money($p->total, $p->currency) }}</span>
                        <span class="shrink-0 {{ $p->status === 'completed' ? 'badge-green' : ($p->status === 'pending' ? 'badge-amber' : 'badge-red') }}">{{ __(ucfirst($p->status)) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 py-6 text-center">{{ __('No payments yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const series = @json($series);
        const labels = Object.keys(series);
        makeLineChart('growthChart', labels, [
            { label: @json(__('Signups')), data: Object.values(series).map(r => r.signups) },
            { label: @json(__('Clicks')), data: Object.values(series).map(r => r.clicks), color: '#22c55e' },
        ]);
        makeLineChart('revenueChart', labels, [
            { label: @json(__('Revenue')), data: Object.values(series).map(r => r.revenue), color: '#f59e0b' },
        ], { type: 'bar' });
    });
</script>
@endpush
