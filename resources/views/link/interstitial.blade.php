@extends('layouts.base')

@section('title', __('Redirecting…') . ' — ' . site_name())

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('body')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10"
     x-data="{
        n: {{ (int) $seconds }},
        dest: @js($destination),
        timer: null,
        start() {
            this.timer = setInterval(() => {
                if (--this.n <= 0) {
                    clearInterval(this.timer);
                    this.n = 0;
                    window.location = this.dest;
                }
            }, 1000);
        }
     }"
     x-init="start()">

    <div class="w-full max-w-md card card-pad text-center">
        {{-- Countdown circle --}}
        <div class="relative mx-auto h-20 w-20">
            <svg class="h-20 w-20 -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
                <circle cx="40" cy="40" r="34" fill="none" stroke-width="6" class="stroke-slate-200 dark:stroke-slate-700"/>
                <circle cx="40" cy="40" r="34" fill="none" stroke-width="6" stroke-linecap="round"
                        class="stroke-brand-500 transition-all duration-1000 ease-linear"
                        stroke-dasharray="213.6"
                        :stroke-dashoffset="213.6 * (1 - n / {{ max(1, (int) $seconds) }})"/>
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-2xl font-extrabold" x-text="n"></span>
        </div>

        <h1 class="mt-4 text-lg font-bold tracking-tight" x-text="n > 0 ? @js(__('Your link is almost ready…')) : @js(__('Redirecting…'))">{{ __('Your link is almost ready…') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __("You'll be redirected automatically.") }}</p>

        <button type="button" class="btn-primary mt-5 w-full" :disabled="n > 0" @click="window.location = dest">
            <span x-text="n > 0 ? @js(__('Please wait…')) : @js(__('Continue'))">{{ __('Please wait…') }}</span>
        </button>
    </div>

    @if(trim((string) $ad_code) !== '')
        <div class="mt-6 w-full max-w-md overflow-hidden">
            {!! $ad_code !!}
        </div>
    @endif

    <p class="mt-6 text-xs text-slate-400">
        {{ __('Powered by') }}
        <a href="{{ url('/') }}" class="text-brand-600 font-medium hover:underline">{{ site_name() }}</a>
    </p>
</div>
@endsection
