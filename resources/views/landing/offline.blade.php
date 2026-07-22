@extends('layouts.base')

@section('title', __("You're offline") . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-sm">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
            <x-icon name="bolt" class="h-8 w-8"/>
        </span>
        <h1 class="mt-5 text-2xl font-bold tracking-tight">{{ __("You're offline") }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __('It looks like your internet connection dropped. Check your network and try again.') }}
        </p>
        <button type="button" onclick="location.reload()" class="btn-primary mt-6">
            <x-icon name="refresh" class="h-4 w-4"/> {{ __('Retry') }}
        </button>
    </div>
</div>
@endsection
