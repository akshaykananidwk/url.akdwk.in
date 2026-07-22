@extends('layouts.base')

@php
    $navItems = [
        ['route' => 'dashboard', 'icon' => 'home', 'label' => __('Dashboard'), 'match' => 'dashboard'],
        ['route' => 'links.index', 'icon' => 'link', 'label' => __('Links'), 'match' => 'links*'],
        ['route' => 'stats.global', 'icon' => 'chart', 'label' => __('Statistics'), 'match' => 'stats*'],
        ['route' => 'qr.index', 'icon' => 'qr', 'label' => __('QR Codes'), 'match' => 'qr*'],
        ['route' => 'bio.index', 'icon' => 'user', 'label' => __('Bio Pages'), 'match' => 'bio*'],
        ['route' => 'spaces.index', 'icon' => 'folder', 'label' => __('Spaces'), 'match' => 'spaces*'],
        ['route' => 'domains.index', 'icon' => 'globe', 'label' => __('Domains'), 'match' => 'domains*'],
        ['route' => 'pixels.index', 'icon' => 'target', 'label' => __('Pixels'), 'match' => 'pixels*'],
        ['route' => 'tools.index', 'icon' => 'wrench', 'label' => __('Tools'), 'match' => 'tools*'],
        ['route' => 'team.index', 'icon' => 'users', 'label' => __('Team'), 'match' => 'team*'],
        ['route' => 'developers.index', 'icon' => 'code', 'label' => __('Developers'), 'match' => 'developers*'],
        ['route' => 'affiliate.index', 'icon' => 'gift', 'label' => __('Affiliate'), 'match' => 'affiliate*'],
        ['route' => 'billing.plans', 'icon' => 'card', 'label' => __('Billing'), 'match' => 'billing*'],
        ['route' => 'account.index', 'icon' => 'settings', 'label' => __('Account'), 'match' => 'account*'],
    ];
    $navItems = hook_filter('user_menu', $navItems);
    // Bottom nav shows the 4 most important destinations + "More" sheet.
    $bottomNav = [
        $navItems[0], $navItems[1], $navItems[2], $navItems[3],
    ];
@endphp

@section('body-class', 'pb-16 lg:pb-0')

@section('body')
<div x-data="{ sidebar: false, more: false }" class="min-h-screen lg:flex">

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 sticky top-0 h-screen overflow-y-auto">
        @include('partials.sidebar-content', ['navItems' => $navItems])
    </aside>

    {{-- Mobile slide-over sidebar (full menu) --}}
    <div x-cloak x-show="sidebar" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div x-show="sidebar" x-transition.opacity class="absolute inset-0 bg-slate-900/60" @click="sidebar = false"></div>
        <aside x-show="sidebar" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full rtl:translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full rtl:translate-x-full"
               class="absolute inset-y-0 start-0 w-72 max-w-[85%] bg-white dark:bg-slate-900 overflow-y-auto shadow-xl">
            @include('partials.sidebar-content', ['navItems' => $navItems])
        </aside>
    </div>

    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        {{-- Top bar --}}
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2 px-4 sm:px-6 h-14">
                <button class="lg:hidden btn-ghost btn-sm -ms-2" @click="sidebar = true" aria-label="{{ __('Open menu') }}">
                    <x-icon name="menu" class="h-6 w-6"/>
                </button>
                <h1 class="text-base sm:text-lg font-semibold truncate">@yield('page-title', site_name())</h1>
                <div class="ms-auto flex items-center gap-1.5">
                    @if(session('impersonator'))
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="badge-amber min-h-touch px-3" type="submit">{{ __('Exit impersonation') }}</button>
                        </form>
                    @endif
                    <a href="{{ route('links.create') }}" class="btn-primary btn-sm hidden sm:inline-flex">
                        <x-icon name="plus" class="h-4 w-4"/> {{ __('New link') }}
                    </a>
                    @include('partials.lang-switcher')
                    <button onclick="toggleTheme()" class="btn-ghost btn-sm" aria-label="{{ __('Toggle dark mode') }}">
                        <x-icon name="moon" class="h-5 w-5 dark:hidden"/>
                        <x-icon name="sun" class="h-5 w-5 hidden dark:block"/>
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center min-h-touch min-w-touch justify-center" aria-label="{{ __('Account menu') }}">
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700">
                        </button>
                        <div x-cloak x-show="open" @click.outside="open = false" x-transition
                             class="absolute end-0 mt-2 w-52 card p-1.5 z-50">
                            <div class="px-3 py-2 text-sm">
                                <div class="font-semibold truncate">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <a href="{{ route('account.index') }}" class="nav-item">{{ __('Account settings') }}</a>
                            <a href="{{ route('billing.invoices') }}" class="nav-item">{{ __('Billing history') }}</a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="nav-item">{{ __('Admin panel') }}</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <button type="submit" class="nav-item w-full text-start">{{ __('Log out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @if(setting('announcement_enabled') && setting('announcement_text'))
            <div class="bg-brand-600 text-white text-sm text-center px-4 py-2">{{ setting('announcement_text') }}</div>
        @endif

        <main class="flex-1 px-4 sm:px-6 py-5 sm:py-6 w-full max-w-7xl mx-auto">
            @yield('content')
        </main>
    </div>

    {{-- Mobile bottom navigation --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-800 flex pb-[env(safe-area-inset-bottom)]"
         aria-label="{{ __('Main navigation') }}">
        @foreach($bottomNav as $item)
            <a href="{{ route($item['route']) }}"
               class="bottomnav-item {{ request()->routeIs($item['match']) ? 'bottomnav-active' : '' }}">
                <x-icon :name="$item['icon']" class="h-6 w-6"/>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <button @click="sidebar = true" class="bottomnav-item" aria-label="{{ __('More') }}">
            <x-icon name="menu" class="h-6 w-6"/>
            <span>{{ __('More') }}</span>
        </button>
    </nav>
</div>
@endsection
