@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@extends('layouts.base')

@section('title', __('Server error') . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="text-center max-w-sm">
        <div class="text-7xl sm:text-8xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">500</div>
        <h1 class="mt-3 text-xl font-bold tracking-tight">{{ __('Something went wrong') }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __('An unexpected error occurred on our side. We have been notified — please try again in a moment.') }}
        </p>
        <a href="{{ url('/') }}" class="btn-primary mt-6">{{ __('Go to homepage') }}</a>
    </div>
</div>
@endsection
