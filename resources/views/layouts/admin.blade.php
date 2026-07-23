@extends('layouts.base')

@php
    $adminNav = [
        ['route' => 'admin.dashboard', 'icon' => 'home', 'label' => __('Dashboard'), 'match' => 'admin.dashboard'],
        ['route' => 'admin.users.index', 'icon' => 'users', 'label' => __('Users'), 'match' => 'admin.users.*'],
        ['route' => 'admin.links.index', 'icon' => 'link', 'label' => __('Links'), 'match' => 'admin.links.*'],
        ['route' => 'admin.payments.index', 'icon' => 'card', 'label' => __('Payments'), 'match' => 'admin.payments.*'],
        ['route' => 'admin.plans.index', 'icon' => 'sparkles', 'label' => __('Plans'), 'match' => 'admin.plans.*'],
        ['route' => 'admin.coupons.index', 'icon' => 'gift', 'label' => __('Coupons'), 'match' => 'admin.coupons.*'],
        ['route' => 'admin.taxes.index', 'icon' => 'doc', 'label' => __('Tax rates'), 'match' => 'admin.taxes.*'],
        ['route' => 'admin.payouts.index', 'icon' => 'download', 'label' => __('Payouts'), 'match' => 'admin.payouts.*'],
        ['route' => 'admin.spaces.index', 'icon' => 'folder', 'label' => __('Spaces'), 'match' => 'admin.spaces.*'],
        ['route' => 'admin.domains.index', 'icon' => 'globe', 'label' => __('Domains'), 'match' => 'admin.domains.*'],
        ['route' => 'admin.pixels.index', 'icon' => 'target', 'label' => __('Pixels'), 'match' => 'admin.pixels.*'],
        ['route' => 'admin.qr.index', 'icon' => 'qr', 'label' => __('QR codes'), 'match' => 'admin.qr.*'],
        ['route' => 'admin.bio.index', 'icon' => 'user', 'label' => __('Bio pages'), 'match' => 'admin.bio.*'],
        ['route' => 'admin.abuse.index', 'icon' => 'warning', 'label' => __('Abuse reports'), 'match' => 'admin.abuse.*'],
        ['route' => 'admin.blocklist', 'icon' => 'shield', 'label' => __('Blocklist'), 'match' => 'admin.blocklist*'],
        ['route' => 'admin.content.pages', 'icon' => 'doc', 'label' => __('Content'), 'match' => 'admin.content.*'],
        ['route' => 'admin.languages.index', 'icon' => 'globe', 'label' => __('Languages'), 'match' => 'admin.languages.*'],
        ['route' => 'admin.reports.index', 'icon' => 'chart', 'label' => __('Reports'), 'match' => 'admin.reports.*'],
        ['route' => 'admin.audit.index', 'icon' => 'eye', 'label' => __('Audit log'), 'match' => 'admin.audit.*'],
        ['route' => 'admin.addons.index', 'icon' => 'bolt', 'label' => __('Addons'), 'match' => 'admin.addons.*'],
        ['route' => 'admin.sso.index', 'icon' => 'lock', 'label' => __('SSO'), 'match' => 'admin.sso.*'],
        ['route' => 'admin.waitlist', 'icon' => 'mail', 'label' => __('Waitlist'), 'match' => 'admin.waitlist*'],
        ['route' => 'admin.updates', 'icon' => 'refresh', 'label' => __('Update'), 'match' => 'admin.updates*'],
        ['route' => 'admin.settings', 'icon' => 'settings', 'label' => __('Settings'), 'match' => 'admin.settings*'],
    ];
    $adminNav = hook_filter('admin_menu', $adminNav);
@endphp

@section('body-class', 'pb-16 lg:pb-0')

@section('body')
<div x-data="{ sidebar: false }" class="min-h-screen lg:flex">

    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 sticky top-0 h-screen overflow-y-auto">
        @include('partials.admin-sidebar', ['adminNav' => $adminNav])
    </aside>

    <div x-cloak x-show="sidebar" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div x-show="sidebar" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="sidebar = false"></div>
        <aside x-show="sidebar" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full rtl:translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full rtl:translate-x-full"
               class="absolute inset-y-0 start-0 w-72 max-w-[85%] bg-white dark:bg-slate-900 overflow-y-auto shadow-xl">
            @include('partials.admin-sidebar', ['adminNav' => $adminNav])
        </aside>
    </div>

    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2 px-4 sm:px-6 h-14">
                <button class="lg:hidden btn-ghost btn-sm -ms-2" @click="sidebar = true" aria-label="{{ __('Open menu') }}">
                    <x-icon name="menu" class="h-6 w-6"/>
                </button>
                <h1 class="text-base sm:text-lg font-semibold truncate">@yield('page-title', __('Admin'))</h1>
                <div class="ms-auto flex items-center gap-1.5">
                    <a href="{{ route('dashboard') }}" class="btn-secondary btn-sm hidden sm:inline-flex">{{ __('User panel') }}</a>
                    <button onclick="toggleTheme()" class="btn-ghost btn-sm" aria-label="{{ __('Toggle dark mode') }}">
                        <x-icon name="moon" class="h-5 w-5 dark:hidden"/>
                        <x-icon name="sun" class="h-5 w-5 hidden dark:block"/>
                    </button>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="btn-ghost btn-sm" aria-label="{{ __('Log out') }}"><x-icon name="logout" class="h-5 w-5"/></button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 py-5 sm:py-6 w-full max-w-7xl mx-auto">
            @yield('content')
        </main>
    </div>

    {{-- Mobile bottom navigation (admin) --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-800 flex pb-[env(safe-area-inset-bottom)]">
        <a href="{{ route('admin.dashboard') }}" class="bottomnav-item {{ request()->routeIs('admin.dashboard') ? 'bottomnav-active' : '' }}">
            <x-icon name="home" class="h-6 w-6"/><span>{{ __('Home') }}</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="bottomnav-item {{ request()->routeIs('admin.users.*') ? 'bottomnav-active' : '' }}">
            <x-icon name="users" class="h-6 w-6"/><span>{{ __('Users') }}</span>
        </a>
        <a href="{{ route('admin.payments.index') }}" class="bottomnav-item {{ request()->routeIs('admin.payments.*') ? 'bottomnav-active' : '' }}">
            <x-icon name="card" class="h-6 w-6"/><span>{{ __('Payments') }}</span>
        </a>
        <a href="{{ route('admin.settings') }}" class="bottomnav-item {{ request()->routeIs('admin.settings*') ? 'bottomnav-active' : '' }}">
            <x-icon name="settings" class="h-6 w-6"/><span>{{ __('Settings') }}</span>
        </a>
        <button @click="sidebar = true" class="bottomnav-item"><x-icon name="menu" class="h-6 w-6"/><span>{{ __('More') }}</span></button>
    </nav>
</div>
@endsection
