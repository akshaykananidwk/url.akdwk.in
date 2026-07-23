@extends('layouts.base')

@section('body')
<div x-data="{ mobileNav: false }">
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-950/80 backdrop-blur border-b border-slate-200/70 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center h-16 gap-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                @if(setting('site_logo'))
                    <img src="{{ storage_url(setting('site_logo')) }}" alt="{{ site_name() }}" class="h-8 w-auto">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <x-icon name="link" class="h-5 w-5"/>
                    </span>
                    <span class="text-lg font-bold tracking-tight">{{ site_name() }}</span>
                @endif
            </a>
            <nav class="hidden md:flex items-center gap-1 ms-6" aria-label="{{ __('Primary') }}">
                <a href="{{ url('/#features') }}" class="nav-item">{{ __('Features') }}</a>
                <a href="{{ route('pricing') }}" class="nav-item">{{ __('Pricing') }}</a>
                <a href="{{ route('blog') }}" class="nav-item">{{ __('Blog') }}</a>
                <a href="{{ route('contact') }}" class="nav-item">{{ __('Contact') }}</a>
            </nav>
            <div class="ms-auto flex items-center gap-1.5">
                @include('partials.lang-switcher')
                <button onclick="toggleTheme()" class="btn-ghost btn-sm" aria-label="{{ __('Toggle dark mode') }}">
                    <x-icon name="moon" class="h-5 w-5 dark:hidden"/>
                    <x-icon name="sun" class="h-5 w-5 hidden dark:block"/>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary btn-sm">{{ __('Dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex">{{ __('Log in') }}</a>
                    @if(setting('registration_enabled', true))
                        <a href="{{ route('register') }}" class="btn-primary btn-sm">{{ __('Sign up free') }}</a>
                    @endif
                @endauth
                <button class="md:hidden btn-ghost btn-sm" @click="mobileNav = !mobileNav" aria-label="{{ __('Open menu') }}">
                    <x-icon name="menu" class="h-6 w-6"/>
                </button>
            </div>
        </div>
        <nav x-cloak x-show="mobileNav" x-transition class="md:hidden border-t border-slate-200 dark:border-slate-800 px-4 py-3 space-y-0.5 bg-white dark:bg-slate-950">
            <a href="{{ url('/#features') }}" class="nav-item" @click="mobileNav = false">{{ __('Features') }}</a>
            <a href="{{ route('pricing') }}" class="nav-item">{{ __('Pricing') }}</a>
            <a href="{{ route('blog') }}" class="nav-item">{{ __('Blog') }}</a>
            <a href="{{ route('contact') }}" class="nav-item">{{ __('Contact') }}</a>
            @guest
                <a href="{{ route('login') }}" class="nav-item">{{ __('Log in') }}</a>
            @endguest
        </nav>
    </header>

    @if(setting('announcement_enabled') && setting('announcement_text'))
        <div class="bg-brand-600 text-white text-sm text-center px-4 py-2">{{ setting('announcement_text') }}</div>
    @endif

    <main>@yield('content')</main>

    <footer class="border-t border-slate-200 dark:border-slate-800 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div>
                <div class="flex items-center gap-2 font-bold text-base mb-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white"><x-icon name="link" class="h-4 w-4"/></span>
                    {{ site_name() }}
                </div>
                <p class="text-slate-500 dark:text-slate-400">{{ setting('tagline', __('Short links, QR codes, bio pages & analytics — all in one place.')) }}</p>
            </div>
            <div>
                <h3 class="font-semibold mb-3">{{ __('Product') }}</h3>
                <ul class="space-y-2 text-slate-500 dark:text-slate-400">
                    <li><a class="hover:text-brand-600" href="{{ url('/#features') }}">{{ __('Features') }}</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('pricing') }}">{{ __('Pricing') }}</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('register') }}">{{ __('Sign up') }}</a></li>
                    <li><a class="hover:text-brand-600" href="{{ route('blog') }}">{{ __('Blog') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-3">{{ __('Legal') }}</h3>
                <ul class="space-y-2 text-slate-500 dark:text-slate-400">
                    @foreach(\App\Models\Page::where('active', true)->where('show_in_footer', true)->get() as $footerPage)
                        <li><a class="hover:text-brand-600" href="{{ route('page', $footerPage->slug) }}">{{ $footerPage->title }}</a></li>
                    @endforeach
                    <li><a class="hover:text-brand-600" href="{{ route('report') }}">{{ __('Report abuse') }}</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-3">{{ __('Support') }}</h3>
                <ul class="space-y-2 text-slate-500 dark:text-slate-400">
                    <li><a class="hover:text-brand-600" href="{{ route('contact') }}">{{ __('Contact us') }}</a></li>
                    <li><a class="hover:text-brand-600" href="{{ url('/login') }}">{{ __('Log in') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-200 dark:border-slate-800 py-5 text-center text-xs text-slate-400">
            © {{ date('Y') }} {{ site_name() }}. {{ __('All rights reserved.') }}
            {{-- === UPDATE TEST MARKER (safe to remove) === --}}
            <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 px-2 py-0.5 font-medium">
                🟢 {{ __('Update test OK') }} · build 2026.07.22-1
            </span>
        </div>
    </footer>

    {{-- Cookie consent banner --}}
    @if(setting('cookie_consent_enabled', true))
        <div x-data="{ show: !localStorage.getItem('cookie_ok') }" x-cloak x-show="show"
             class="fixed bottom-0 inset-x-0 z-50 p-4">
            <div class="max-w-3xl mx-auto card card-pad !p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3 shadow-lg">
                <p class="text-sm text-slate-600 dark:text-slate-300 flex-1">
                    {{ setting('cookie_consent_text', __('We use cookies to make this site work and to analyze traffic. By using the site you agree to our privacy policy.')) }}
                </p>
                <button class="btn-primary btn-sm shrink-0" @click="localStorage.setItem('cookie_ok', '1'); show = false">{{ __('Got it') }}</button>
            </div>
        </div>
    @endif
</div>
@endsection
