@extends('layouts.admin')

@section('title', __('Registration settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Accounts') }}</h2>
        <x-toggle name="registration_enabled" :checked="(bool) setting('registration_enabled', true)" :label="__('Allow new registrations')"/>
        <x-toggle name="require_email_verification" :checked="(bool) setting('require_email_verification')" :label="__('Require email verification before login')"/>
    </div>

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Guest shortening') }}</h2>
        <x-toggle name="guest_shorten_enabled" :checked="(bool) setting('guest_shorten_enabled')" :label="__('Allow visitors to shorten links without an account')"/>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="guest_link_days" :label="__('Guest link lifetime (days)')" :help="__('Guest links expire after this many days.')">
                <input type="number" min="1" max="3650" id="guest_link_days" name="guest_link_days" value="{{ setting('guest_link_days', 30) }}" class="input">
            </x-field>
            <x-field name="guest_shorten_rate" :label="__('Guest rate limit (per hour)')" :help="__('Maximum links a guest IP can create per hour.')">
                <input type="number" min="1" max="100" id="guest_shorten_rate" name="guest_shorten_rate" value="{{ setting('guest_shorten_rate', 5) }}" class="input">
            </x-field>
        </div>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
