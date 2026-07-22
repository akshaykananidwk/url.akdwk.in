@extends('layouts.guest')

@section('title', __('Forgot password') . ' — ' . site_name())

@section('content')
<h1 class="text-xl font-bold tracking-tight">{{ __('Forgot your password?') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400 mb-6">
    {{ __("No problem. Enter your email address and we'll send you a link to reset it.") }}
</p>

@if(session('status'))
    <div class="mb-5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <x-field name="email" :label="__('Email address')">
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
    </x-field>

    <button type="submit" class="btn-primary w-full">{{ __('Send reset link') }}</button>
</form>

<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
    <a href="{{ route('login') }}" class="text-brand-600 hover:underline">{{ __('Back to login') }}</a>
</p>
@endsection
