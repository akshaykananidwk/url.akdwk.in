@extends('layouts.landing')

@section('title', $post->title . ' — ' . site_name())
@if($post->excerpt)
    @section('meta_description', $post->excerpt)
@endif

@section('content')
<article class="max-w-3xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <a href="{{ route('blog') }}" class="text-sm text-brand-600 hover:underline">&larr; {{ __('All posts') }}</a>

    <h1 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">{{ $post->title }}</h1>

    <div class="mt-4 flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
        @if($post->author)
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300 text-xs font-bold">
                {{ mb_substr($post->author->name, 0, 1) }}
            </span>
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $post->author->name }}</span>
            <span aria-hidden="true">·</span>
        @endif
        <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('F j, Y') }}</time>
    </div>

    @if($post->image)
        <img src="{{ storage_url($post->image) }}" alt="{{ $post->title }}" class="mt-8 w-full rounded-2xl object-cover max-h-96">
    @endif

    <div class="mt-8 prose-content">
        {!! $post->content !!}
    </div>
</article>
@endsection
