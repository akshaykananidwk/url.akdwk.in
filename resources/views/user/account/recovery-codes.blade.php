@extends('layouts.app')

@section('title', __('Recovery codes') . ' — ' . site_name())
@section('page-title', __('Recovery codes'))

@section('content')
<div class="max-w-lg mx-auto space-y-5">
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Two-factor authentication is enabled') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            {{ __('Each recovery code can be used once to sign in if you lose access to your authenticator app.') }}
        </p>

        <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 p-3 mb-4 text-sm text-amber-800 dark:text-amber-300 flex gap-2">
            <x-icon name="warning" class="h-5 w-5 shrink-0"/>
            <span>{{ __('Store these codes somewhere safe — they will not be shown again.') }}</span>
        </div>

        <div class="grid grid-cols-2 gap-2 mb-4">
            @foreach($codes as $code)
                <code class="font-mono text-sm text-center rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2.5">{{ $code }}</code>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-2">
            <button type="button" class="btn-secondary" onclick="copyText(@js(implode(PHP_EOL, $codes)))">
                <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy all codes') }}
            </button>
            <a href="{{ route('account.index') }}" class="btn-primary">{{ __('Continue to account settings') }}</a>
        </div>
    </div>
</div>
@endsection
