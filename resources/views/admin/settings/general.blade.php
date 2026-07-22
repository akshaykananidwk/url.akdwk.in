@extends('layouts.admin')

@section('title', __('Settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<div class="space-y-5 max-w-4xl">
    <form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="card card-pad space-y-4">
            <h2 class="font-semibold">{{ __('Site') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="site_name" :label="__('Site name')">
                    <input type="text" id="site_name" name="site_name" value="{{ setting('site_name') }}" class="input">
                </x-field>
                <x-field name="tagline" :label="__('Tagline')">
                    <input type="text" id="tagline" name="tagline" value="{{ setting('tagline') }}" class="input">
                </x-field>
                <x-field name="site_url" :label="__('Site URL')">
                    <input type="url" id="site_url" name="site_url" value="{{ setting('site_url') }}" class="input" placeholder="https://example.com">
                </x-field>
                <x-field name="timezone" :label="__('Timezone')">
                    <select id="timezone" name="timezone" class="input">
                        @foreach(\DateTimeZone::listIdentifiers() as $tz)
                            <option value="{{ $tz }}" @selected(setting('timezone') === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="date_format" :label="__('Date format')" :help="__('PHP date format, e.g. M j, Y')">
                    <input type="text" id="date_format" name="date_format" value="{{ setting('date_format') }}" class="input" placeholder="M j, Y">
                </x-field>
                <x-field name="currency" :label="__('Currency')">
                    <select id="currency" name="currency" class="input">
                        @foreach(['USD', 'EUR', 'GBP', 'INR', 'JPY', 'CNY', 'BRL', 'IDR', 'NGN', 'MXN', 'CAD', 'AUD', 'AED', 'SGD', 'ZAR'] as $cur)
                            <option value="{{ $cur }}" @selected(setting('currency', 'USD') === $cur)>{{ $cur }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="default_language" :label="__('Default language')">
                    <select id="default_language" name="default_language" class="input">
                        @foreach(active_languages() as $lang)
                            <option value="{{ $lang->code }}" @selected(setting('default_language', 'en') === $lang->code)>{{ $lang->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="theme" :label="__('Theme')">
                    <select id="theme" name="theme" class="input">
                        @foreach($themes as $theme)
                            <option value="{{ $theme['slug'] }}" @selected(setting('theme', 'default') === $theme['slug'])>{{ $theme['name'] }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
            <x-toggle name="auto_detect_language" :checked="(bool) setting('auto_detect_language')" :label="__('Auto-detect visitor language from the browser')"/>
        </div>

        <div class="card card-pad space-y-4">
            <h2 class="font-semibold">{{ __('Announcement') }}</h2>
            <x-field name="announcement_text" :label="__('Announcement text')">
                <input type="text" id="announcement_text" name="announcement_text" value="{{ setting('announcement_text') }}" class="input">
            </x-field>
            <x-toggle name="announcement_enabled" :checked="(bool) setting('announcement_enabled')" :label="__('Show announcement bar')"/>
        </div>

        <div class="card card-pad space-y-4">
            <h2 class="font-semibold">{{ __('Maintenance & cookies') }}</h2>
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 ring-1 ring-amber-200 dark:ring-amber-900 p-4">
                <x-toggle name="maintenance_mode" :checked="(bool) setting('maintenance_mode')" :label="__('Maintenance mode')"/>
                <p class="help mt-2 !text-amber-700 dark:!text-amber-400">{{ __('Warning: visitors and users will see a maintenance page. Admins can still log in.') }}</p>
            </div>
            <x-toggle name="cookie_consent_enabled" :checked="(bool) setting('cookie_consent_enabled')" :label="__('Show cookie consent banner')"/>
            <x-field name="cookie_consent_text" :label="__('Cookie consent text')">
                <textarea id="cookie_consent_text" name="cookie_consent_text" rows="2" class="input">{{ setting('cookie_consent_text') }}</textarea>
            </x-field>
        </div>

        <button class="btn-primary">{{ __('Save settings') }}</button>
    </form>

    {{-- Branding (separate multipart form) --}}
    <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" class="card card-pad space-y-4">
        @csrf
        <h2 class="font-semibold">{{ __('Branding') }}</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="logo" :label="__('Logo')" :help="__('PNG/SVG, max 1 MB.')">
                @if(setting('site_logo'))
                    <img src="{{ storage_url(setting('site_logo')) }}" alt="{{ __('Current logo') }}" class="h-10 mb-2">
                @endif
                <input type="file" id="logo" name="logo" accept="image/*" class="input !py-2.5">
            </x-field>
            <x-field name="favicon" :label="__('Favicon')" :help="__('PNG, ICO or SVG, max 512 KB.')">
                @if(setting('site_favicon'))
                    <img src="{{ storage_url(setting('site_favicon')) }}" alt="{{ __('Current favicon') }}" class="h-8 w-8 mb-2">
                @endif
                <input type="file" id="favicon" name="favicon" accept=".png,.ico,.svg" class="input !py-2.5">
            </x-field>
        </div>
        <button class="btn-secondary btn-sm"><x-icon name="upload" class="h-4 w-4"/> {{ __('Upload branding') }}</button>
    </form>
</div>
@endsection
