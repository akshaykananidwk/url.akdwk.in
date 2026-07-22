@extends('layouts.admin')

@section('title', __('GDPR & invoices') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-5">
        <h2 class="font-semibold">{{ __('Privacy') }}</h2>
        <div>
            <x-toggle name="anonymize_ips" :checked="(bool) setting('anonymize_ips')" :label="__('Anonymize IP addresses')"/>
            <p class="help mt-1 ms-14">{{ __('The last octet of visitor IPs is zeroed before storing click analytics.') }}</p>
        </div>
        <div>
            <x-toggle name="no_personal_data" :checked="(bool) setting('no_personal_data')" :label="__('Do not store personal data in analytics')"/>
            <p class="help mt-1 ms-14">{{ __('Skips storing IPs and user agents entirely — only aggregate counts are kept.') }}</p>
        </div>
        <div>
            <x-toggle name="geo_http_lookup" :checked="(bool) setting('geo_http_lookup')" :label="__('Geo lookup via HTTP API')"/>
            <p class="help mt-1 ms-14">{{ __('Resolve visitor country via an external HTTP API when no local GeoIP database is available. The IP is sent to a third party.') }}</p>
        </div>
    </div>

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Invoices') }}</h2>
        <x-field name="invoice_prefix" :label="__('Invoice prefix')" class="sm:max-w-xs">
            <input type="text" id="invoice_prefix" name="invoice_prefix" value="{{ setting('invoice_prefix', 'INV-') }}" maxlength="10" class="input" placeholder="INV-">
        </x-field>
        <x-field name="invoice_company_details" :label="__('Company details')" :help="__('Shown on invoices — name, address, tax number, one item per line.')">
            <textarea id="invoice_company_details" name="invoice_company_details" rows="5" class="input">{{ setting('invoice_company_details') }}</textarea>
        </x-field>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
