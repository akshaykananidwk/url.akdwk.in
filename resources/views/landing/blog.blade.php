@extends('layouts.landing')

@section('title', __('Blog') . ' — ' . site_name())

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 sm:py-16">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Blog') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">{{ __('Product updates, growth tips and link-building know-how.') }}</p>
    </div>

    @if($posts->isEmpty())
        <x-empty-state icon="doc" :title="__('No posts yet')" :description="__('Check back soon — we are writing our first stories.')"/>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($posts as $post)
                <a href="{{ route('blog.post', $post->slug) }}" class="card overflow-hidden flex flex-col hover:ring-brand-300 dark:hover:ring-brand-700 transition-shadow hover:shadow-md">
                    @if($post->image)
                        <img src="{{ storage_url($post->image) }}" alt="" class="h-44 w-full object-cover" loading="lazy">
                    @else
                        <div class="h-44 w-full bg-gradient-to-br from-brand-100 to-brand-50 dark:from-brand-950 dark:to-slate-900 flex items-center justify-center">
                            <x-icon name="doc" class="h-10 w-10 text-brand-300 dark:text-brand-800"/>
                        </div>
                    @endif
                    <div class="card-pad flex-1 flex flex-col">
                        <h2 class="font-semibold leading-snug">{{ $post->title }}</h2>
                        @if($post->excerpt)
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 line-clamp-3 flex-1">{{ $post->excerpt }}</p>
                        @endif
                        <time datetime="{{ $post->published_at?->toDateString() }}" class="mt-3 block text-xs text-slate-400">
                            {{ $post->published_at?->translatedFormat('M j, Y') }}
                        </time>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
