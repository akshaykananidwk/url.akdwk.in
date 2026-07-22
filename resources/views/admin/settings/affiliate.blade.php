@extends('layouts.admin')

@section('title', __('Affiliate settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Affiliate program') }}</h2>
        <x-toggle name="affiliate_enabled" :checked="(bool) setting('affiliate_enabled')" :label="__('Enable the affiliate program')"/>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="affiliate_commission_percent" :label="__('Commission (%)')" :help="__('Share of each referred payment credited to the affiliate.')">
                <input type="number" step="0.01" min="0" max="100" id="affiliate_commission_percent" name="affiliate_commission_percent"
                       value="{{ setting('affiliate_commission_percent', 20) }}" class="input">
            </x-field>
            <x-field name="affiliate_min_payout" :label="__('Minimum payout')" :help="__('Affiliates can request a payout once their balance reaches this amount.')">
                <input type="number" step="0.01" min="0" id="affiliate_min_payout" name="affiliate_min_payout"
                       value="{{ setting('affiliate_min_payout', 50) }}" class="input">
            </x-field>
        </div>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
