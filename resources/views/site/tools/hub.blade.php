@extends('layouts.landing')

@section('title', __('Free Online Tools') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    <div class="text-center max-w-2xl mx-auto">
        <span class="badge-brand mb-4">{{ __('100% free — no sign-up') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Free tools for links & QR codes') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('A handy toolbox for marketers, creators and developers — generate QR codes, build UTM links, shorten in bulk, expand short URLs, preview social cards and more. Everything runs right here, no account required.') }}
        </p>
    </div>

    @php
        $tools = [
            ['route' => 'ftools.qr', 'icon' => 'qr', 'title' => __('QR Code Generator'), 'desc' => __('Create a downloadable QR code for any link or text.')],
            ['route' => 'ftools.utm', 'icon' => 'target', 'title' => __('UTM Builder'), 'desc' => __('Add campaign tracking parameters to any URL.')],
            ['route' => 'ftools.bulk', 'icon' => 'link', 'title' => __('Bulk URL Shortener'), 'desc' => __('Shorten up to 20 links at once in one go.')],
            ['route' => 'ftools.scanner', 'icon' => 'search', 'title' => __('QR Code Scanner'), 'desc' => __('Scan a QR code with your device camera.')],
            ['route' => 'ftools.password', 'icon' => 'lock', 'title' => __('Password Generator'), 'desc' => __('Generate strong, random passwords instantly.')],
            ['route' => 'ftools.expander', 'icon' => 'external', 'title' => __('Link Expander'), 'desc' => __('Reveal where a short link really goes.')],
            ['route' => 'ftools.og', 'icon' => 'share', 'title' => __('Social Preview'), 'desc' => __('See how a link looks when shared online.')],
            ['route' => 'ftools.vcard', 'icon' => 'card', 'title' => __('Digital Business Card'), 'desc' => __('Build a vCard QR code people can save in one tap.')],
        ];
    @endphp

    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($tools as $t)
            <a href="{{ route($t['route']) }}" class="card card-pad group hover:ring-2 hover:ring-brand-500 transition">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                    <x-icon :name="$t['icon']" class="h-5 w-5"/>
                </span>
                <h2 class="mt-3 font-semibold flex items-center gap-1">
                    {{ $t['title'] }}
                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-300 group-hover:text-brand-500 transition"/>
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $t['desc'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Shorten by platform (programmatic pages) --}}
    <div class="mt-12">
        <h2 class="text-lg font-semibold">{{ __('Shorten links by platform') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Tailored guides for the places you share most.') }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach(['youtube' => 'YouTube', 'amazon' => 'Amazon', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn', 'whatsapp' => 'WhatsApp', 'spotify' => 'Spotify', 'github' => 'GitHub'] as $slug => $label)
                <a href="{{ route('ftools.programmatic', $slug) }}" class="rounded-full ring-1 ring-slate-200 dark:ring-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:ring-brand-400">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Soft CTA --}}
    <div class="mt-12 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-xl font-bold">{{ __('Want more than one-off tools?') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">
            {{ __('Create a free account to save your links forever, track clicks in real time, brand your QR codes and manage everything from one dashboard.') }}
        </p>
        <a href="{{ route('register') }}" class="btn-primary mt-5">{{ __('Get started free') }}</a>
    </div>

</div>
@endsection
