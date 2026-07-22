@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@extends('layouts.base')

@section('title', __('Access denied') . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="text-center max-w-sm">
        <div class="text-7xl sm:text-8xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">403</div>
        <h1 class="mt-3 text-xl font-bold tracking-tight">{{ __('Access denied') }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ ($exception ?? null)?->getMessage() ?: __("You don't have permission to view this page.") }}
        </p>
        <a href="{{ url('/') }}" class="btn-primary mt-6">{{ __('Go to homepage') }}</a>
    </div>
</div>
@endsection
