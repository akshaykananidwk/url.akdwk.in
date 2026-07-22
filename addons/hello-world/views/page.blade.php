@extends('layouts.app')

@section('title', 'Hello World')
@section('page-title', 'Hello World')

@section('content')
    <div class="card card-pad">
        <h2 class="font-semibold text-lg">{{ __('Hello from an addon!') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __('This page is served by the hello-world addon in the /addons folder. Copy that folder, rename the slug, and build your own feature — routes, views, migrations and hooks are auto-registered.') }}
        </p>
    </div>
@endsection
