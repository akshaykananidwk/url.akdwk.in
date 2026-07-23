@extends('layouts.guest')

@section('title', __('Log in') . ' — ' . site_name())

@section('content')
<h1 class="text-xl font-bold tracking-tight">{{ __('Welcome back') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400 mb-6">{{ __('Log in to manage your links.') }}</p>

@php($providers = \App\Http\Controllers\Auth\SocialAuthController::enabledProviders())
@php($ssoProviders = \App\Http\Controllers\Auth\SsoController::active())
@if($providers || $ssoProviders->isNotEmpty())
    <div class="space-y-2 mb-5">
        @foreach($providers as $provider)
            <a href="{{ route('social.redirect', $provider) }}" class="btn-secondary w-full">
                {{ __('Continue with :provider', ['provider' => ['google' => 'Google', 'facebook' => 'Facebook', 'twitter' => 'X', 'github' => 'GitHub', 'apple' => 'Apple'][$provider] ?? ucfirst($provider)]) }}
            </a>
        @endforeach

        @if($providers && $ssoProviders->isNotEmpty())
            <div class="relative py-1">
                <div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-t border-slate-200 dark:border-slate-700"></div></div>
                <div class="relative flex justify-center"><span class="bg-white dark:bg-slate-900 px-3 text-xs uppercase tracking-wide text-slate-400">{{ __('single sign-on') }}</span></div>
            </div>
        @endif

        @foreach($ssoProviders as $p)
            <a href="{{ route('sso.redirect', $p->slug) }}" class="btn-secondary w-full">
                <x-icon name="lock" class="h-4 w-4"/> {{ __('Sign in with :name', ['name' => $p->name]) }}
            </a>
        @endforeach
    </div>
    <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-t border-slate-200 dark:border-slate-700"></div></div>
        <div class="relative flex justify-center"><span class="bg-white dark:bg-slate-900 px-3 text-xs uppercase tracking-wide text-slate-400">{{ __('or') }}</span></div>
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

    <x-field name="email" :label="__('Email address')">
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
    </x-field>

    <x-field name="password">
        <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="label !mb-0">{{ __('Password') }}</label>
            <a href="{{ route('password.request') }}" class="text-xs text-brand-600 hover:underline">{{ __('Forgot password?') }}</a>
        </div>
        <input id="password" type="password" name="password" required autocomplete="current-password" class="input">
    </x-field>

    <label class="flex items-center gap-2.5 min-h-touch cursor-pointer select-none">
        <input type="checkbox" name="remember" value="1" class="checkbox" @checked(old('remember'))>
        <span class="text-sm text-slate-600 dark:text-slate-300">{{ __('Remember me') }}</span>
    </label>

    @include('partials.captcha')

    <button type="submit" class="btn-primary w-full">{{ __('Log in') }}</button>
</form>

@if(setting('registration_enabled', true))
    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __("Don't have an account?") }}
        <a href="{{ route('register') }}" class="text-brand-600 font-medium hover:underline">{{ __('Sign up free') }}</a>
    </p>
@endif
@endsection
