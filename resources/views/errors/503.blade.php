@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@extends('layouts.base')

@section('title', __('Service unavailable') . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="text-center max-w-sm">
        <div class="text-7xl sm:text-8xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">503</div>
        <h1 class="mt-3 text-xl font-bold tracking-tight">{{ __("We'll be right back") }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ setting('maintenance_message', '') ?: __('The service is briefly unavailable for maintenance. Please check back in a few minutes.') }}
        </p>
        <button type="button" onclick="location.reload()" class="btn-primary mt-6">
            <x-icon name="refresh" class="h-4 w-4"/> {{ __('Try again') }}
        </button>
    </div>
</div>
@endsection
