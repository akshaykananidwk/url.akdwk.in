@extends('layouts.landing')

@section('title', site_name() . ' — ' . setting('tagline', __('Short links, QR codes & analytics')))

@section('content')

{{-- ============================================================ HERO --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-brand-50 via-white to-white dark:from-brand-950/40 dark:via-slate-950 dark:to-slate-950"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-14 sm:pt-20 pb-12 sm:pb-16 text-center">
        <span class="badge-brand mb-4">{{ __('Free forever plan available') }}</span>
        <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
            {{ __('Short links with superpowers') }}
        </h1>
        <p class="mt-4 text-base sm:text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">
            {{ __('Shorten, brand, target and measure every link you share — with QR codes, bio pages and real-time analytics built in.') }}
        </p>

        @if(setting('guest_shorten_enabled', true))
            {{-- Instant shorten box --}}
            <div x-data="guestShortener()" class="mt-8 max-w-2xl mx-auto text-start">
                <form @submit.prevent="shorten" class="card p-2 flex flex-col sm:flex-row gap-2">
                    <input type="url" x-model="url" required inputmode="url" autocomplete="off"
                           placeholder="{{ __('Paste your long URL here…') }}"
                           class="input flex-1 !border-0 !shadow-none !ring-0 focus:!ring-0 bg-transparent"
                           aria-label="{{ __('URL to shorten') }}">
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
                    <button type="button" class="btn-secondary btn-sm shrink-0" @click="copyText(result)">
                        <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy') }}
                    </button>
                </div>
                <p x-cloak x-show="error" x-text="error" class="mt-3 text-sm font-medium text-rose-600 dark:text-rose-400"></p>

                <p class="mt-3 text-xs text-center text-slate-400 dark:text-slate-500">
                    {{ __('Guest links expire after :days days —', ['days' => setting('guest_link_days', 30)]) }}
                    <a href="{{ route('register') }}" class="text-brand-600 hover:underline">{{ __('sign up free to keep them forever') }}</a>
                </p>
            </div>

            <script>
                function guestShortener() {
                    return {
                        url: '', loading: false, result: '', error: '',
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
                    };
                }
            </script>
        @else
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-primary">{{ __('Get started for free') }}</a>
                <a href="{{ route('pricing') }}" class="btn-secondary">{{ __('View pricing') }}</a>
            </div>
        @endif
    </div>

    {{-- Stats preview strip --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 pb-14">
        <div class="grid grid-cols-3 gap-3 sm:gap-6">
            <div class="card card-pad text-center">
                <div class="text-2xl sm:text-3xl font-extrabold text-brand-600 dark:text-brand-400">{{ format_number($totalLinks) }}</div>
                <div class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">{{ __('Links created') }}</div>
            </div>
            <div class="card card-pad text-center">
                <div class="text-2xl sm:text-3xl font-extrabold text-brand-600 dark:text-brand-400">{{ format_number($totalClicks) }}</div>
                <div class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">{{ __('Clicks tracked') }}</div>
            </div>
            <div class="card card-pad text-center">
                <div class="text-2xl sm:text-3xl font-extrabold text-brand-600 dark:text-brand-400">{{ format_number($totalUsers) }}</div>
                <div class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">{{ __('Happy users') }}</div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================== FEATURES --}}
<section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <h2 class="text-2xl sm:text-4xl font-bold tracking-tight">{{ __('Everything your links deserve') }}</h2>
        <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('One toolbox for creating, branding, targeting and measuring links at any scale.') }}</p>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5">
        @foreach([
            ['icon' => 'link', 'title' => __('Short links'), 'text' => __('Clean, memorable links with custom aliases, expiry and passwords.')],
            ['icon' => 'globe', 'title' => __('Custom domains'), 'text' => __('Connect your own domains for fully branded short links.')],
            ['icon' => 'qr', 'title' => __('QR codes'), 'text' => __('Styled QR codes with logos, colors and downloadable formats.')],
            ['icon' => 'user', 'title' => __('Bio pages'), 'text' => __('A beautiful link-in-bio page with themes, blocks and lead capture.')],
            ['icon' => 'target', 'title' => __('Smart targeting'), 'text' => __('Route visitors by country, device or language to the right destination.')],
            ['icon' => 'refresh', 'title' => __('A/B rotator'), 'text' => __('Split traffic across multiple destinations and find the winner.')],
            ['icon' => 'chart', 'title' => __('Analytics'), 'text' => __('Real-time clicks, referrers, countries, devices and heatmaps.')],
            ['icon' => 'users', 'title' => __('Team workspaces'), 'text' => __('Invite teammates, organize links in spaces and work together.')],
            ['icon' => 'code', 'title' => __('API & webhooks'), 'text' => __('Automate everything with a clean REST API and instant webhooks.')],
        ] as $f)
            <div class="card card-pad">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                    <x-icon :name="$f['icon']" class="h-5 w-5"/>
                </span>
                <h3 class="mt-3 font-semibold text-sm sm:text-base">{{ $f['title'] }}</h3>
                <p class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">{{ $f['text'] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- =========================================== LINK MANAGEMENT SHOWCASE --}}
<section class="bg-slate-50 dark:bg-slate-900/40 border-y border-slate-200/70 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20 grid lg:grid-cols-2 gap-10 items-center">
        <div>
            <span class="badge-brand">{{ __('Organize') }}</span>
            <h2 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight">{{ __('All your links, tidy and under control') }}</h2>
            <p class="mt-3 text-slate-500 dark:text-slate-400">
                {{ __('Group links into spaces, tag them, search instantly, bulk-edit, archive and export. Every link carries its own targeting rules, pixels and QR code.') }}
            </p>
            <ul class="mt-5 space-y-2.5 text-sm">
                @foreach([__('Spaces and tags to keep campaigns separated'), __('Bulk shortening and CSV import/export'), __('One-click enable, disable or archive'), __('Powerful search across aliases and destinations')] as $li)
                    <li class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                            <x-icon name="check" class="h-3 w-3"/>
                        </span>
                        <span class="text-slate-600 dark:text-slate-300">{{ $li }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        {{-- Fake browser mock --}}
        <div class="card overflow-hidden shadow-lg" aria-hidden="true">
            <div class="flex items-center gap-1.5 px-4 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                <span class="ms-3 flex-1 h-6 rounded-md bg-slate-200/70 dark:bg-slate-800"></span>
            </div>
            <div class="p-4 space-y-3">
                @foreach([['sale', 'w-24', '4.2K'], ['launch-day', 'w-32', '2.8K'], ['newsletter', 'w-28', '1.9K'], ['yt-promo', 'w-20', '954']] as [$alias, $w, $clicks])
                    <div class="flex items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 px-3 py-2.5">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                            <x-icon name="link" class="h-4 w-4"/>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold text-slate-700 dark:text-slate-200">/{{ $alias }}</div>
                            <div class="mt-1.5 h-2 {{ $w }} rounded bg-slate-200 dark:bg-slate-800"></div>
                        </div>
                        <span class="badge-brand shrink-0">{{ $clicks }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================================== STATISTICS PREVIEW --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20 grid lg:grid-cols-2 gap-10 items-center">
    {{-- CSS-only chart mock --}}
    <div class="order-2 lg:order-1 card card-pad shadow-lg" aria-hidden="true">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="text-xs text-slate-400">{{ __('Clicks — last 14 days') }}</div>
                <div class="text-2xl font-extrabold">12,481</div>
            </div>
            <span class="badge-green">▲ 23%</span>
        </div>
        <div class="flex items-end gap-1.5 h-36">
            @foreach([35, 48, 40, 62, 55, 70, 64, 82, 74, 90, 78, 96, 88, 100] as $h)
                <div class="flex-1 rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 dark:from-brand-700 dark:to-brand-500" style="height: {{ $h }}%"></div>
            @endforeach
        </div>
        <div class="mt-5 grid grid-cols-3 gap-3 text-center text-xs">
            @foreach([[__('Countries'), '38'], [__('Referrers'), '112'], [__('Devices'), '7']] as [$l, $v])
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 py-3">
                    <div class="font-bold text-base">{{ $v }}</div>
                    <div class="text-slate-400">{{ $l }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="order-1 lg:order-2">
        <span class="badge-brand">{{ __('Measure') }}</span>
        <h2 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight">{{ __('Know exactly who clicks — and from where') }}</h2>
        <p class="mt-3 text-slate-500 dark:text-slate-400">
            {{ __('Every click is broken down by country, city, referrer, device, browser, OS and language. Watch traffic live, spot your best hours in the heatmap and share public stats pages with clients.') }}
        </p>
        <a href="{{ route('register') }}" class="btn-primary mt-6">{{ __('Start tracking free') }}</a>
    </div>
</section>

{{-- ==================================================== INTEGRATIONS --}}
<section class="max-w-5xl mx-auto px-4 sm:px-6 pb-14 sm:pb-20 text-center">
    <h2 class="text-lg font-semibold text-slate-500 dark:text-slate-400">{{ __('Retarget your visitors with pixels from') }}</h2>
    <div class="mt-5 flex flex-wrap justify-center gap-2">
        @foreach(['Google Ads', 'Google Analytics 4', 'Google Tag Manager', 'Meta', 'TikTok', 'LinkedIn', 'X (Twitter)', 'Pinterest', 'Snapchat', 'Reddit', 'Quora', 'Bing', 'AdRoll'] as $provider)
            <span class="rounded-full ring-1 ring-slate-200 dark:ring-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300">{{ $provider }}</span>
        @endforeach
    </div>
</section>

{{-- ========================================================= PRICING --}}
@if($plans->isNotEmpty())
<section class="bg-slate-50 dark:bg-slate-900/40 border-y border-slate-200/70 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-4xl font-bold tracking-tight">{{ __('Simple, honest pricing') }}</h2>
            <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('Start free, upgrade when you grow. No hidden fees.') }}</p>
        </div>
        @php($planGridCols = [1 => 'lg:grid-cols-1 max-w-sm', 2 => 'lg:grid-cols-2 max-w-3xl', 3 => 'lg:grid-cols-3 max-w-5xl', 4 => 'lg:grid-cols-4 max-w-7xl'][min(4, max(1, $plans->count()))])
        <div class="grid sm:grid-cols-2 {{ $planGridCols }} gap-5 mx-auto">
            @foreach($plans as $plan)
                <div class="card card-pad flex flex-col relative {{ $plan->is_featured ? 'ring-2 !ring-brand-500' : '' }}">
                    @if($plan->is_featured)
                        <span class="absolute -top-3 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 badge-brand shadow-sm">{{ __('Popular') }}</span>
                    @endif
                    <h3 class="font-semibold">{{ $plan->name }}</h3>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-3xl font-extrabold">{{ $plan->is_free ? __('Free') : format_money($plan->price_monthly) }}</span>
                        @unless($plan->is_free)<span class="text-sm text-slate-400">/{{ __('mo') }}</span>@endunless
                    </div>
                    @if($plan->description)
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>
                    @endif
                    <ul class="mt-4 space-y-2 text-sm flex-1">
                        @foreach([
                            ['links', __('links')],
                            ['clicks_per_month', __('tracked clicks / month')],
                            ['domains', __('custom domains')],
                            ['qr_codes', __('QR codes')],
                        ] as [$key, $label])
                            <li class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <x-icon name="check" class="h-4 w-4 shrink-0 text-emerald-500"/>
                                <span>
                                    {{ $plan->limit($key) === -1 ? __('Unlimited') : format_number($plan->limit($key)) }}
                                    {{ $label }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-secondary' }} mt-5 w-full">
                        {{ $plan->is_free ? __('Start free') : __('Choose :plan', ['plan' => $plan->name]) }}
                    </a>
                </div>
            @endforeach
        </div>
        <p class="mt-8 text-center text-sm text-slate-500 dark:text-slate-400">
            <a href="{{ route('pricing') }}" class="text-brand-600 font-medium hover:underline">{{ __('Compare all plans and features →') }}</a>
        </p>
    </div>
</section>
@endif

{{-- ==================================================== TESTIMONIALS --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-14 sm:py-20">
    <h2 class="text-center text-2xl sm:text-3xl font-bold tracking-tight mb-10">{{ __('Loved by marketers and creators') }}</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach([
            ['quote' => __('We moved 40k links over in an afternoon. The targeting rules alone doubled our campaign conversion rate.'), 'name' => 'Sarah M.', 'role' => __('Growth Lead')],
            ['quote' => __('The bio page replaced two paid tools for me. My audience clicks more because everything is on-brand now.'), 'name' => 'Diego R.', 'role' => __('Content Creator')],
            ['quote' => __('Clean API, instant webhooks, sensible docs. We had our integration live in a day — support even answered on Sunday.'), 'name' => 'Priya K.', 'role' => __('Engineering Manager')],
        ] as $t)
            <figure class="card card-pad">
                <blockquote class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">“{{ $t['quote'] }}”</blockquote>
                <figcaption class="mt-4 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300 text-sm font-bold">
                        {{ mb_substr($t['name'], 0, 1) }}
                    </span>
                    <span>
                        <span class="block text-sm font-semibold">{{ $t['name'] }}</span>
                        <span class="block text-xs text-slate-400">{{ $t['role'] }}</span>
                    </span>
                </figcaption>
            </figure>
        @endforeach
    </div>
</section>

{{-- ============================================================= FAQ --}}
@if($faqs->isNotEmpty())
<section class="max-w-3xl mx-auto px-4 sm:px-6 pb-14 sm:pb-20">
    <h2 class="text-center text-2xl sm:text-3xl font-bold tracking-tight mb-8">{{ __('Frequently asked questions') }}</h2>
    <div class="space-y-3">
        @foreach($faqs as $faq)
            <div x-data="{ open: false }" class="card">
                <button type="button" @click="open = !open" :aria-expanded="open"
                        class="w-full flex items-center justify-between gap-3 text-start px-4 sm:px-5 py-4 min-h-touch font-medium">
                    <span>{{ $faq->question }}</span>
                    <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''"/>
                </button>
                <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="px-4 sm:px-5 pb-4 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                    {{ $faq->answer }}
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ======================================================= FINAL CTA --}}
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-6">
    <div class="rounded-3xl bg-brand-600 dark:bg-brand-700 px-6 py-12 sm:py-16 text-center text-white overflow-hidden relative">
        <div class="absolute -top-16 -end-16 h-56 w-56 rounded-full bg-white/10" aria-hidden="true"></div>
        <div class="absolute -bottom-20 -start-10 h-64 w-64 rounded-full bg-white/10" aria-hidden="true"></div>
        <h2 class="relative text-2xl sm:text-4xl font-extrabold tracking-tight">{{ __('Ready to shorten smarter?') }}</h2>
        <p class="relative mt-3 text-brand-100 max-w-xl mx-auto">{{ __('Join :count users who trust :site with their links every day.', ['count' => format_number(max($totalUsers, 1)), 'site' => site_name()]) }}</p>
        <a href="{{ route('register') }}" class="relative btn mt-7 bg-white text-brand-700 hover:bg-brand-50 font-semibold px-6">
            {{ __('Create your free account') }}
        </a>
    </div>
</section>
@endsection
