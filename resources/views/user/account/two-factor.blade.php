@extends('layouts.app')

@section('title', __('Two-factor authentication') . ' — ' . site_name())
@section('page-title', __('Two-factor authentication'))

@section('content')
<div class="max-w-lg mx-auto space-y-5">
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Set up your authenticator app') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            {{ __('Scan the QR code with Google Authenticator, 1Password, Authy or any TOTP app, then enter the 6-digit code to confirm.') }}
        </p>

        <div class="flex justify-center mb-4">
            <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200 dark:ring-slate-700 [&_svg]:h-52 [&_svg]:w-52">
                {!! $qrSvg !!}
            </div>
        </div>

        <div class="mb-5">
            <p class="label">{{ __("Can't scan? Enter this key manually") }}</p>
            <div class="flex items-center gap-2">
                <code class="font-mono text-sm tracking-widest rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 flex-1 overflow-x-auto">{{ $secret }}</code>
                <button type="button" class="btn-secondary btn-sm shrink-0" onclick="copyText(@js($secret))" aria-label="{{ __('Copy key') }}">
                    <x-icon name="copy" class="h-4 w-4"/>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('account.two-factor.confirm') }}" class="space-y-4">
            @csrf
            <x-field name="code" :label="__('6-digit code')">
                <input type="text" name="code" required class="input text-center text-lg tracking-[0.5em] font-mono"
                       inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456" autofocus>
            </x-field>
            <div class="flex justify-end gap-2">
                <a href="{{ route('account.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn-primary"><x-icon name="shield" class="h-4 w-4"/> {{ __('Confirm & enable') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
