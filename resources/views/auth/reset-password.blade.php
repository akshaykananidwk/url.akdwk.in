@extends('layouts.guest')

@section('title', __('Reset password') . ' — ' . site_name())

@section('content')
<h1 class="text-xl font-bold tracking-tight">{{ __('Set a new password') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400 mb-6">{{ __('Choose a strong password for your account.') }}</p>

<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <x-field name="email" :label="__('Email address')">
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="input">
    </x-field>

    <x-field name="password" :label="__('New password')" :help="__('At least 8 characters.')">
        <input id="password" type="password" name="password" required minlength="8" autofocus autocomplete="new-password" class="input">
    </x-field>

    <x-field name="password_confirmation" :label="__('Confirm new password')">
        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="input">
    </x-field>

    <button type="submit" class="btn-primary w-full">{{ __('Reset password') }}</button>
</form>
@endsection
