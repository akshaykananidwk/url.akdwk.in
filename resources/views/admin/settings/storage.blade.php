@extends('layouts.admin')

@section('title', __('Storage settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Storage disk') }}</h2>
        <x-field name="storage_disk" :label="__('Disk')" :help="__('Where uploads (avatars, QR logos, blog images) are stored.')" class="sm:max-w-xs">
            <select id="storage_disk" name="storage_disk" class="input">
                <option value="public" @selected(setting('storage_disk', 'public') === 'public')>{{ __('Local (public)') }}</option>
                <option value="s3" @selected(setting('storage_disk') === 's3')>{{ __('S3-compatible') }}</option>
            </select>
        </x-field>
    </div>

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('S3 credentials') }}</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="s3_key" :label="__('Access key ID')">
                <input type="text" id="s3_key" name="s3_key" value="{{ setting('s3_key') }}" class="input" autocomplete="off">
            </x-field>
            <x-field name="s3_secret" :label="__('Secret access key')" :help="__('Keep the dots unchanged to keep the current value.')">
                <input type="password" id="s3_secret" name="s3_secret" value="{{ setting('s3_secret') ? '••••••••' : '' }}" class="input" placeholder="••••••••" autocomplete="new-password">
            </x-field>
            <x-field name="s3_region" :label="__('Region')">
                <input type="text" id="s3_region" name="s3_region" value="{{ setting('s3_region') }}" class="input" placeholder="us-east-1">
            </x-field>
            <x-field name="s3_bucket" :label="__('Bucket')">
                <input type="text" id="s3_bucket" name="s3_bucket" value="{{ setting('s3_bucket') }}" class="input">
            </x-field>
            <x-field name="s3_endpoint" :label="__('Endpoint')" :help="__('Only for non-AWS providers (MinIO, DigitalOcean Spaces…).')">
                <input type="url" id="s3_endpoint" name="s3_endpoint" value="{{ setting('s3_endpoint') }}" class="input" placeholder="https://…">
            </x-field>
            <div class="pt-7">
                <x-toggle name="s3_path_style" :checked="(bool) setting('s3_path_style')" :label="__('Use path-style endpoint')"/>
            </div>
        </div>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
