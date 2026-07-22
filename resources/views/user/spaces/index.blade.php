@extends('layouts.app')

@section('title', __('Spaces') . ' — ' . site_name())
@section('page-title', __('Spaces'))

@php
    $spaceIcons = ['folder', 'megaphone', 'share', 'target', 'globe', 'bolt', 'sparkles'];
@endphp

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Organize your links into folders for campaigns, clients or projects.') }}</p>
        <button type="button" class="btn-primary btn-sm shrink-0" @click="$dispatch('open-modal', 'create-space')">
            <x-icon name="plus" class="h-4 w-4"/> {{ __('New space') }}
        </button>
    </div>

    @if($spaces->isEmpty())
        <x-empty-state icon="folder" :title="__('No spaces yet')" :description="__('Create a space to group related links together.')">
            <button type="button" class="btn-primary btn-sm" @click="$dispatch('open-modal', 'create-space')">{{ __('Create space') }}</button>
        </x-empty-state>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($spaces as $space)
                <div class="card card-pad !p-4 sm:!p-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white" style="background-color: {{ $space->color }}">
                            <x-icon :name="$space->icon" class="h-5 w-5"/>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold truncate">{{ $space->name }}</p>
                            <a href="{{ route('links.index', ['space' => $space->id]) }}" class="text-xs text-slate-500 hover:text-brand-600">
                                {{ trans_choice(':count link|:count links', $space->links_count, ['count' => format_number($space->links_count)]) }}
                            </a>
                        </div>
                        <span class="h-3 w-3 rounded-full shrink-0" style="background-color: {{ $space->color }}" aria-hidden="true"></span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'edit-space-{{ $space->id }}')">
                            <x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}
                        </button>
                        <x-confirm :action="route('spaces.destroy', $space)" method="DELETE" :title="__('Delete this space?')"
                                   :message="__('Links inside will not be deleted — they simply lose the space.')">
                            <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/> {{ __('Delete') }}</button>
                        </x-confirm>
                    </div>
                </div>

                {{-- Edit modal --}}
                <x-modal name="edit-space-{{ $space->id }}" :title="__('Edit space')">
                    <form method="POST" action="{{ route('spaces.update', $space) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <x-field name="name" :label="__('Name')">
                            <input type="text" name="name" value="{{ $space->name }}" required class="input">
                        </x-field>
                        <div class="grid grid-cols-2 gap-4">
                            <x-field name="color" :label="__('Color')">
                                <input type="color" name="color" value="{{ $space->color }}" class="input !p-1 h-11">
                            </x-field>
                            <x-field name="icon" :label="__('Icon')">
                                <select name="icon" class="input">
                                    @foreach($spaceIcons as $icon)
                                        <option value="{{ $icon }}" @selected($space->icon === $icon)>{{ ucfirst($icon) }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </x-modal>
            @endforeach
        </div>

        <div>{{ $spaces->links() }}</div>
    @endif

    {{-- Create modal --}}
    <x-modal name="create-space" :title="__('New space')">
        <form method="POST" action="{{ route('spaces.store') }}" class="space-y-4">
            @csrf
            <x-field name="name" :label="__('Name')">
                <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="{{ __('Marketing') }}">
            </x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field name="color" :label="__('Color')">
                    <input type="color" name="color" value="{{ old('color', '#6366f1') }}" class="input !p-1 h-11">
                </x-field>
                <x-field name="icon" :label="__('Icon')">
                    <select name="icon" class="input">
                        @foreach($spaceIcons as $icon)
                            <option value="{{ $icon }}" @selected(old('icon') === $icon)>{{ ucfirst($icon) }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                <button type="submit" class="btn-primary">{{ __('Create') }}</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
