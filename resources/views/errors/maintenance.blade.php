@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@extends('layouts.base')

@section('title', __('Maintenance') . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="text-center max-w-sm">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
            <x-icon name="wrench" class="h-8 w-8"/>
        </span>
        <h1 class="mt-5 text-2xl font-bold tracking-tight">{{ __("We'll be right back") }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ setting('maintenance_message', '') ?: __('We are performing scheduled maintenance to make things even better. Thanks for your patience!') }}
        </p>
        <button type="button" onclick="location.reload()" class="btn-primary mt-6">
            <x-icon name="refresh" class="h-4 w-4"/> {{ __('Check again') }}
        </button>
    </div>
</div>
@endsection
