@extends('layouts.admin')

@section('title', __('Reports') . ' — ' . site_name())
@section('page-title', __('Reports'))

@section('content')
<div class="space-y-5">

    {{-- 12-month charts --}}
    <div class="grid lg:grid-cols-2 gap-5">
        <div class="card card-pad">
            <h2 class="font-semibold mb-4">{{ __('Revenue by month') }}</h2>
            <div class="h-56 sm:h-64"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="card card-pad">
            <h2 class="font-semibold mb-4">{{ __('Signups by month') }}</h2>
            <div class="h-56 sm:h-64"><canvas id="signupsChart"></canvas></div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5 items-start">
        {{-- Revenue by gateway --}}
        <div class="card">
            <div class="card-pad !pb-0"><h2 class="font-semibold">{{ __('Revenue by gateway') }}</h2></div>
            <div class="overflow-x-auto">
                <table class="table-cards">
                    <thead>
                        <tr>
                            <th>{{ __('Gateway') }}</th>
                            <th>{{ __('Payments') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byGateway as $row)
                            <tr>
                                <td data-label="{{ __('Gateway') }}" class="font-medium">{{ $row->gateway }}</td>
                                <td data-label="{{ __('Payments') }}">{{ format_number($row->count) }}</td>
                                <td data-label="{{ __('Total') }}" class="sm:text-end font-semibold">{{ format_money($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-slate-500 py-8 sm:table-cell">{{ __('No completed payments yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Revenue by plan --}}
        <div class="card">
            <div class="card-pad !pb-0"><h2 class="font-semibold">{{ __('Revenue by plan') }}</h2></div>
            <div class="overflow-x-auto">
                <table class="table-cards">
                    <thead>
                        <tr>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Payments') }}</th>
                            <th class="text-end">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byPlan as $row)
                            <tr>
                                <td data-label="{{ __('Plan') }}" class="font-medium">{{ $row->plan?->name ?? __('Deleted plan') }}</td>
                                <td data-label="{{ __('Payments') }}">{{ format_number($row->count) }}</td>
                                <td data-label="{{ __('Total') }}" class="sm:text-end font-semibold">{{ format_money($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-slate-500 py-8 sm:table-cell">{{ __('No completed payments yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top users --}}
    <div class="card">
        <div class="card-pad !pb-0"><h2 class="font-semibold">{{ __('Top users by clicks') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="table-cards">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Links') }}</th>
                        <th class="text-end">{{ __('Clicks') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topUsers as $u)
                        <tr>
                            <td data-label="#" class="text-slate-400">{{ $loop->iteration }}</td>
                            <td data-label="{{ __('User') }}">
                                <a href="{{ route('admin.users.edit', $u) }}" class="min-w-0 hover:text-brand-600">
                                    <span class="block truncate font-medium">{{ $u->name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ $u->email }}</span>
                                </a>
                            </td>
                            <td data-label="{{ __('Links') }}">{{ format_number($u->links_count) }}</td>
                            <td data-label="{{ __('Clicks') }}" class="sm:text-end font-semibold">{{ format_number($u->links_sum_clicks_count ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-slate-500 py-8 sm:table-cell">{{ __('No users yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const months = @json($months->values());
        makeLineChart('revenueChart', months, [
            { label: @json(__('Revenue')), data: @json($revenue->values()), color: '#f59e0b' },
        ], { type: 'bar' });
        makeLineChart('signupsChart', months, [
            { label: @json(__('Signups')), data: @json($signups->values()) },
        ], { type: 'bar' });
    });
</script>
@endpush
