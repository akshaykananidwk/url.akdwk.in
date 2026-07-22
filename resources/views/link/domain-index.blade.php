@extends('layouts.base')

@section('title', $domain->domain)

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-sm card card-pad text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon name="globe" class="h-7 w-7"/>
        </span>
        <h1 class="mt-4 text-lg font-bold tracking-tight">{{ $domain->domain }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('This domain is used for short links.') }}</p>
        <p class="mt-6 text-xs text-slate-400">
            {{ __('Powered by') }}
            <a href="{{ config('app.url') }}" class="text-brand-600 font-medium hover:underline">{{ site_name() }}</a>
        </p>
    </div>
</div>
@endsection
