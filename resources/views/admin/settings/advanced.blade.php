@extends('layouts.admin')

@section('title', __('Advanced') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<div class="space-y-5 max-w-4xl">

    {{-- Cron status --}}
    <div class="card card-pad space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold">{{ __('Cron') }}</h2>
            @if($cron['healthy'])
                <span class="badge-green">{{ __('Healthy') }}</span>
            @else
                <span class="badge-red">{{ __('Not running') }}</span>
            @endif
        </div>
        <p class="text-sm text-slate-500">
            {{ __('Last run:') }}
            <span class="font-medium text-slate-700 dark:text-slate-300">
                {{ $cron['last_run'] ? now()->parse($cron['last_run'])->diffForHumans() : __('never') }}
            </span>
        </p>
        <div>
            <p class="label">{{ __('Add this cron entry on the server (runs every minute):') }}</p>
            <div class="flex items-start gap-2">
                <pre class="flex-1 min-w-0 overflow-x-auto rounded-xl bg-slate-100 dark:bg-slate-800 p-3 text-xs"><code>{{ $cron['command'] }}</code></pre>
                <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js($cron['command']))" aria-label="{{ __('Copy') }}">
                    <x-icon name="copy" class="h-4 w-4"/>
                </button>
            </div>
        </div>
    </div>

    {{-- Queue --}}
    <div class="card card-pad space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold">{{ __('Queue worker') }}</h2>
            <span class="inline-flex gap-2">
                <span class="badge-gray">{{ __(':n pending', ['n' => format_number($cron['pending_jobs'])]) }}</span>
                <span class="{{ $cron['failed_jobs'] > 0 ? 'badge-red' : 'badge-green' }}">{{ __(':n failed', ['n' => format_number($cron['failed_jobs'])]) }}</span>
            </span>
        </div>
        <div>
            <p class="label">{{ __('Run the queue worker:') }}</p>
            <div class="flex items-start gap-2">
                <pre class="flex-1 min-w-0 overflow-x-auto rounded-xl bg-slate-100 dark:bg-slate-800 p-3 text-xs"><code>{{ $cron['queue_command'] }}</code></pre>
                <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js($cron['queue_command']))" aria-label="{{ __('Copy') }}">
                    <x-icon name="copy" class="h-4 w-4"/>
                </button>
            </div>
        </div>
        <div>
            <p class="label">{{ __('Supervisor example config:') }}</p>
            <pre class="overflow-x-auto rounded-xl bg-slate-100 dark:bg-slate-800 p-3 text-xs"><code>[program:{{ str_replace(' ', '-', strtolower(site_name())) }}-worker]
command={{ $cron['queue_command'] }}
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile={{ storage_path('logs/worker.log') }}</code></pre>
        </div>
    </div>

    {{-- System info --}}
    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('System') }}</h2>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:max-w-sm">
            <dt class="text-slate-500">{{ __('PHP version') }}</dt><dd class="text-end font-medium">{{ PHP_VERSION }}</dd>
            <dt class="text-slate-500">{{ __('Laravel version') }}</dt><dd class="text-end font-medium">{{ app()->version() }}</dd>
        </dl>
        <div class="flex flex-wrap gap-2 pt-1">
            <a href="{{ url('/update') }}" class="btn-secondary btn-sm"><x-icon name="refresh" class="h-4 w-4"/> {{ __('Run updater') }}</a>
            <a href="{{ route('admin.backup.download') }}" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4"/> {{ __('Download backup') }}</a>
        </div>
    </div>
</div>
@endsection
