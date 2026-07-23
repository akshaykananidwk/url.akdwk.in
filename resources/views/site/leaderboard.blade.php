@extends('layouts.landing')

@section('title', __('Leaderboard') . ' — ' . site_name())

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">

    {{-- Hero --}}
    <div class="text-center max-w-2xl mx-auto">
        <span class="inline-flex items-center gap-1.5 badge-amber">
            <x-icon name="bolt" class="h-4 w-4"/> {{ __('Live rankings') }}
        </span>
        <h1 class="mt-4 text-3xl sm:text-4xl font-bold tracking-tight">{{ __('The Leaderboard') }}</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">
            {{ __('The most-clicked public links and top creators on :name. Make yours public to climb the ranks.', ['name' => site_name()]) }}
        </p>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">

        {{-- Top links --}}
        <section class="card card-pad">
            <div class="flex items-center gap-2 mb-4">
                <x-icon name="link" class="h-5 w-5 text-brand-600"/>
                <h2 class="text-lg font-semibold">{{ __('Top Links') }}</h2>
            </div>

            @forelse($topLinks as $i => $link)
                @php($rank = $i + 1)
                <div class="flex items-center gap-3 py-3 {{ ! $loop->last ? 'border-b border-slate-100 dark:border-slate-800' : '' }}">
                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $rank === 1,
                        'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-200' => $rank === 2,
                        'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' => $rank === 3,
                        'bg-slate-50 text-slate-400 dark:bg-slate-800 dark:text-slate-500' => $rank > 3,
                    ])>
                        @if($rank <= 3)🏅@else{{ $rank }}@endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium">{{ $link->title ?: $link->shortUrl() }}</div>
                        <a href="{{ route('stats.public', $link->alias) }}" class="truncate block text-xs text-brand-600 hover:underline">
                            {{ $link->shortUrl() }}
                        </a>
                    </div>
                    <div class="text-end shrink-0">
                        <div class="font-bold tabular-nums">{{ format_number($link->clicks_count) }}</div>
                        <div class="text-[11px] text-slate-400">{{ __('clicks') }}</div>
                    </div>
                </div>
            @empty
                <x-empty-state icon="chart" :title="__('No ranked links yet')"
                    :description="__('Enable public stats on a link to appear here.')"/>
            @endforelse
        </section>

        {{-- Top creators --}}
        <section class="card card-pad">
            <div class="flex items-center gap-2 mb-4">
                <x-icon name="users" class="h-5 w-5 text-brand-600"/>
                <h2 class="text-lg font-semibold">{{ __('Top Creators') }}</h2>
            </div>

            @forelse($topCreators as $i => $creator)
                @php($rank = $i + 1)
                <div class="flex items-center gap-3 py-3 {{ ! $loop->last ? 'border-b border-slate-100 dark:border-slate-800' : '' }}">
                    <span @class([
                        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold',
                        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $rank === 1,
                        'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-200' => $rank === 2,
                        'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' => $rank === 3,
                        'bg-slate-50 text-slate-400 dark:bg-slate-800 dark:text-slate-500' => $rank > 3,
                    ])>
                        @if($rank <= 3)🏅@else{{ $rank }}@endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium">{{ $creator->name }}</div>
                        <div class="text-xs text-slate-400">{{ trans_choice('{1} :count link|[2,*] :count links', (int) $creator->links_count, ['count' => format_number($creator->links_count)]) }}</div>
                    </div>
                    <div class="text-end shrink-0">
                        <div class="font-bold tabular-nums">{{ format_number($creator->total_clicks) }}</div>
                        <div class="text-[11px] text-slate-400">{{ __('clicks') }}</div>
                    </div>
                </div>
            @empty
                <x-empty-state icon="users" :title="__('No ranked creators yet')"
                    :description="__('Be the first to top the charts.')"/>
            @endforelse
        </section>
    </div>

    {{-- CTA --}}
    <div class="mt-10 card card-pad text-center">
        <h3 class="text-xl font-semibold">{{ __('Want to see your links here?') }}</h3>
        <p class="mt-2 text-slate-500 dark:text-slate-400">{{ __('Create a short link, turn on public stats, and start climbing.') }}</p>
        <div class="mt-4">
            <a href="{{ route('register') }}" class="btn-primary">
                <x-icon name="plus" class="h-5 w-5"/> {{ __('Get started free') }}
            </a>
        </div>
    </div>
</div>
@endsection
