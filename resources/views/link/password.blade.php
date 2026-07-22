@extends('layouts.base')

@section('title', __('Protected link') . ' — ' . site_name())

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm card card-pad text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon name="lock" class="h-7 w-7"/>
        </span>
        <h1 class="mt-4 text-lg font-bold tracking-tight">{{ __('This link is password protected') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Enter the password to continue to the destination.') }}</p>

        <form method="POST" action="{{ route('redirect.unlock', $alias) }}" class="mt-5 space-y-4 text-start">
            @csrf
            <x-field name="password">
                <input id="password" type="password" name="password" required autofocus autocomplete="off"
                       class="input text-center" placeholder="{{ __('Password') }}" aria-label="{{ __('Password') }}">
            </x-field>
            <button type="submit" class="btn-primary w-full">{{ __('Unlock') }}</button>
        </form>
    </div>
</div>
@endsection
