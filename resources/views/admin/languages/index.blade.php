@extends('layouts.admin')

@section('title', __('Languages') . ' — ' . site_name())
@section('page-title', __('Languages'))

@section('content')
<div class="space-y-5">

    {{-- Add language --}}
    <form method="POST" action="{{ route('admin.languages.store') }}" class="card card-pad space-y-4">
        @csrf
        <h2 class="font-semibold">{{ __('Add language') }}</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <x-field name="code" :label="__('Code')" :help="__('ISO code, e.g. \'fr\' or \'pt-BR\'')">
                <input type="text" id="code" name="code" value="{{ old('code') }}" class="input" placeholder="fr" required>
            </x-field>
            <x-field name="name" :label="__('Name')">
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="input" placeholder="Français" required>
            </x-field>
            <div class="pb-1">
                <x-toggle name="rtl" :checked="(bool) old('rtl')" :label="__('Right-to-left')"/>
            </div>
            <div class="pb-0.5">
                <button class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add language') }}</button>
            </div>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Language') }}</th>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Direction') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($languages as $language)
                    <tr>
                        <td data-label="{{ __('Language') }}">
                            <span class="inline-flex items-center gap-2 font-medium">
                                {{ $language->name }}
                                @if($language->is_default)<span class="badge-brand">{{ __('Default') }}</span>@endif
                            </span>
                        </td>
                        <td data-label="{{ __('Code') }}"><span class="font-mono text-xs">{{ $language->code }}</span></td>
                        <td data-label="{{ __('Direction') }}">
                            @if($language->rtl)<span class="badge-amber">{{ __('RTL') }}</span>
                            @else<span class="text-xs text-slate-400">{{ __('LTR') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Status') }}">
                            @if($language->active)<span class="badge-green">{{ __('Active') }}</span>
                            @else<span class="badge-gray">{{ __('Disabled') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5 flex-wrap">
                                <a href="{{ route('admin.languages.edit', $language) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-4 w-4"/> {{ __('Translate') }}</a>
                                @unless($language->is_default)
                                    <form method="POST" action="{{ route('admin.languages.toggle', $language) }}">
                                        @csrf
                                        <button class="btn-secondary btn-sm">{{ $language->active ? __('Disable') : __('Enable') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.languages.default', $language) }}">
                                        @csrf
                                        <button class="btn-ghost btn-sm">{{ __('Set default') }}</button>
                                    </form>
                                    @if($language->code !== 'en')
                                        <x-confirm :action="route('admin.languages.destroy', $language)" method="DELETE"
                                                   :title="__('Delete this language?')"
                                                   :message="__('Its translation file stays on disk in case you re-add it later.')">
                                            <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                        </x-confirm>
                                    @endif
                                @endunless
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No languages yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
