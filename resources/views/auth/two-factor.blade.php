@extends('layouts.guest')

@section('title', __('Two-factor authentication') . ' — ' . site_name())

@section('content')
<div class="text-center mb-6">
    <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
        <x-icon name="shield" class="h-6 w-6"/>
    </span>
    <h1 class="text-xl font-bold tracking-tight">{{ __('Two-factor authentication') }}</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>
</div>

<form method="POST" action="{{ url('/two-factor') }}" class="space-y-4">
    @csrf
    <x-field name="code" :label="__('Authentication code')">
        <input id="code" type="text" name="code" required autofocus
               inputmode="numeric" autocomplete="one-time-code" maxlength="32"
               class="input text-center text-lg font-semibold tracking-[0.35em]"
               placeholder="123456">
    </x-field>

    <button type="submit" class="btn-primary w-full">{{ __('Verify') }}</button>
</form>

<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
    <a href="{{ route('login') }}" class="text-brand-600 hover:underline">{{ __('Back to login') }}</a>
</p>
@endsection
