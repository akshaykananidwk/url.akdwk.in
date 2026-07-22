@extends('layouts.admin')

@section('title', __('Blocklist') . ' — ' . site_name())
@section('page-title', __('Blocklist'))

@section('content')
<div class="grid lg:grid-cols-2 gap-5 items-start">

    {{-- Blocked domains --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Blocked domains') }}</h2>
        <p class="help mb-4">{{ __('Links pointing to these destination domains are rejected.') }}</p>

        <form method="POST" action="{{ route('admin.blocklist.domains.store') }}" class="space-y-3 mb-5">
            @csrf
            <div class="flex flex-col sm:flex-row gap-3">
                <x-field name="domain" class="flex-1">
                    <input type="text" name="domain" value="{{ old('domain') }}" class="input" placeholder="spam-site.com" required aria-label="{{ __('Domain') }}">
                </x-field>
                <x-field name="reason" class="flex-1">
                    <input type="text" name="reason" value="{{ old('reason') }}" class="input" placeholder="{{ __('Reason (optional)') }}" aria-label="{{ __('Reason') }}">
                </x-field>
                <button class="btn-primary btn-sm shrink-0 self-start"><x-icon name="plus" class="h-4 w-4"/> {{ __('Block') }}</button>
            </div>
        </form>

        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($domains as $d)
                <li class="flex items-center gap-3 py-2 min-h-touch">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ $d->domain }}</span>
                        @if($d->reason)<span class="block truncate text-xs text-slate-500">{{ $d->reason }}</span>@endif
                    </span>
                    <x-confirm :action="route('admin.blocklist.domains.destroy', $d)" method="DELETE"
                               :title="__('Unblock this domain?')" :message="__('New links to it will be allowed again.')" :button="__('Unblock')">
                        <button type="button" class="btn-ghost btn-sm text-rose-600" aria-label="{{ __('Unblock') }}"><x-icon name="x" class="h-4 w-4"/></button>
                    </x-confirm>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500">{{ __('No blocked domains.') }}</li>
            @endforelse
        </ul>
    </div>

    {{-- Blocked words --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Blocked words') }}</h2>
        <p class="help mb-4">{{ __('Aliases and destinations containing these words are rejected.') }}</p>

        <form method="POST" action="{{ route('admin.blocklist.words.store') }}" class="mb-5">
            @csrf
            <div class="flex gap-3">
                <x-field name="word" class="flex-1">
                    <input type="text" name="word" value="{{ old('word') }}" class="input" placeholder="{{ __('Word…') }}" required aria-label="{{ __('Word') }}">
                </x-field>
                <button class="btn-primary btn-sm shrink-0 self-start"><x-icon name="plus" class="h-4 w-4"/> {{ __('Block') }}</button>
            </div>
        </form>

        <div class="flex flex-wrap gap-2">
            @forelse($words as $w)
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 ps-3 pe-1 py-1 text-sm font-medium">
                    {{ $w->word }}
                    <x-confirm :action="route('admin.blocklist.words.destroy', $w)" method="DELETE"
                               :title="__('Remove this word?')" :message="__('It will no longer be blocked.')" :button="__('Remove')">
                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:text-rose-600" aria-label="{{ __('Remove') }}">
                            <x-icon name="x" class="h-3.5 w-3.5"/>
                        </button>
                    </x-confirm>
                </span>
            @empty
                <p class="py-4 text-sm text-slate-500 w-full text-center">{{ __('No blocked words.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
