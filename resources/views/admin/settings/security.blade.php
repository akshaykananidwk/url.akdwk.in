@extends('layouts.admin')

@section('title', __('Security settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Captcha') }}</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <x-field name="captcha_provider" :label="__('Provider')">
                <select id="captcha_provider" name="captcha_provider" class="input">
                    @foreach(['none' => __('None'), 'recaptcha2' => 'reCAPTCHA v2', 'recaptcha3' => 'reCAPTCHA v3', 'hcaptcha' => 'hCaptcha'] as $p => $label)
                        <option value="{{ $p }}" @selected(setting('captcha_provider', 'none') === $p)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field name="captcha_site_key" :label="__('Site key')">
                <input type="text" id="captcha_site_key" name="captcha_site_key" value="{{ setting('captcha_site_key') }}" class="input" autocomplete="off">
            </x-field>
            <x-field name="captcha_secret" :label="__('Secret key')" :help="__('Keep the dots unchanged to keep the current value.')">
                <input type="password" id="captcha_secret" name="captcha_secret" value="{{ setting('captcha_secret') ? '••••••••' : '' }}" class="input" placeholder="••••••••" autocomplete="new-password">
            </x-field>
        </div>
        <x-toggle name="honeypot_enabled" :checked="(bool) setting('honeypot_enabled')" :label="__('Honeypot spam protection on public forms')"/>
    </div>

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Threat protection & monitoring') }}</h2>
        <x-field name="safe_browsing_key" :label="__('Google Safe Browsing API key')"
                 :help="__('Destination URLs are checked against Google\'s malware and phishing lists before shortening. Keep the dots unchanged to keep the current value.')">
            <input type="password" id="safe_browsing_key" name="safe_browsing_key" value="{{ setting('safe_browsing_key') ? '••••••••' : '' }}" class="input" placeholder="••••••••" autocomplete="new-password">
        </x-field>
        <x-field name="sentry_dsn" :label="__('Sentry DSN')" :help="__('Optional — server errors are reported to this Sentry project.')">
            <input type="url" id="sentry_dsn" name="sentry_dsn" value="{{ setting('sentry_dsn') }}" class="input" placeholder="https://…@sentry.io/…">
        </x-field>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
