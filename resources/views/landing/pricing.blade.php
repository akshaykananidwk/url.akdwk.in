@extends('layouts.landing')

@section('title', __('Pricing') . ' — ' . site_name())

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16" x-data="{ yearly: false }">

    <div class="text-center max-w-2xl mx-auto">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Pricing that scales with you') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('Every plan starts free of risk — upgrade, downgrade or cancel anytime.') }}</p>

        {{-- Cycle toggle --}}
        @php($maxDiscount = $plans->max(fn ($p) => $p->yearlyDiscountPercent()))
        <div class="mt-7 inline-flex items-center rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-1 text-sm font-medium bg-white dark:bg-slate-900">
            <button type="button" @click="yearly = false"
                    class="px-4 py-2 rounded-lg min-h-[40px]"
                    :class="!yearly ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'">
                {{ __('Monthly') }}
            </button>
            <button type="button" @click="yearly = true"
                    class="px-4 py-2 rounded-lg min-h-[40px] inline-flex items-center gap-2"
                    :class="yearly ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'">
                {{ __('Yearly') }}
                @if($maxDiscount > 0)
                    <span class="badge-green">{{ __('Save up to :pct%', ['pct' => $maxDiscount]) }}</span>
                @endif
            </button>
        </div>
    </div>

    @php
        $featureKeys = ['custom_alias', 'custom_domains', 'password', 'targeting', 'rotator', 'pixels', 'api', 'team'];
    @endphp

    {{-- Plan cards --}}
    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-{{ min(4, max(1, $plans->count())) }} gap-5">
        @foreach($plans as $plan)
            <div class="card card-pad flex flex-col relative {{ $plan->is_featured ? 'ring-2 !ring-brand-500' : '' }}">
                @if($plan->is_featured)
                    <span class="absolute -top-3 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 badge-brand shadow-sm">{{ __('Popular') }}</span>
                @endif

                <h2 class="font-semibold">{{ $plan->name }}</h2>
                @if($plan->description)
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>
                @endif

                <div class="mt-3">
                    @if($plan->is_free)
                        <span class="text-3xl font-extrabold">{{ __('Free') }}</span>
                        <span class="text-sm text-slate-400">{{ __('forever') }}</span>
                    @else
                        <div x-show="!yearly">
                            <span class="text-3xl font-extrabold">{{ format_money($plan->price_monthly) }}</span>
                            <span class="text-sm text-slate-400">/{{ __('month') }}</span>
                        </div>
                        <div x-cloak x-show="yearly">
                            <span class="text-3xl font-extrabold">{{ format_money($plan->price_yearly) }}</span>
                            <span class="text-sm text-slate-400">/{{ __('year') }}</span>
                            @if($plan->yearlyDiscountPercent() > 0)
                                <span class="badge-green ms-1">{{ __('-:pct%', ['pct' => $plan->yearlyDiscountPercent()]) }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                @auth
                    <a href="{{ route('billing.plans') }}" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-secondary' }} mt-4 w-full">
                        {{ $plan->is_free ? __('Included') : __('Upgrade') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-secondary' }} mt-4 w-full">
                        {{ $plan->is_free ? __('Start free') : __('Get started') }}
                    </a>
                @endauth

                {{-- Limits --}}
                <ul class="mt-5 space-y-2 text-sm border-t border-slate-100 dark:border-slate-800 pt-4">
                    @foreach(\App\Models\Plan::LIMIT_KEYS as $key => $label)
                        <li class="flex items-center justify-between gap-2 text-slate-600 dark:text-slate-300">
                            <span>{{ __($label) }}</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-100">
                                {{ $plan->limit($key) === -1 ? '∞' : format_number($plan->limit($key)) }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                {{-- Key features --}}
                <ul class="mt-4 space-y-2 text-sm border-t border-slate-100 dark:border-slate-800 pt-4 flex-1">
                    @foreach($featureKeys as $key)
                        <li class="flex items-center gap-2 {{ $plan->hasFeature($key) ? 'text-slate-600 dark:text-slate-300' : 'text-slate-400 dark:text-slate-600 line-through' }}">
                            @if($plan->hasFeature($key))
                                <x-icon name="check" class="h-4 w-4 shrink-0 text-emerald-500"/>
                            @else
                                <x-icon name="x" class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-700"/>
                            @endif
                            <span>{{ __(\App\Models\Plan::FEATURE_KEYS[$key]) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    {{-- FAQ --}}
    @if($faqs->isNotEmpty())
        <div class="max-w-3xl mx-auto mt-16">
            <h2 class="text-center text-2xl font-bold tracking-tight mb-8">{{ __('Pricing questions') }}</h2>
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
        </div>
    @endif
</div>
@endsection
