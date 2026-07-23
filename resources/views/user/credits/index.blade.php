@extends('layouts.app')

@section('title', __('Credits') . ' — ' . site_name())
@section('page-title', __('Credits'))

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- Balance --}}
    <div class="grid sm:grid-cols-2 gap-4 items-start">
        <x-stat-card :label="__('Credit balance')" :value="format_number($balance)" icon="sparkles"/>
        <p class="text-sm text-slate-500 dark:text-slate-400 sm:pt-2">
            {{ __('Credits let you create links beyond your plan limit — 1 credit per extra link.') }}
        </p>
    </div>

    {{-- Ledger --}}
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th>{{ __('Balance after') }}</th>
                    <th class="text-end">{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td data-label="{{ __('Amount') }}">
                            <span class="font-semibold {{ $t->amount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $t->amount >= 0 ? '+' : '' }}{{ format_number($t->amount) }}
                            </span>
                        </td>
                        <td data-label="{{ __('Reason') }}">{{ \Illuminate\Support\Str::headline($t->reason) }}</td>
                        <td data-label="{{ __('Balance after') }}">{{ format_number($t->balance_after) }}</td>
                        <td data-label="{{ __('Date') }}" class="sm:text-end text-xs text-slate-500">{{ $t->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-0">
                        <x-empty-state icon="sparkles" :title="__('No credit history yet')" :description="__('An admin can grant you credits.')"/>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div>{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
