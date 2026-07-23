@extends('layouts.landing')

@section('title', 'Status — ' . site_name())

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">

    {{-- Header --}}
    <div class="text-center max-w-2xl mx-auto">
        <span class="inline-flex items-center gap-1.5 badge-amber">
            <x-icon name="chart" class="h-4 w-4"/> {{ __('System status') }}
        </span>
        <h1 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight">{{ site_name() }} {{ __('Status') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">
            {{ __('Live health of the services that power your short links.') }}
        </p>
    </div>

    {{-- Overall banner --}}
    <div @class([
        'mt-8 flex items-center gap-3 rounded-xl px-5 py-4',
        'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-900/25 dark:text-emerald-200 dark:ring-emerald-800' => $allUp,
        'bg-red-50 text-red-800 ring-1 ring-red-200 dark:bg-red-900/25 dark:text-red-200 dark:ring-red-800' => ! $allUp,
    ])>
        <span @class([
            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-800/60 dark:text-emerald-200' => $allUp,
            'bg-red-100 text-red-700 dark:bg-red-800/60 dark:text-red-200' => ! $allUp,
        ])>
            <x-icon :name="$allUp ? 'check' : 'warning'" class="h-5 w-5"/>
        </span>
        <div>
            <p class="text-base font-semibold">
                {{ $allUp ? __('All systems operational') : __('Some systems are experiencing issues') }}
            </p>
            <p class="text-sm opacity-80">
                {{ __('Last checked') }} {{ $checkedAt->toDayDateTimeString() }} UTC
            </p>
        </div>
    </div>

    {{-- Component list --}}
    <div class="card card-pad mt-6">
        <h2 class="text-lg font-semibold mb-1">{{ __('Components') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            {{ __('Each check runs in real time when this page loads.') }}
        </p>

        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($components as $component)
                @php($up = $component['status'] === 'up')
                <li class="flex items-center gap-3 py-4">
                    <span @class([
                        'h-2.5 w-2.5 shrink-0 rounded-full',
                        'bg-emerald-500' => $up,
                        'bg-red-500' => ! $up,
                    ])></span>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ $component['name'] }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 truncate">
                            {{ $component['detail'] }}
                            @if(! is_null($component['latency_ms']))
                                <span class="text-slate-400 dark:text-slate-500">· {{ format_number($component['latency_ms']) }} ms</span>
                            @endif
                        </p>
                    </div>

                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $up,
                        'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => ! $up,
                    ])>
                        <x-icon :name="$up ? 'check' : 'warning'" class="h-3.5 w-3.5"/>
                        {{ $up ? __('Operational') : __('Down') }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Meta --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="card card-pad flex items-center gap-3">
            <x-icon name="clock" class="h-5 w-5 text-brand-600"/>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Server time') }}</p>
                <p class="font-medium">{{ $checkedAt->format('H:i:s') }} UTC</p>
            </div>
        </div>
        <div class="card card-pad flex items-center gap-3">
            <x-icon name="bolt" class="h-5 w-5 text-brand-600"/>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('App version') }}</p>
                <p class="font-medium">v{{ $version }}</p>
            </div>
        </div>
    </div>

    {{-- Subscribe to updates --}}
    <div class="mt-6 card card-pad text-center">
        <div class="flex justify-center">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-900/30">
                <x-icon name="megaphone" class="h-5 w-5"/>
            </span>
        </div>
        <h3 class="mt-3 font-semibold">{{ __('Subscribe to updates') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
            {{ __('Want a heads-up during planned maintenance or incidents? Reach out and we will keep you posted.') }}
        </p>
        <a href="{{ route('contact') }}" class="btn-secondary mt-4 inline-flex items-center gap-2">
            <x-icon name="mail" class="h-4 w-4"/> {{ __('Contact us') }}
        </a>
    </div>

</div>
@endsection
