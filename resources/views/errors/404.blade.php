@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@extends('layouts.base')

@section('title', __('Page not found') . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="text-center max-w-sm">
        <div class="text-7xl sm:text-8xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">404</div>
        <h1 class="mt-3 text-xl font-bold tracking-tight">{{ __('Page not found') }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __("The page or short link you're looking for doesn't exist — it may have been removed or the URL was mistyped.") }}
        </p>
        <a href="{{ url('/') }}" class="btn-primary mt-6">{{ __('Go to homepage') }}</a>
    </div>
</div>
@endsection
