@extends('layouts.landing')

@section('title', __('Bulk URL Shortener') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Bulk URL Shortener') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Paste up to 20 links — one per line — and shorten them all at once. Great for newsletters, spreadsheets and campaign batches. Copy each short link with a single tap.') }}
        </p>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6 items-start">
        <div class="card card-pad">
            <form method="POST" action="{{ route('ftools.bulk.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="urls" class="block text-sm font-medium mb-1.5">{{ __('Long URLs (one per line, max 20)') }}</label>
                    <textarea id="urls" name="urls" rows="10" required
                              class="input w-full font-mono text-sm"
                              placeholder="https://example.com/page-one&#10;https://example.com/page-two">{{ old('urls') }}</textarea>
                    @error('urls')
                        <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary w-full">
                    <x-icon name="link" class="h-4 w-4"/> {{ __('Shorten all') }}
                </button>
                <p class="text-xs text-slate-400">{{ __('Guest links expire after :days days. Sign up free to keep them forever.', ['days' => setting('guest_link_days', 30)]) }}</p>
            </form>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-medium mb-3">{{ __('Your short links') }}</h2>
            @if(session('results'))
                <ul class="space-y-2">
                    @foreach(session('results') as $r)
                        <li x-data="{ copied: false }" class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 px-3 py-2.5">
                            @if($r['short'])
                                <div class="flex items-center gap-2">
                                    <a href="{{ $r['short'] }}" target="_blank" rel="noopener" class="flex-1 min-w-0 truncate text-sm font-semibold text-brand-600 hover:underline">{{ $r['short'] }}</a>
                                    <button type="button" class="btn-secondary btn-sm shrink-0"
                                            @click="navigator.clipboard.writeText('{{ $r['short'] }}').then(() => { copied = true; setTimeout(() => copied = false, 1500); })">
                                        <x-icon name="copy" class="h-4 w-4"/>
                                        <span x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span>
                                    </button>
                                </div>
                                <div class="mt-1 text-xs text-slate-400 truncate">{{ $r['original'] }}</div>
                            @else
                                <div class="flex items-center gap-2 text-sm text-rose-600 dark:text-rose-400">
                                    <x-icon name="warning" class="h-4 w-4 shrink-0"/>
                                    <span class="truncate">{{ $r['original'] }}</span>
                                </div>
                                <div class="mt-1 text-xs text-slate-400">{{ $r['error'] ?? __('Could not be shortened.') }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center justify-center text-center py-10 text-slate-400">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <x-icon name="link" class="h-7 w-7"/>
                    </span>
                    <p class="mt-3 text-sm">{{ __('Your shortened links will show up here.') }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-10 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Need to shorten thousands?') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Import a CSV, use the API, add custom aliases and track every click from your dashboard.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Create free account') }}</a>
    </div>

</div>
@endsection
