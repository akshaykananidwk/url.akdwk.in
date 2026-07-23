@extends('layouts.landing')

@section('title', __('Link Expander') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Link Expander') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Not sure where a short link leads? Paste it here and we will safely follow the redirects and show you the final destination — plus every hop along the way — before you click.') }}
        </p>
    </div>

    <div class="mt-8 card card-pad max-w-2xl">
        <form method="POST" action="{{ route('ftools.expander.check') }}" class="flex flex-col sm:flex-row gap-2">
            @csrf
            <input type="url" name="url" value="{{ old('url') }}" required inputmode="url"
                   placeholder="{{ __('https://bit.ly/example') }}"
                   class="input flex-1" aria-label="{{ __('Short URL to expand') }}">
            <button type="submit" class="btn-primary shrink-0">
                <x-icon name="search" class="h-4 w-4"/> {{ __('Expand link') }}
            </button>
        </form>
        @error('url')
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
        @enderror
    </div>

    @if(session('expanded'))
        @php($x = session('expanded'))
        <div class="mt-6 card card-pad max-w-2xl">
            @if(!empty($x['error']))
                <div class="flex items-start gap-3 text-sm text-rose-600 dark:text-rose-400">
                    <x-icon name="warning" class="h-5 w-5 shrink-0"/>
                    <span>{{ $x['error'] }}</span>
                </div>
            @else
                <div class="flex items-center gap-2 mb-4">
                    <span class="badge-green">{{ __('HTTP :status', ['status' => $x['status']]) }}</span>
                    <span class="badge-amber">{{ trans_choice(':count redirect|:count redirects', $x['redirects'], ['count' => $x['redirects']]) }}</span>
                </div>

                <div class="mb-4">
                    <div class="text-xs text-slate-400 mb-1">{{ __('Final destination') }}</div>
                    <a href="{{ $x['final'] }}" target="_blank" rel="noopener nofollow" class="text-sm font-semibold text-brand-600 hover:underline break-all">{{ $x['final'] }}</a>
                </div>

                @if(count($x['chain']) > 1)
                    <div class="text-xs text-slate-400 mb-2">{{ __('Redirect chain') }}</div>
                    <ol class="space-y-2">
                        @foreach($x['chain'] as $i => $hop)
                            <li class="flex items-start gap-2.5 text-sm">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-500">{{ $i + 1 }}</span>
                                <span class="min-w-0 break-all {{ $loop->last ? 'font-semibold text-slate-700 dark:text-slate-200' : 'text-slate-500 dark:text-slate-400' }}">{{ $hop }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            @endif
        </div>
    @endif

    <div class="mt-8 max-w-2xl space-y-3 text-sm text-slate-500 dark:text-slate-400">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ __('Why expand a short link?') }}</h2>
        <p>{{ __('Short links hide their true destination, which scammers sometimes abuse to disguise phishing pages. Expanding a link first lets you confirm it goes somewhere you trust before you visit it.') }}</p>
    </div>

    <div class="mt-8 card card-pad text-center max-w-2xl bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Build links people can trust') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Use your own branded domain and a public preview page so your audience always knows where they are headed.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Start free') }}</a>
    </div>

</div>
@endsection
