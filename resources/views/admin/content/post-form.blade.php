@extends('layouts.admin')

@section('title', ($post ? __('Edit post') : __('New post')) . ' — ' . site_name())
@section('page-title', $post ? __('Edit post: :title', ['title' => $post->title]) : __('New post'))

@section('content')
<form method="POST" action="{{ request()->url() }}" enctype="multipart/form-data" class="space-y-5 max-w-4xl">
    @csrf

    <a href="{{ route('admin.content.posts') }}" class="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline min-h-touch">
        <x-icon name="chevron-right" class="h-4 w-4 rotate-180 rtl:rotate-0"/> {{ __('Back to posts') }}
    </a>

    <div class="card card-pad space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="title" :label="__('Title')">
                <input type="text" id="title" name="title" value="{{ old('title', $post?->title) }}" class="input" required>
            </x-field>
            <x-field name="slug" :label="__('Slug')" :help="__('Lowercase letters, numbers and dashes. Empty = generated from the title.')">
                <input type="text" id="slug" name="slug" value="{{ old('slug', $post?->slug) }}" class="input">
            </x-field>
        </div>
        <x-field name="excerpt" :label="__('Excerpt')" :help="__('Short summary shown in the blog listing.')">
            <textarea id="excerpt" name="excerpt" rows="2" class="input">{{ old('excerpt', $post?->excerpt) }}</textarea>
        </x-field>
        <x-field name="content" :label="__('Content')" :help="__('HTML allowed')">
            <textarea id="content" name="content" rows="14" class="input font-mono text-xs">{{ old('content', $post?->content) }}</textarea>
        </x-field>
        <div class="grid sm:grid-cols-2 gap-4 items-end">
            <x-field name="image" :label="__('Cover image')" :help="$post?->image ? __('Uploading a new file replaces the current image.') : __('Optional, max 4 MB.')">
                <input type="file" id="image" name="image" accept="image/*" class="input !py-2.5">
            </x-field>
            <div class="pb-1">
                <x-toggle name="published" :checked="(bool) old('published', $post?->published)" :label="__('Published')"/>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button class="btn-primary">{{ $post ? __('Save post') : __('Create post') }}</button>
        <a href="{{ route('admin.content.posts') }}" class="btn-secondary">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
