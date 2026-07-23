@extends('layouts.app')

@section('title', __('Tips') . ' — ' . site_name())
@section('page-title', __('Tips'))

@section('content')
<div class="space-y-5">
    <div class="grid grid-cols-2 gap-3 sm:gap-4 max-w-md">
        <x-stat-card :label="__('Total received')" :value="format_money($totalReceived)" icon="gift"/>
        <x-stat-card :label="__('Pending')" :value="format_number($pendingCount)" icon="clock"/>
    </div>

    @if($tips->isEmpty())
        <x-empty-state icon="gift" :title="__('No tips yet')"
            :description="__('Add a Tip jar block to a bio page — tips from supporters will show up here.')">
            <a href="{{ route('bio.index') }}" class="btn-primary btn-sm">{{ __('Go to bio pages') }}</a>
        </x-empty-state>
    @else
        <div class="card overflow-hidden">
            <table class="table-cards">
                <thead>
                    <tr>
                        <th>{{ __('Supporter') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Page') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tips as $tip)
                        <tr>
                            <td data-label="{{ __('Supporter') }}">
                                <div class="min-w-0">
                                    <div class="font-medium truncate">{{ $tip->supporter_name ?: __('Anonymous') }}</div>
                                    @if($tip->message)<div class="text-xs text-slate-500 truncate">{{ $tip->message }}</div>@endif
                                </div>
                            </td>
                            <td data-label="{{ __('Amount') }}" class="font-semibold">{{ format_money($tip->amount, $tip->currency) }}</td>
                            <td data-label="{{ __('Page') }}">{{ $tip->bioPage ? '@'.$tip->bioPage->username : '—' }}</td>
                            <td data-label="{{ __('Method') }}"><span class="badge-gray uppercase">{{ $tip->gateway ?: '—' }}</span></td>
                            <td data-label="{{ __('Status') }}">
                                @if($tip->status === 'completed')
                                    <span class="badge-green">{{ __('Completed') }}</span>
                                @elseif($tip->status === 'failed')
                                    <span class="badge-red">{{ __('Failed') }}</span>
                                @else
                                    <span class="badge-amber">{{ __('Pending') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Date') }}" class="text-slate-500">{{ $tip->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $tips->links() }}
        <p class="help">{{ __('Tips are collected through your configured payment method (UPI / PayPal / link). Payment happens on the provider — mark tips as received there.') }}</p>
    @endif
</div>
@endsection
