@extends('layouts.app')

@section('title', $title . ' — ' . site_name())
@section('page-title', $title)

@section('content')
<div class="max-w-lg mx-auto space-y-5">

    <div class="card card-pad">
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                <x-icon name="card" class="h-6 w-6"/>
            </span>
            <div>
                <h1 class="font-bold text-lg">{{ $title }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $instructions }}</p>
            </div>
        </div>

        {{-- Payment details --}}
        <div class="mt-5 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($details as $label => $value)
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <div class="text-xs text-slate-400">{{ $label }}</div>
                        <div class="text-sm font-semibold break-all">{{ $value }}</div>
                    </div>
                    <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js((string) $value))" aria-label="{{ __('Copy :label', ['label' => $label]) }}">
                        <x-icon name="copy" class="h-4 w-4"/>
                    </button>
                </div>
            @endforeach
        </div>

        @if($qrSvg)
            <div class="mt-5 flex flex-col items-center gap-3">
                <div class="rounded-2xl bg-white p-3 ring-1 ring-slate-200 [&_svg]:h-auto [&_svg]:max-w-full">
                    {!! $qrSvg !!}
                </div>
                @if($upiUri)
                    <a href="{{ $upiUri }}" class="btn-primary w-full sm:w-auto">
                        <x-icon name="phone" class="h-4 w-4"/> {{ __('Open UPI app') }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Reference submission --}}
    <form method="POST" action="{{ route('billing.return', ['gateway' => $gateway->key(), 'payment' => $payment->id]) }}" class="card card-pad space-y-4">
        @csrf
        <div>
            <h2 class="font-semibold">{{ __('Already paid?') }}</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Submit your transaction reference so our team can verify and activate your plan.') }}</p>
        </div>

        <x-field name="reference" :label="__('Transaction reference')">
            <input id="reference" type="text" name="reference" value="{{ old('reference') }}" required minlength="4" maxlength="64" autocomplete="off" class="input" placeholder="{{ __('e.g. UTR / transfer number') }}">
        </x-field>

        <x-field name="note" :label="__('Note (optional)')">
            <textarea id="note" name="note" rows="3" maxlength="1000" class="input" placeholder="{{ __('Anything we should know…') }}">{{ old('note') }}</textarea>
        </x-field>

        <button type="submit" class="btn-primary w-full">{{ __('Submit for verification') }}</button>
        <p class="help !mt-2 text-center">{{ __('Order #:id — your plan activates as soon as the payment is approved.', ['id' => $payment->id]) }}</p>
    </form>
</div>
@endsection
