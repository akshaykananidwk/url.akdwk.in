@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag)

@extends('layouts.base')

@section('title', __('Session expired') . ' — ' . site_name())

@section('body')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="text-center max-w-sm">
        <div class="text-7xl sm:text-8xl font-extrabold tracking-tight text-amber-500">419</div>
        <h1 class="mt-3 text-xl font-bold tracking-tight">{{ __('Your session expired') }}</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __('For your security the page timed out before the form was submitted. Please go back, reload the page, and try again — your changes were not lost if you reopen the form.') }}
        </p>
        <button onclick="history.back()" class="btn-primary mt-6">{{ __('Go back and retry') }}</button>
    </div>
</div>
@endsection
