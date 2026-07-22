@extends('layouts.admin')

@section('title', ($page ? __('Edit page') : __('New page')) . ' — ' . site_name())
@section('page-title', $page ? __('Edit page: :title', ['title' => $page->title]) : __('New page'))

@section('content')
<form method="POST" action="{{ request()->url() }}" class="space-y-5 max-w-4xl">
    @csrf

    <a href="{{ route('admin.content.pages') }}" class="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline min-h-touch">
        <x-icon name="chevron-right" class="h-4 w-4 rotate-180 rtl:rotate-0"/> {{ __('Back to pages') }}
    </a>

    <div class="card card-pad space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="title" :label="__('Title')">
                <input type="text" id="title" name="title" value="{{ old('title', $page?->title) }}" class="input" required>
            </x-field>
            <x-field name="slug" :label="__('Slug')" :help="__('Lowercase letters, numbers and dashes. Empty = generated from the title.')">
                <input type="text" id="slug" name="slug" value="{{ old('slug', $page?->slug) }}" class="input" placeholder="about-us">
            </x-field>
        </div>
        <x-field name="content" :label="__('Content')" :help="__('HTML allowed')">
            <textarea id="content" name="content" rows="14" class="input font-mono text-xs">{{ old('content', $page?->content) }}</textarea>
        </x-field>
    </div>

    <div class="card card-pad space-y-4">
        <h2 class="font-semibold">{{ __('SEO') }}</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="meta_title" :label="__('Meta title')">
                <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $page?->meta_title) }}" class="input">
            </x-field>
            <x-field name="meta_description" :label="__('Meta description')">
                <input type="text" id="meta_description" name="meta_description" value="{{ old('meta_description', $page?->meta_description) }}" class="input">
            </x-field>
        </div>
        <div class="flex flex-wrap gap-x-8 gap-y-2">
            <x-toggle name="active" :checked="(bool) old('active', $page?->active ?? true)" :label="__('Active')"/>
            <x-toggle name="show_in_footer" :checked="(bool) old('show_in_footer', $page?->show_in_footer ?? true)" :label="__('Show in footer')"/>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="btn-primary">{{ $page ? __('Save page') : __('Create page') }}</button>
        <a href="{{ route('admin.content.pages') }}" class="btn-secondary">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
