@extends('layouts.admin')

@section('title', __('Translate :name', ['name' => $language->name]) . ' — ' . site_name())
@section('page-title', __('Translate :name', ['name' => $language->name]))

@php
    $translated = count(array_filter($strings, fn ($v) => $v !== ''));
    $total = count($strings);
@endphp

@section('content')
<div class="space-y-5" x-data="{ q: '' }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.languages.index') }}" class="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline min-h-touch">
            <x-icon name="chevron-right" class="h-4 w-4 rotate-180 rtl:rotate-0"/> {{ __('Back to languages') }}
        </a>
        <span class="badge-brand">{{ __(':done of :total translated', ['done' => $translated, 'total' => $total]) }}</span>
    </div>

    <div class="card card-pad">
        <label for="filter" class="label">{{ __('Filter strings') }}</label>
        <input type="search" id="filter" x-model="q" class="input" placeholder="{{ __('Type to filter…') }}">
        <p class="help">{{ __('Untranslated strings are marked with an amber border. Empty fields fall back to English.') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.languages.update', $language) }}">
        @csrf
        @method('PUT')

        <div class="card divide-y divide-slate-100 dark:divide-slate-800">
            @foreach($strings as $key => $value)
                <div class="p-4 border-s-4 {{ $value === '' ? 'border-amber-400' : 'border-transparent' }}"
                     data-k="{{ mb_strtolower($key . ' ' . $value) }}"
                     x-show="q === '' || $el.dataset.k.includes(q.toLowerCase())">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-1.5">{{ $key }}</p>
                    <input type="text" name="strings[{{ $key }}]" value="{{ $value }}" class="input"
                           dir="{{ $language->rtl ? 'rtl' : 'ltr' }}" placeholder="{{ $english[$key] ?? $key }}">
                </div>
            @endforeach
        </div>

        <div class="sticky bottom-20 lg:bottom-4 mt-5 flex justify-end">
            <button class="btn-primary shadow-lg">{{ __('Save translations') }}</button>
        </div>
    </form>
</div>
@endsection
