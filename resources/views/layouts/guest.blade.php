@extends('layouts.base')

@section('body')
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
    <a href="{{ url('/') }}" class="flex items-center gap-2.5 mb-8">
        @if(setting('site_logo'))
            <img src="{{ storage_url(setting('site_logo')) }}" alt="{{ site_name() }}" class="h-10 w-auto">
        @else
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-600 text-white">
                <x-icon name="link" class="h-6 w-6"/>
            </span>
            <span class="text-2xl font-bold tracking-tight">{{ site_name() }}</span>
        @endif
    </a>

    <div class="w-full max-w-md card card-pad">
        @yield('content')
    </div>

    <div class="mt-6 flex items-center gap-3">
        @include('partials.lang-switcher')
        <button onclick="toggleTheme()" class="btn-ghost btn-sm" aria-label="{{ __('Toggle dark mode') }}">
            <x-icon name="moon" class="h-5 w-5 dark:hidden"/>
            <x-icon name="sun" class="h-5 w-5 hidden dark:block"/>
        </button>
    </div>
</div>
@endsection
