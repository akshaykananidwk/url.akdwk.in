@extends('layouts.admin')

@section('title', __('Email settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<div class="space-y-5 max-w-4xl">
    <form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="card card-pad space-y-4">
            <h2 class="font-semibold">{{ __('SMTP') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="smtp_host" :label="__('Host')">
                    <input type="text" id="smtp_host" name="smtp_host" value="{{ setting('smtp_host') }}" class="input" placeholder="smtp.example.com">
                </x-field>
                <x-field name="smtp_port" :label="__('Port')">
                    <input type="number" id="smtp_port" name="smtp_port" value="{{ setting('smtp_port') }}" class="input" placeholder="587">
                </x-field>
                <x-field name="smtp_username" :label="__('Username')">
                    <input type="text" id="smtp_username" name="smtp_username" value="{{ setting('smtp_username') }}" class="input" autocomplete="off">
                </x-field>
                <x-field name="smtp_password" :label="__('Password')" :help="__('Keep the dots unchanged to keep the current password.')">
                    <input type="password" id="smtp_password" name="smtp_password" value="{{ setting('smtp_password') ? '••••••••' : '' }}" class="input" placeholder="••••••••" autocomplete="new-password">
                </x-field>
                <x-field name="smtp_encryption" :label="__('Encryption')">
                    <select id="smtp_encryption" name="smtp_encryption" class="input">
                        @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => __('None')] as $enc => $label)
                            <option value="{{ $enc }}" @selected(setting('smtp_encryption', 'tls') === $enc)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
        </div>

        <div class="card card-pad space-y-4">
            <h2 class="font-semibold">{{ __('Sender') }}</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                <x-field name="mail_from_address" :label="__('From address')">
                    <input type="email" id="mail_from_address" name="mail_from_address" value="{{ setting('mail_from_address') }}" class="input" placeholder="no-reply@example.com">
                </x-field>
                <x-field name="mail_from_name" :label="__('From name')">
                    <input type="text" id="mail_from_name" name="mail_from_name" value="{{ setting('mail_from_name') }}" class="input">
                </x-field>
                <x-field name="contact_email" :label="__('Contact email')" :help="__('Receives contact-form messages.')">
                    <input type="email" id="contact_email" name="contact_email" value="{{ setting('contact_email') }}" class="input">
                </x-field>
            </div>
        </div>

        <button class="btn-primary">{{ __('Save settings') }}</button>
    </form>

    {{-- Test email --}}
    <form method="POST" action="{{ route('admin.settings.test-email') }}" class="card card-pad">
        @csrf
        <h2 class="font-semibold mb-1">{{ __('Send test email') }}</h2>
        <p class="help mb-3">{{ __('Save your settings first, then send a test to verify them.') }}</p>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-field name="to" class="flex-1">
                <input type="email" name="to" value="{{ old('to', auth()->user()->email) }}" class="input" placeholder="you@example.com" required aria-label="{{ __('Recipient') }}">
            </x-field>
            <button class="btn-secondary btn-sm shrink-0 self-start"><x-icon name="mail" class="h-4 w-4"/> {{ __('Send test') }}</button>
        </div>
    </form>
</div>
@endsection
