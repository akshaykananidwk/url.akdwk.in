@extends('layouts.admin')

@section('title', __('Payment settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

@php
    // field types: text, secret, toggle, textarea
    $gatewayFields = [
        'stripe' => [
            'secret_key' => ['secret', __('Secret key')],
            'webhook_secret' => ['secret', __('Webhook signing secret')],
        ],
        'paypal' => [
            'client_id' => ['text', __('Client ID')],
            'secret' => ['secret', __('Secret')],
            'sandbox' => ['toggle', __('Sandbox mode')],
        ],
        'razorpay' => [
            'key_id' => ['text', __('Key ID')],
            'key_secret' => ['secret', __('Key secret')],
        ],
        'paystack' => [
            'secret_key' => ['secret', __('Secret key')],
        ],
        'mollie' => [
            'api_key' => ['secret', __('API key')],
        ],
        'paddle' => [
            'api_key' => ['secret', __('API key')],
            'webhook_secret' => ['secret', __('Webhook secret')],
            'checkout_url' => ['text', __('Checkout URL')],
            'sandbox' => ['toggle', __('Sandbox mode')],
        ],
        'xendit' => [
            'secret_key' => ['secret', __('Secret key')],
            'callback_token' => ['secret', __('Callback token')],
        ],
        'mercadopago' => [
            'access_token' => ['secret', __('Access token')],
        ],
        'coinbase' => [
            'api_key' => ['secret', __('API key')],
            'webhook_secret' => ['secret', __('Webhook shared secret')],
        ],
        'nowpayments' => [
            'api_key' => ['secret', __('API key')],
            'ipn_secret' => ['secret', __('IPN secret')],
        ],
        'upi' => [
            'vpa' => ['text', __('UPI ID (VPA)')],
            'payee_name' => ['text', __('Payee name')],
        ],
        'bank' => [
            'details' => ['textarea', __('Bank details')],
        ],
    ];
    $noWebhook = ['upi', 'bank'];
@endphp

<form method="POST" action="{{ route('admin.settings.payments') }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    @foreach($gateways as $g)
        @php $key = $g->key(); @endphp
        <div class="card card-pad space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold">{{ $g->label() }}</h2>
                <x-toggle name="{{ $key }}_enabled" :checked="(bool) setting($key . '_enabled')" :label="__('Enabled')"/>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($gatewayFields[$key] ?? [] as $field => [$type, $label])
                    @php $settingKey = $key . '_' . $field; @endphp
                    @if($type === 'secret')
                        <x-field name="{{ $key }}.{{ $field }}" :label="$label" :help="__('Keep the dots unchanged to keep the current value.')">
                            <input type="password" name="{{ $key }}[{{ $field }}]"
                                   value="{{ setting($settingKey) ? '••••••••' : '' }}" class="input" placeholder="••••••••" autocomplete="new-password">
                        </x-field>
                    @elseif($type === 'toggle')
                        <div class="pt-7">
                            <label class="inline-flex items-center gap-2.5 min-h-touch cursor-pointer text-sm font-medium">
                                <input type="checkbox" name="{{ $key }}[{{ $field }}]" value="1" class="checkbox" @checked(setting($settingKey))>
                                {{ $label }}
                            </label>
                        </div>
                    @elseif($type === 'textarea')
                        <x-field name="{{ $key }}.{{ $field }}" :label="$label" :help="__('One per line, Label: Value')" class="sm:col-span-2">
                            <textarea name="{{ $key }}[{{ $field }}]" rows="4" class="input" placeholder="Account name: …&#10;IBAN: …&#10;SWIFT: …">{{ setting($settingKey) }}</textarea>
                        </x-field>
                    @else
                        <x-field name="{{ $key }}.{{ $field }}" :label="$label">
                            <input type="text" name="{{ $key }}[{{ $field }}]" value="{{ setting($settingKey) }}" class="input" autocomplete="off">
                        </x-field>
                    @endif
                @endforeach
            </div>

            @unless(in_array($key, $noWebhook))
                <div>
                    <p class="label">{{ __('Webhook URL') }}</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 min-w-0 truncate rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2.5 text-xs">{{ url('/webhooks/' . $key) }}</code>
                        <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js(url('/webhooks/' . $key)))" aria-label="{{ __('Copy') }}">
                            <x-icon name="copy" class="h-4 w-4"/>
                        </button>
                    </div>
                    <p class="help">{{ __('Configure this URL in the gateway dashboard so payments complete automatically.') }}</p>
                </div>
            @endunless
        </div>
    @endforeach

    <button class="btn-primary">{{ __('Save payment settings') }}</button>
</form>
@endsection
