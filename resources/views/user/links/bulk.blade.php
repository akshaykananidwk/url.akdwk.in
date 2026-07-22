@extends('layouts.app')

@section('title', __('Bulk shorten') . ' — ' . site_name())
@section('page-title', __('Bulk shorten'))

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    @if(isset($results) && count($results))
        <div class="card card-pad">
            <h2 class="font-semibold mb-3">{{ __(':count links created', ['count' => count($results)]) }}</h2>
            <div class="space-y-2">
                @foreach($results as $created)
                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{ $created->shortUrl() }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $created->destination }}</span>
                        </span>
                        <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js($created->shortUrl()))" aria-label="{{ __('Copy link') }}">
                            <x-icon name="copy" class="h-4 w-4"/>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($failures) && count($failures))
        <div class="card card-pad">
            <h2 class="font-semibold mb-3 text-rose-600 dark:text-rose-400">{{ __(':count URLs failed', ['count' => count($failures)]) }}</h2>
            <div class="space-y-2">
                @foreach($failures as $url => $reason)
                    <div class="rounded-xl bg-rose-50 dark:bg-rose-950/40 px-3 py-2.5">
                        <p class="text-sm font-medium truncate">{{ $url }}</p>
                        <p class="text-xs text-rose-600 dark:text-rose-400">{{ $reason }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Shorten many URLs at once') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('Paste up to 200 URLs, one per line. Each will get an automatically generated alias.') }}</p>

        <form method="POST" action="{{ route('links.bulk') }}" class="space-y-4">
            @csrf
            <x-field name="urls">
                <textarea name="urls" rows="10" required class="input font-mono text-xs"
                          placeholder="https://example.com/first-page&#10;https://example.com/second-page">{{ old('urls') }}</textarea>
            </x-field>
            <div class="flex justify-end gap-2">
                <a href="{{ route('links.index') }}" class="btn-secondary">{{ __('Back to links') }}</a>
                <button type="submit" class="btn-primary"><x-icon name="bolt" class="h-4 w-4"/> {{ __('Shorten all') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
