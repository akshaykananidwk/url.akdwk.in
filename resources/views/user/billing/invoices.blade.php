@extends('layouts.app')

@section('title', __('Billing history') . ' — ' . site_name())
@section('page-title', __('Billing history'))

@section('content')
<div class="space-y-5">

    {{-- Subscription --}}
    @if($subscription)
        <div class="card card-pad">
            <div class="flex flex-wrap items-center gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold">{{ $subscription->plan?->name ?? __('Subscription') }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        @if($subscription->cycle === 'lifetime')
                            {{ __('Lifetime access') }}
                        @elseif($subscription->ends_at)
                            {{ $subscription->auto_renew ? __('Renews on :date', ['date' => $subscription->ends_at->format('M j, Y')]) : __('Expires on :date', ['date' => $subscription->ends_at->format('M j, Y')]) }}
                        @endif
                        · {{ $subscription->auto_renew ? __('Auto-renewal on') : __('Auto-renewal off') }}
                    </p>
                </div>
                <span class="{{ $subscription->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ __(ucfirst($subscription->status)) }}</span>
                @if($subscription->status === 'active' && $subscription->auto_renew)
                    <x-confirm :action="route('billing.cancel')" method="POST" :title="__('Cancel auto-renewal?')"
                               :message="__('Your plan stays active until the end of the current period.')" :button="__('Cancel renewal')">
                        <button type="button" class="btn-danger btn-sm">{{ __('Cancel renewal') }}</button>
                    </x-confirm>
                @endif
            </div>
        </div>
    @endif

    {{-- Payments --}}
    @if($payments->isEmpty())
        <x-empty-state icon="card" :title="__('No payments yet')" :description="__('Your invoices will appear here once you upgrade to a paid plan.')">
            <a href="{{ route('billing.plans') }}" class="btn-primary btn-sm">{{ __('View plans') }}</a>
        </x-empty-state>
    @else
        <div class="card overflow-hidden">
            <table class="table-cards">
                <thead>
                    <tr>
                        <th>{{ __('Invoice') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('PDF') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td data-label="{{ __('Invoice') }}"><span class="font-mono text-xs">{{ $payment->invoice_number ?: '—' }}</span></td>
                            <td data-label="{{ __('Plan') }}">{{ $payment->plan?->name ?? '—' }} <span class="text-xs text-slate-500">({{ $payment->cycle }})</span></td>
                            <td data-label="{{ __('Total') }}"><span class="font-medium">{{ format_money($payment->total, $payment->currency) }}</span></td>
                            <td data-label="{{ __('Method') }}">{{ ucfirst($payment->gateway) }}</td>
                            <td data-label="{{ __('Status') }}">
                                <span class="{{ ['completed' => 'badge-green', 'pending' => 'badge-amber', 'failed' => 'badge-red', 'refunded' => 'badge-gray', 'declined' => 'badge-red'][$payment->status] ?? 'badge-gray' }}">
                                    {{ __(ucfirst($payment->status)) }}
                                </span>
                            </td>
                            <td data-label="{{ __('Date') }}">{{ $payment->created_at->format('M j, Y') }}</td>
                            <td data-label="{{ __('PDF') }}">
                                <span class="flex justify-end">
                                    @if($payment->status === 'completed')
                                        <a href="{{ route('billing.invoice.pdf', $payment) }}" class="btn-ghost btn-sm" aria-label="{{ __('Download invoice') }}">
                                            <x-icon name="download" class="h-4 w-4"/>
                                        </a>
                                    @else
                                        —
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $payments->links() }}</div>
    @endif
</div>
@endsection
