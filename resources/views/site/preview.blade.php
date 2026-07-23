@extends('layouts.landing')

@section('title', $title . ' — ' . __('Link preview') . ' — ' . site_name())

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <div class="max-w-xl mx-auto">
        <nav class="mb-6 text-sm text-slate-500 dark:text-slate-400">
            <a href="{{ url('/') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ site_name() }}</a>
            <x-icon name="chevron-right" class="inline h-4 w-4 -mt-0.5"/>
            <span>{{ __('Link preview') }}</span>
        </nav>

        <div class="card card-pad text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                <x-icon name="link" class="h-7 w-7"/>
            </span>

            <h1 class="mt-4 text-xl sm:text-2xl font-bold tracking-tight break-words">{{ $title }}</h1>

            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ __('You are about to leave :site and continue to:', ['site' => site_name()]) }}
            </p>

            <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm font-medium max-w-full">
                <x-icon name="globe" class="h-4 w-4 text-slate-400 shrink-0"/>
                <span class="truncate">{{ $host }}</span>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-4 py-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('Total clicks') }}</div>
                    <div class="mt-0.5 text-lg font-bold">{{ format_number($link->clicks_count ?? 0) }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 px-4 py-3">
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('Created') }}</div>
                    <div class="mt-0.5 text-lg font-bold">{{ $link->created_at?->translatedFormat('M j, Y') ?? '—' }}</div>
                </div>
            </div>

            <a href="{{ $shortUrl }}" rel="nofollow noopener" class="btn-primary w-full mt-6 justify-center inline-flex items-center gap-2">
                <span>{{ __('Continue to destination') }}</span>
                <x-icon name="external" class="h-4 w-4"/>
            </a>

            <div class="mt-3 text-xs text-slate-400 break-all">{{ $shortUrl }}</div>

            @if($qrSvg)
                <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-3">{{ __('Scan to open on another device') }}</div>
                    <div class="mx-auto inline-block rounded-xl bg-white p-3 shadow-sm">
                        <div class="h-40 w-40">{!! $qrSvg !!}</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="card card-pad mt-4 flex items-start gap-3">
            <x-icon name="shield" class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5"/>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Always check the destination before continuing. :site does not control the content of external websites. Never enter passwords or payment details unless you trust the destination.', ['site' => site_name()]) }}
            </p>
        </div>
    </div>
</div>
@endsection
