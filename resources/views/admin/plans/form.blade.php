@extends('layouts.admin')

@section('title', ($plan ? __('Edit plan') : __('New plan')) . ' — ' . site_name())
@section('page-title', $plan ? __('Edit plan: :name', ['name' => $plan->name]) : __('New plan'))

@section('content')
<form method="POST" action="{{ $plan ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="space-y-5 max-w-4xl">
    @csrf
    @if($plan) @method('PUT') @endif

    <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline min-h-touch">
        <x-icon name="chevron-right" class="h-4 w-4 rotate-180 rtl:rotate-0"/> {{ __('Back to plans') }}
    </a>

    {{-- Basics --}}
    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Basics') }}</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="name" :label="__('Name')">
                <input type="text" id="name" name="name" value="{{ old('name', $plan?->name) }}" class="input" required>
            </x-field>
            <x-field name="sort_order" :label="__('Sort order')">
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $plan?->sort_order ?? 0) }}" class="input">
            </x-field>
        </div>
        <x-field name="description" :label="__('Description')">
            <textarea id="description" name="description" rows="2" class="input">{{ old('description', $plan?->description) }}</textarea>
        </x-field>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-field name="price_monthly" :label="__('Monthly price')">
                <input type="number" step="0.01" min="0" id="price_monthly" name="price_monthly" value="{{ old('price_monthly', $plan?->price_monthly ?? 0) }}" class="input" required>
            </x-field>
            <x-field name="price_yearly" :label="__('Yearly price')">
                <input type="number" step="0.01" min="0" id="price_yearly" name="price_yearly" value="{{ old('price_yearly', $plan?->price_yearly ?? 0) }}" class="input" required>
            </x-field>
            <x-field name="price_lifetime" :label="__('Lifetime price')">
                <input type="number" step="0.01" min="0" id="price_lifetime" name="price_lifetime" value="{{ old('price_lifetime', $plan?->price_lifetime ?? 0) }}" class="input" required>
            </x-field>
            <x-field name="trial_days" :label="__('Trial days')">
                <input type="number" min="0" max="365" id="trial_days" name="trial_days" value="{{ old('trial_days', $plan?->trial_days ?? 0) }}" class="input" required>
            </x-field>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
            <x-toggle name="is_free" :checked="(bool) old('is_free', $plan?->is_free)" :label="__('Free plan')"/>
            <x-toggle name="is_default" :checked="(bool) old('is_default', $plan?->is_default)" :label="__('Default')"/>
            <x-toggle name="is_featured" :checked="(bool) old('is_featured', $plan?->is_featured)" :label="__('Featured')"/>
            <x-toggle name="active" :checked="(bool) old('active', $plan?->active ?? true)" :label="__('Active')"/>
        </div>
    </div>

    {{-- Limits --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Limits') }}</h2>
        <p class="help mb-4">{{ __('-1 = unlimited') }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($limitKeys as $key => $label)
                <div>
                    <label for="limit-{{ $key }}" class="label">{{ __($label) }}</label>
                    <input type="number" id="limit-{{ $key }}" name="limits[{{ $key }}]" min="-1"
                           value="{{ old('limits.' . $key, $plan->limits[$key] ?? 0) }}" class="input">
                </div>
            @endforeach
        </div>
    </div>

    {{-- Features --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Features') }}</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($featureKeys as $key => $label)
                <x-toggle name="features[{{ $key }}]" :checked="(bool) old('features.' . $key, $plan?->hasFeature($key))" :label="__($label)"/>
            @endforeach
        </div>
    </div>

    <div class="flex gap-2">
        <button class="btn-primary">{{ $plan ? __('Save plan') : __('Create plan') }}</button>
        <a href="{{ route('admin.plans.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
