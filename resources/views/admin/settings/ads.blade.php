@extends('layouts.admin')

@section('title', __('Ads settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Interstitial ads') }}</h2>
        <p class="help">{{ __('Show an ad page with a countdown before redirecting. Plans with the "No interstitial ads" feature skip it.') }}</p>
        <x-toggle name="interstitial_enabled" :checked="(bool) setting('interstitial_enabled')" :label="__('Enable interstitial page')"/>
        <x-field name="interstitial_seconds" :label="__('Countdown (seconds)')" class="sm:max-w-xs">
            <input type="number" min="1" max="60" id="interstitial_seconds" name="interstitial_seconds" value="{{ setting('interstitial_seconds', 5) }}" class="input">
        </x-field>
        <x-field name="interstitial_ad_code" :label="__('Ad code')" :help="__('HTML/JS ad snippet (e.g. AdSense) rendered on the interstitial page.')">
            <textarea id="interstitial_ad_code" name="interstitial_ad_code" rows="6" class="input font-mono text-xs">{{ setting('interstitial_ad_code') }}</textarea>
        </x-field>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
