@extends('layouts.app')

@section('title', __('Plans') . ' — ' . site_name())
@section('page-title', __('Plans & pricing'))

@php
    $featureList = [
        'custom_domains', 'qr', 'bio', 'pixels', 'targeting', 'password',
        'api', 'team', 'export', 'bulk', 'remove_branding',
    ];
    $limitLabel = function (int $limit, string $singular) {
        return $limit === -1 ? __('Unlimited :thing', ['thing' => $singular]) : format_number($limit) . ' ' . $singular;
    };
    $cycleSuffix = ['monthly' => __('/month'), 'yearly' => __('/year'), 'lifetime' => __('once')];
@endphp

@section('content')
<div class="space-y-6" x-data="{ cycle: 'monthly' }">

    {{-- Cycle switch --}}
    <div class="flex justify-center">
        <div class="flex rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-0.5 text-sm font-medium bg-white dark:bg-slate-900">
            @foreach(['monthly' => __('Monthly'), 'yearly' => __('Yearly'), 'lifetime' => __('Lifetime')] as $cycleKey => $cycleLabel)
                <button type="button" @click="cycle = '{{ $cycleKey }}'"
                        :class="cycle === '{{ $cycleKey }}' ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                        class="px-4 py-2 rounded-lg min-h-[40px]">{{ $cycleLabel }}</button>
            @endforeach
        </div>
    </div>

    {{-- Plan cards --}}
    <div class="grid sm:grid-cols-2 {{ $plans->count() >= 4 ? 'xl:grid-cols-4' : 'lg:grid-cols-3' }} gap-4 items-stretch">
        @foreach($plans as $plan)
            @php
                $prices = [
                    'monthly' => (float) $plan->price_monthly,
                    'yearly' => (float) $plan->price_yearly,
                    'lifetime' => (float) $plan->price_lifetime,
                ];
                $isCurrent = $current->id === $plan->id;
            @endphp
            <div class="card card-pad flex flex-col {{ $plan->is_featured ? 'ring-2 !ring-brand-500 relative' : '' }}">
                @if($plan->is_featured)
                    <span class="badge-brand absolute -top-2.5 start-1/2 -translate-x-1/2">{{ __('Popular') }}</span>
                @endif

                <div class="flex items-center justify-between gap-2">
                    <h2 class="font-bold text-lg">{{ $plan->name }}</h2>
                    @if($isCurrent)<span class="badge-green">{{ __('Current plan') }}</span>@endif
                </div>
                @if($plan->description)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $plan->description }}</p>
                @endif

                <div class="mt-4">
                    @foreach(['monthly', 'yearly', 'lifetime'] as $cycleKey)
                        <div x-show="cycle === '{{ $cycleKey }}'" {{ $cycleKey !== 'monthly' ? 'x-cloak' : '' }}>
                            <span class="text-3xl font-extrabold tracking-tight">{{ format_money($prices[$cycleKey]) }}</span>
                            <span class="text-sm text-slate-500">{{ $cycleSuffix[$cycleKey] }}</span>
                            @if($cycleKey === 'yearly' && $plan->yearlyDiscountPercent() > 0)
                                <span class="badge-green ms-1">{{ __('Save :percent%', ['percent' => $plan->yearlyDiscountPercent()]) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <ul class="mt-5 space-y-2 text-sm flex-1">
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-500 shrink-0"/> {{ $limitLabel($plan->limit('links'), __('links')) }}</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-500 shrink-0"/> {{ $limitLabel($plan->limit('clicks_per_month'), __('tracked clicks / month')) }}</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-500 shrink-0"/> {{ $limitLabel($plan->limit('domains'), __('custom domains')) }}</li>
                    <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-emerald-500 shrink-0"/> {{ $limitLabel($plan->limit('team_members'), __('team members')) }}</li>
                    @foreach($featureList as $featureKey)
                        <li class="flex items-center gap-2 {{ $plan->hasFeature($featureKey) ? '' : 'text-slate-400 dark:text-slate-500' }}">
                            <x-icon :name="$plan->hasFeature($featureKey) ? 'check' : 'x'"
                                    class="h-4 w-4 shrink-0 {{ $plan->hasFeature($featureKey) ? 'text-emerald-500' : 'text-slate-300 dark:text-slate-600' }}"/>
                            {{ __(\App\Models\Plan::FEATURE_KEYS[$featureKey]) }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-5">
                    @if($isCurrent)
                        <button type="button" class="btn-secondary w-full" disabled>{{ __('Current plan') }}</button>
                    @elseif($plan->is_free)
                        <button type="button" class="btn-secondary w-full" disabled>{{ __('Free forever') }}</button>
                    @else
                        <a x-show="@js($prices)[cycle] > 0"
                           :href="'{{ route('billing.checkout', $plan) }}?cycle=' + cycle"
                           class="{{ $plan->is_featured ? 'btn-primary' : 'btn-secondary' }} w-full">
                            {{ __('Choose :plan', ['plan' => $plan->name]) }}
                        </a>
                        <button x-cloak x-show="@js($prices)[cycle] <= 0" type="button" class="btn-secondary w-full" disabled>
                            {{ __('Not available for this cycle') }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-center text-sm text-slate-500">
        <a href="{{ route('billing.invoices') }}" class="text-brand-600 hover:underline">{{ __('View billing history') }}</a>
    </p>
</div>
@endsection
