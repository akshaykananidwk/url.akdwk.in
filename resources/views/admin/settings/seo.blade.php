@extends('layouts.admin')

@section('title', __('SEO settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<form method="POST" action="{{ route('admin.settings.update', $tab) }}" class="space-y-5 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('Meta tags') }}</h2>
        <x-field name="meta_title" :label="__('Meta title')" :help="__('Shown in search results for the homepage.')">
            <input type="text" id="meta_title" name="meta_title" value="{{ setting('meta_title') }}" class="input">
        </x-field>
        <x-field name="meta_description" :label="__('Meta description')">
            <textarea id="meta_description" name="meta_description" rows="2" class="input">{{ setting('meta_description') }}</textarea>
        </x-field>
        <x-field name="og_image" :label="__('Open Graph image URL')" :help="__('Shown when the site is shared on social media. Recommended 1200×630.')">
            <input type="url" id="og_image" name="og_image" value="{{ setting('og_image') }}" class="input" placeholder="https://…/og.png">
        </x-field>
    </div>

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('robots.txt') }}</h2>
        <x-field name="robots_txt">
            <textarea id="robots_txt" name="robots_txt" rows="6" class="input font-mono text-xs" placeholder="User-agent: *&#10;Allow: /">{{ setting('robots_txt') }}</textarea>
        </x-field>
        <p class="help">
            {{ __('The sitemap is generated automatically:') }}
            <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">{{ url('/sitemap.xml') }}</a>
        </p>
    </div>

    <button class="btn-primary">{{ __('Save settings') }}</button>
</form>
@endsection
