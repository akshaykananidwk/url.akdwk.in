@extends('layouts.landing')

@section('title', __('Contact us') . ' — ' . site_name())

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="text-center mb-8">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon name="mail" class="h-7 w-7"/>
        </span>
        <h1 class="text-3xl font-extrabold tracking-tight">{{ __('Contact us') }}</h1>
        <p class="mt-2 text-slate-500 dark:text-slate-400">{{ __('Questions, feedback or partnership ideas? We usually reply within one business day.') }}</p>
    </div>

    @if(session('status'))
        <div class="mb-5 card !ring-emerald-200 dark:!ring-emerald-900 card-pad !p-4 flex items-center gap-3 text-sm text-emerald-700 dark:text-emerald-300">
            <x-icon name="check" class="h-5 w-5 shrink-0"/>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('contact') }}" class="card card-pad space-y-4">
        @csrf
        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" class="hidden" aria-hidden="true">

        <x-field name="name" :label="__('Your name')">
            <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="100" class="input" autocomplete="name">
        </x-field>

        <x-field name="email" :label="__('Email address')">
            <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="190" class="input" autocomplete="email">
        </x-field>

        <x-field name="message" :label="__('Message')">
            <textarea id="message" name="message" rows="6" required maxlength="5000" class="input" placeholder="{{ __('How can we help?') }}">{{ old('message') }}</textarea>
        </x-field>

        @include('partials.captcha')

        <button type="submit" class="btn-primary w-full">{{ __('Send message') }}</button>
    </form>
</div>
@endsection
