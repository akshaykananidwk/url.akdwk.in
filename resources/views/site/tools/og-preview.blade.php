@extends('layouts.landing')

@section('title', __('Social Share Preview') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Social Share Preview') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('See exactly how your link will look when shared on Facebook, LinkedIn, X and other platforms. We read the page\'s Open Graph tags — title, description and image — and render a live preview card.') }}
        </p>
    </div>

    <div class="mt-8 card card-pad max-w-2xl">
        <form method="POST" action="{{ route('ftools.og.check') }}" class="flex flex-col sm:flex-row gap-2">
            @csrf
            <input type="url" name="url" value="{{ old('url') }}" required inputmode="url"
                   placeholder="{{ __('https://example.com/article') }}"
                   class="input flex-1" aria-label="{{ __('URL to preview') }}">
            <button type="submit" class="btn-primary shrink-0">
                <x-icon name="eye" class="h-4 w-4"/> {{ __('Preview') }}
            </button>
        </form>
        @error('url')
            <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
        @enderror
    </div>

    @if(session('og'))
        @php($og = session('og'))
        <div class="mt-6 max-w-md">
            @if(!empty($og['error']))
                <div class="card card-pad flex items-start gap-3 text-sm text-rose-600 dark:text-rose-400">
                    <x-icon name="warning" class="h-5 w-5 shrink-0"/>
                    <span>{{ $og['error'] }}</span>
                </div>
            @else
                {{-- Facebook-style share card --}}
                <div class="rounded-xl overflow-hidden ring-1 ring-slate-200 dark:ring-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                    @if(!empty($og['image']))
                        <div class="aspect-[1.91/1] w-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <img src="{{ $og['image'] }}" alt="" class="h-full w-full object-cover" referrerpolicy="no-referrer" loading="lazy">
                        </div>
                    @else
                        <div class="aspect-[1.91/1] w-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                            <x-icon name="eye" class="h-8 w-8"/>
                        </div>
                    @endif
                    <div class="p-3.5 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-xs uppercase tracking-wide text-slate-400 truncate">{{ $og['site'] ?: parse_url($og['url'], PHP_URL_HOST) }}</div>
                        <div class="mt-1 font-semibold text-sm text-slate-800 dark:text-slate-100 line-clamp-2">{{ $og['title'] ?: __('(No title found)') }}</div>
                        @if(!empty($og['description']))
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ $og['description'] }}</div>
                        @endif
                    </div>
                </div>

                {{-- Raw tags --}}
                <dl class="mt-4 card card-pad text-sm space-y-2">
                    @foreach(['title' => __('Title'), 'description' => __('Description'), 'image' => __('Image'), 'site' => __('Site name')] as $k => $label)
                        <div class="flex gap-3">
                            <dt class="w-24 shrink-0 text-slate-400">{{ $label }}</dt>
                            <dd class="min-w-0 break-all text-slate-600 dark:text-slate-300">{{ $og[$k] ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    @endif

    <div class="mt-8 card card-pad text-center max-w-2xl bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('Control your own share card') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Set a custom title, description and image for every short link so it always looks great — no matter the destination.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Create free account') }}</a>
    </div>

</div>
@endsection
