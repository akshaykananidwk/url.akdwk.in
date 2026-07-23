@extends('layouts.landing')

@section('title', __('Shorten :name Links', ['name' => $meta['name']]) . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    {{-- Hero --}}
    <div class="max-w-2xl">
        <span class="inline-flex items-center gap-2 badge-brand mb-4">
            <x-icon :name="$meta['icon']" class="h-4 w-4"/> {{ $meta['name'] }}
        </span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Shorten :name links for free', ['name' => $meta['name']]) }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">{{ $meta['desc'] }}</p>
    </div>

    {{-- Shorten form (mirrors the homepage hero) --}}
    @if(setting('guest_shorten_enabled', true))
        <div x-data="guestShortener()" class="mt-6 max-w-2xl">
            <form @submit.prevent="shorten" class="card p-2 flex flex-col sm:flex-row gap-2">
                <input type="url" x-model="url" required inputmode="url" autocomplete="off"
                       placeholder="{{ $meta['example'] }}"
                       class="input flex-1 !border-0 !shadow-none !ring-0 focus:!ring-0 bg-transparent"
                       aria-label="{{ __('Paste a :name link', ['name' => $meta['name']]) }}">
                <button type="submit" class="btn-primary shrink-0" :disabled="loading">
                    <svg x-cloak x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/>
                    </svg>
                    <span x-text="loading ? @js(__('Shortening…')) : @js(__('Shorten'))">{{ __('Shorten') }}</span>
                </button>
            </form>

            <div x-cloak x-show="result" x-transition class="mt-3 card card-pad !p-4 flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                    <x-icon name="check" class="h-5 w-5"/>
                </span>
                <a :href="result" target="_blank" rel="noopener" x-text="result" class="flex-1 min-w-0 truncate text-sm font-semibold text-brand-600 hover:underline"></a>
                <button type="button" class="btn-secondary btn-sm shrink-0" @click="copyResult()">
                    <x-icon name="copy" class="h-4 w-4"/> <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span>
                </button>
            </div>
            <p x-cloak x-show="error" x-text="error" class="mt-3 text-sm font-medium text-rose-600 dark:text-rose-400"></p>
            <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                {{ __('Guest links expire after :days days —', ['days' => setting('guest_link_days', 30)]) }}
                <a href="{{ route('register') }}" class="text-brand-600 hover:underline">{{ __('sign up free to keep them forever') }}</a>
            </p>
        </div>
    @else
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="btn-primary">{{ __('Get started for free') }}</a>
            <a href="{{ route('pricing') }}" class="btn-secondary">{{ __('View pricing') }}</a>
        </div>
    @endif

    {{-- Benefits --}}
    <div class="mt-12 grid sm:grid-cols-3 gap-4">
        @foreach([
            ['icon' => 'sparkles', 'title' => __('Cleaner shares'), 'text' => __('Swap ugly, tracker-filled URLs for a tidy link that looks trustworthy wherever you post it.')],
            ['icon' => 'chart', 'title' => __('Real click stats'), 'text' => __('See how many people actually clicked, from which country and on what device.')],
            ['icon' => 'qr', 'title' => __('Instant QR codes'), 'text' => __('Every short link comes with a scannable QR code, ready for print or slides.')],
        ] as $b)
            <div class="card card-pad">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                    <x-icon :name="$b['icon']" class="h-5 w-5"/>
                </span>
                <h2 class="mt-3 font-semibold text-sm">{{ $b['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $b['text'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- SEO copy --}}
    <div class="mt-12 max-w-2xl space-y-4 text-sm text-slate-500 dark:text-slate-400">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('How to shorten a :name link', ['name' => $meta['name']]) }}</h2>
        <ol class="list-decimal ms-5 space-y-1.5">
            <li>{{ __('Copy the full :name URL you want to share.', ['name' => $meta['name']]) }}</li>
            <li>{{ __('Paste it into the box above and press Shorten.') }}</li>
            <li>{{ __('Copy your new short link — or grab its QR code — and share it anywhere.') }}</li>
        </ol>
        <p>{{ __(':name URLs are often long and full of tracking parameters, which makes them hard to read and easy to mistype. A short link is friendlier to share in a bio, a caption, a message or on a printed flyer, and it lets you measure engagement you would otherwise never see.', ['name' => $meta['name']]) }}</p>
    </div>

    {{-- Internal links --}}
    <div class="mt-12">
        <h2 class="text-lg font-semibold">{{ __('Shorten links for other platforms') }}</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach($services as $slug => $s)
                @continue($slug === $service)
                <a href="{{ route('ftools.programmatic', $slug) }}" class="rounded-full ring-1 ring-slate-200 dark:ring-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:ring-brand-400">
                    {{ $s['name'] }}
                </a>
            @endforeach
        </div>
        <p class="mt-4 text-sm">
            <a href="{{ route('ftools.hub') }}" class="text-brand-600 font-medium hover:underline">{{ __('Browse all free tools →') }}</a>
        </p>
    </div>

    {{-- CTA --}}
    <div class="mt-10 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Do more with your :name links', ['name' => $meta['name']]) }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Custom aliases, branded domains, deep analytics and QR styling — all on a generous free plan.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Create free account') }}</a>
    </div>

</div>

<script>
    function guestShortener() {
        return {
            url: '', loading: false, result: '', error: '', copied: false,
            async shorten() {
                this.loading = true; this.error = ''; this.result = '';
                try {
                    const res = await fetch(@json(route('guest.shorten')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ destination: this.url }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.short_url) {
                        this.result = data.short_url;
                    } else {
                        this.error = data.message || @json(__('That URL could not be shortened. Please check it and try again.'));
                    }
                } catch (e) {
                    this.error = @json(__('Network error — please try again.'));
                } finally {
                    this.loading = false;
                }
            },
            async copyResult() {
                try { await navigator.clipboard.writeText(this.result); this.copied = true; setTimeout(() => this.copied = false, 1500); } catch (e) {}
            },
        };
    }
</script>
@endsection
