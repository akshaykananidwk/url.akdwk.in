@extends('layouts.landing')

@section('title', __('Creator Directory') . ' — ' . site_name())

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <div class="text-center max-w-2xl mx-auto mb-8">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Creator Directory') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">
            {{ __('Browse creators, brands and link-in-bio pages on :site.', ['site' => site_name()]) }}
        </p>

        <form method="GET" action="{{ route('directory.index') }}" class="mt-6 flex items-center gap-2 max-w-md mx-auto">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -mt-2 h-4 w-4 text-slate-400"/>
                <input type="search" name="q" value="{{ $q }}" class="input !pl-9" placeholder="{{ __('Search creators…') }}">
            </div>
            <button type="submit" class="btn-primary btn-sm">{{ __('Search') }}</button>
        </form>

        @if($q !== '')
            <p class="mt-3 text-sm text-slate-400">
                {{ __(':count results for ":q"', ['count' => format_number($pages->total()), 'q' => $q]) }}
                · <a href="{{ route('directory.index') }}" class="text-brand-600 dark:text-brand-400 hover:underline">{{ __('Clear') }}</a>
            </p>
        @endif
    </div>

    @if($pages->isEmpty())
        <x-empty-state icon="users" :title="__('No creators found')" :description="__('Try a different search, or check back soon.')">
            @if($q !== '')
                <a href="{{ route('directory.index') }}" class="btn-secondary btn-sm">{{ __('View all creators') }}</a>
            @endif
        </x-empty-state>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($pages as $page)
                <a href="{{ route('bio.show', $page->username) }}" class="card overflow-hidden flex flex-col hover:ring-brand-300 dark:hover:ring-brand-700 transition-shadow hover:shadow-md">
                    <div class="h-24 w-full bg-gradient-to-br from-brand-100 to-brand-50 dark:from-brand-950 dark:to-slate-900 relative">
                        @if($page->cover && storage_url($page->cover))
                            <img src="{{ storage_url($page->cover) }}" alt="" class="h-24 w-full object-cover" loading="lazy">
                        @endif
                    </div>
                    <div class="card-pad flex-1 flex flex-col -mt-8">
                        <div class="h-16 w-16 rounded-2xl ring-4 ring-white dark:ring-slate-900 overflow-hidden bg-brand-500 flex items-center justify-center text-white text-2xl font-bold shrink-0">
                            @if($page->avatar && storage_url($page->avatar))
                                <img src="{{ storage_url($page->avatar) }}" alt="" class="h-16 w-16 object-cover" loading="lazy">
                            @else
                                {{ strtoupper(mb_substr($page->title ?: $page->username, 0, 1)) }}
                            @endif
                        </div>
                        <h2 class="mt-3 font-semibold leading-snug truncate">{{ $page->title ?: ('@' . $page->username) }}</h2>
                        <div class="text-sm text-slate-400 truncate">{{ '@' . $page->username }}</div>
                        @if($page->bio)
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 line-clamp-2 flex-1">{{ $page->bio }}</p>
                        @endif
                        <div class="mt-3 inline-flex items-center gap-1.5 text-xs text-slate-400">
                            <x-icon name="eye" class="h-4 w-4"/>
                            {{ __(':n views', ['n' => format_number($page->views ?? 0)]) }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $pages->links() }}</div>
    @endif
</div>
@endsection
