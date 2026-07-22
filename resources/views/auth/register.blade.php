@extends('layouts.guest')

@section('title', __('Sign up') . ' — ' . site_name())

@section('content')
<h1 class="text-xl font-bold tracking-tight">{{ __('Create your account') }}</h1>
<p class="mt-1 text-sm text-slate-500 dark:text-slate-400 mb-6">{{ __('Free forever — no credit card required.') }}</p>

@php($providers = \App\Http\Controllers\Auth\SocialAuthController::enabledProviders())
@if($providers)
    <div class="space-y-2 mb-5">
        @foreach($providers as $provider)
            <a href="{{ route('social.redirect', $provider) }}" class="btn-secondary w-full">
                {{ __('Continue with :provider', ['provider' => ['google' => 'Google', 'facebook' => 'Facebook', 'twitter' => 'X', 'github' => 'GitHub', 'apple' => 'Apple'][$provider] ?? ucfirst($provider)]) }}
            </a>
        @endforeach
    </div>
    <div class="relative mb-5">
        <div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-t border-slate-200 dark:border-slate-700"></div></div>
        <div class="relative flex justify-center"><span class="bg-white dark:bg-slate-900 px-3 text-xs uppercase tracking-wide text-slate-400">{{ __('or') }}</span></div>
    </div>
@endif

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

    <x-field name="name" :label="__('Full name')">
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus maxlength="100" autocomplete="name" class="input">
    </x-field>

    <x-field name="email" :label="__('Email address')">
        <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" class="input">
    </x-field>

    <x-field name="password" :label="__('Password')" :help="__('At least 8 characters.')">
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="input">
    </x-field>

    <x-field name="password_confirmation" :label="__('Confirm password')">
        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="input">
    </x-field>

    <x-field name="terms">
        <label class="flex items-start gap-2.5 min-h-touch cursor-pointer select-none">
            <input type="checkbox" name="terms" value="1" required class="checkbox mt-0.5" @checked(old('terms'))>
            <span class="text-sm text-slate-600 dark:text-slate-300">
                {!! __('I agree to the :link', ['link' => '<a href="' . route('page', 'terms') . '" target="_blank" rel="noopener" class="text-brand-600 hover:underline">' . e(__('Terms of Service')) . '</a>']) !!}
            </span>
        </label>
    </x-field>

    @include('partials.captcha')

    <button type="submit" class="btn-primary w-full">{{ __('Create account') }}</button>
</form>

<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
    {{ __('Already have an account?') }}
    <a href="{{ route('login') }}" class="text-brand-600 font-medium hover:underline">{{ __('Log in') }}</a>
</p>
@endsection
