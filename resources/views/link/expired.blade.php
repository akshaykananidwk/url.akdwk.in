@extends('layouts.base')

@section('title', __('Link unavailable') . ' — ' . site_name())

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm card card-pad text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
            <x-icon name="clock" class="h-7 w-7"/>
        </span>
        @if($reason === 'disabled')
            <h1 class="mt-4 text-lg font-bold tracking-tight">{{ __('This link has been disabled') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('The owner has turned this short link off. It may come back later.') }}</p>
        @elseif($reason === 'scheduled')
            <h1 class="mt-4 text-lg font-bold tracking-tight">{{ __('This link is not active yet') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('This link is not active yet. Please check back later.') }}</p>
            @if($link->starts_at)
                <p class="mt-2 text-sm font-medium">{{ __('Goes live on :time', ['time' => $link->starts_at->format('M j, Y — H:i')]) }}</p>
            @endif
        @else
            <h1 class="mt-4 text-lg font-bold tracking-tight">{{ __('This link has expired') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('The short link you followed is no longer active.') }}</p>
        @endif
        <a href="{{ url('/') }}" class="btn-primary mt-6 w-full">{{ __('Go to homepage') }}</a>
    </div>
</div>
@endsection
