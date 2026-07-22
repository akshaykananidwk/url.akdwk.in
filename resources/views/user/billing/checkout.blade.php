@extends('layouts.app')

@section('title', __('Checkout') . ' — ' . site_name())
@section('page-title', __('Checkout'))

@php
    $cycleLabels = ['monthly' => __('Monthly'), 'yearly' => __('Yearly'), 'lifetime' => __('Lifetime')];
@endphp

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Order summary --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Order summary') }}</h2>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between gap-3">
                <dt class="text-slate-600 dark:text-slate-300">{{ $plan->name }} — {{ $cycleLabels[$cycle] }}</dt>
                <dd class="font-medium">{{ format_money($quote['amount'], $quote['currency']) }}</dd>
            </div>
            @if($quote['discount'] > 0)
                <div class="flex justify-between gap-3 text-emerald-600 dark:text-emerald-400">
                    <dt>{{ __('Discount') }} @if($coupon)({{ $coupon->code }})@endif</dt>
                    <dd class="font-medium">−{{ format_money($quote['discount'], $quote['currency']) }}</dd>
                </div>
            @endif
            @if($quote['tax_rate'])
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-600 dark:text-slate-300">{{ $quote['tax_rate']->name }} ({{ $quote['tax_rate']->rate + 0 }}%)</dt>
                    <dd class="font-medium">{{ format_money($quote['tax'], $quote['currency']) }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-3 border-t border-slate-200 dark:border-slate-700 pt-2.5 text-base">
                <dt class="font-semibold">{{ __('Total') }}</dt>
                <dd class="font-bold">{{ format_money($quote['total'], $quote['currency']) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Coupon --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-3">{{ __('Coupon') }}</h2>
        <form method="GET" action="{{ route('billing.checkout', $plan) }}" class="flex gap-2">
            <input type="hidden" name="cycle" value="{{ $cycle }}">
            <input type="text" name="coupon" value="{{ $coupon?->code ?? request('coupon') }}" class="input flex-1 uppercase" placeholder="{{ __('Coupon code') }}" autocapitalize="characters">
            <button type="submit" class="btn-secondary shrink-0">{{ __('Apply') }}</button>
        </form>
        @error('coupon')<p class="error">{{ $message }}</p>@enderror
        @if($coupon)
            <p class="help">{{ __('Coupon :code applied.', ['code' => $coupon->code]) }}</p>
        @endif
    </div>

    {{-- Payment --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Payment method') }}</h2>

        @if(empty($gateways))
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 p-4 text-sm text-amber-800 dark:text-amber-300">
                {{ __('No payment methods are configured. Please contact support.') }}
            </div>
        @else
            <form method="POST" action="{{ route('billing.pay', $plan) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="cycle" value="{{ $cycle }}">
                @if($coupon)
                    <input type="hidden" name="coupon" value="{{ $coupon->code }}">
                @endif

                @if($quote['tax_rate'])
                    <x-field name="tax_id" :label="$quote['tax_rate']->tax_id_label ?? __('Tax ID')" :help="__('Optional — printed on your invoice.')">
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}" class="input">
                    </x-field>
                @endif

                <div class="space-y-2">
                    @foreach($gateways as $gateway)
                        <label class="flex items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-4 cursor-pointer min-h-touch
                                      has-[:checked]:ring-2 has-[:checked]:ring-brand-500">
                            <input type="radio" name="gateway" value="{{ $gateway->key() }}" class="checkbox !rounded-full" @checked(old('gateway') ? old('gateway') === $gateway->key() : $loop->first)>
                            <span class="font-medium text-sm">{{ $gateway->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('gateway')<p class="error">{{ $message }}</p>@enderror

                <button type="submit" class="btn-primary w-full">
                    <x-icon name="lock" class="h-4 w-4"/> {{ __('Pay :amount', ['amount' => format_money($quote['total'], $quote['currency'])]) }}
                </button>
                <p class="help text-center">{{ __('You will be redirected to complete the payment securely.') }}</p>
            </form>
        @endif
    </div>

    <p class="text-center">
        <a href="{{ route('billing.plans') }}" class="text-sm text-brand-600 hover:underline">{{ __('Back to plans') }}</a>
    </p>
</div>
@endsection
