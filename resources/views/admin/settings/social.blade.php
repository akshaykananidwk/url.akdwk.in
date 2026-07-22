@extends('layouts.admin')

@section('title', __('Social login') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

@php
    $providers = [
        'google' => 'Google',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter / X',
        'github' => 'GitHub',
        'apple' => 'Apple',
    ];
@endphp

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    @foreach($providers as $key => $label)
        <div class="card card-pad space-y-4">
            <h2 class="font-semibold">{{ $label }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="oauth_{{ $key }}_id" :label="__('Client ID')">
                    <input type="text" id="oauth_{{ $key }}_id" name="oauth_{{ $key }}_id" value="{{ setting('oauth_' . $key . '_id') }}" class="input" autocomplete="off">
                </x-field>
                <x-field name="oauth_{{ $key }}_secret" :label="__('Client secret')"
                         :help="$key === 'apple' ? __('Paste a pre-generated client secret JWT. Keep the dots unchanged to keep the current value.') : __('Keep the dots unchanged to keep the current value.')">
                    <input type="password" id="oauth_{{ $key }}_secret" name="oauth_{{ $key }}_secret"
                           value="{{ setting('oauth_' . $key . '_secret') ? '••••••••' : '' }}" class="input" placeholder="••••••••" autocomplete="new-password">
                </x-field>
            </div>
            <div>
                <p class="label">{{ __('Redirect URL') }}</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 min-w-0 truncate rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2.5 text-xs">{{ url('/auth/social/' . $key . '/callback') }}</code>
                    <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js(url('/auth/social/' . $key . '/callback')))" aria-label="{{ __('Copy') }}">
                        <x-icon name="copy" class="h-4 w-4"/>
                    </button>
                </div>
            </div>
        </div>
    @endforeach

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
