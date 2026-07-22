@extends('layouts.guest')

@section('title', __('Verify your email') . ' — ' . site_name())

@section('content')
<div class="text-center">
    <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
        <x-icon name="mail" class="h-6 w-6"/>
    </span>
    <h1 class="text-xl font-bold tracking-tight">{{ __('Verify your email address') }}</h1>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        {{ __("We've sent a verification link to :email. Click the link in that email to activate your account.", ['email' => auth()->user()->email]) }}
    </p>

    @if(session('status'))
        <div class="mt-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}" class="mt-6">
        @csrf
        <button type="submit" class="btn-primary w-full">{{ __('Resend verification email') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn-ghost w-full">{{ __('Log out') }}</button>
    </form>
</div>
@endsection
