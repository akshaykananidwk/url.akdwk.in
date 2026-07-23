@extends('layouts.landing')

@section('title', __('Free QR Code Generator') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Free QR Code Generator') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Type any link or text and get a crisp, scannable QR code in a second. Download it as an SVG — perfect for print, packaging, posters or slides — at any size without losing quality.') }}
        </p>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6 items-start">
        {{-- Generator form --}}
        <div class="card card-pad">
            <form method="GET" action="{{ route('ftools.qr') }}" class="space-y-4">
                <div>
                    <label for="qr-data" class="block text-sm font-medium mb-1.5">{{ __('Link or text') }}</label>
                    <input id="qr-data" type="text" name="data" value="{{ $data }}" maxlength="2000" required
                           placeholder="{{ __('https://example.com') }}"
                           class="input w-full" aria-label="{{ __('Link or text to encode') }}">
                </div>
                <button type="submit" class="btn-primary w-full">
                    <x-icon name="qr" class="h-4 w-4"/> {{ __('Generate QR code') }}
                </button>
            </form>
        </div>

        {{-- Result --}}
        <div class="card card-pad flex flex-col items-center justify-center text-center min-h-[18rem]">
            @if($svg)
                <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200 max-w-[260px]">
                    {!! $svg !!}
                </div>
                <a href="data:image/svg+xml;base64,{{ base64_encode($svg) }}" download="qr-code.svg" class="btn-secondary mt-4">
                    <x-icon name="download" class="h-4 w-4"/> {{ __('Download SVG') }}
                </a>
                <p class="mt-2 text-xs text-slate-400 break-all max-w-xs">{{ $data }}</p>
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <x-icon name="qr" class="h-8 w-8"/>
                </span>
                <p class="mt-3 text-sm text-slate-400">{{ __('Your QR code will appear here.') }}</p>
            @endif
        </div>
    </div>

    {{-- SEO copy --}}
    <div class="mt-12 max-w-2xl space-y-4 text-sm text-slate-500 dark:text-slate-400">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('How does a QR code work?') }}</h2>
        <p>{{ __('A QR (Quick Response) code stores a link or short piece of text as a pattern of squares. When someone points a phone camera at it, their device reads the pattern and opens the link instantly — no typing required. It is the fastest way to bridge the gap between the physical world and your web pages.') }}</p>
        <p>{{ __('This generator produces vector SVG codes, so they stay razor-sharp whether you print them on a business card or a billboard. The QR code never expires and there is no watermark.') }}</p>
    </div>

    <div class="mt-10 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Need branded, trackable QR codes?') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Add your logo and colors, download PNG or PDF, and see exactly how many times each code is scanned — with a free account.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Create free account') }}</a>
    </div>

</div>
@endsection
