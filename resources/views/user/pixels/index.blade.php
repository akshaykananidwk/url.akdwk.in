@extends('layouts.app')

@section('title', __('Pixels') . ' — ' . site_name())
@section('page-title', __('Pixels'))

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Fire retargeting pixels on your short links to build audiences.') }}</p>
        <button type="button" class="btn-primary btn-sm shrink-0" @click="$dispatch('open-modal', 'create-pixel')">
            <x-icon name="plus" class="h-4 w-4"/> {{ __('New pixel') }}
        </button>
    </div>

    @if($pixels->isEmpty())
        <x-empty-state icon="target" :title="__('No pixels yet')" :description="__('Add your first retargeting pixel and attach it to any link.')">
            <button type="button" class="btn-primary btn-sm" @click="$dispatch('open-modal', 'create-pixel')">{{ __('Create pixel') }}</button>
        </x-empty-state>
    @else
        <div class="card overflow-hidden">
            <table class="table-cards">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Links') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pixels as $pixel)
                        <tr>
                            <td data-label="{{ __('Name') }}"><span class="font-medium">{{ $pixel->name }}</span></td>
                            <td data-label="{{ __('Type') }}"><span class="badge-gray">{{ $pixel->typeLabel() }}</span></td>
                            <td data-label="{{ __('Links') }}">{{ format_number($pixel->links_count) }}</td>
                            <td data-label="{{ __('Actions') }}">
                                <span class="flex gap-1.5 justify-end">
                                    <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'edit-pixel-{{ $pixel->id }}')">
                                        <x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}
                                    </button>
                                    <x-confirm :action="route('pixels.destroy', $pixel)" method="DELETE" :title="__('Delete this pixel?')"
                                               :message="__('It will be detached from all links.')">
                                        <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/></button>
                                    </x-confirm>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $pixels->links() }}</div>

        @foreach($pixels as $pixel)
            <x-modal name="edit-pixel-{{ $pixel->id }}" :title="__('Edit pixel')">
                <form method="POST" action="{{ route('pixels.update', $pixel) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-field name="name" :label="__('Name')">
                        <input type="text" name="name" value="{{ $pixel->name }}" required class="input">
                    </x-field>
                    <x-field name="type" :label="__('Type')">
                        <select name="type" class="input">
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected($pixel->type === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="value" :label="__('Pixel value')" :help="__('Pixel ID, or full HTML for the custom type.')">
                        <textarea name="value" rows="3" required class="input font-mono text-xs">{{ $pixel->value }}</textarea>
                    </x-field>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endif

    {{-- Create modal --}}
    <x-modal name="create-pixel" :title="__('New pixel')">
        <form method="POST" action="{{ route('pixels.store') }}" class="space-y-4">
            @csrf
            <x-field name="name" :label="__('Name')">
                <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="{{ __('My Meta pixel') }}">
            </x-field>
            <x-field name="type" :label="__('Type')">
                <select name="type" class="input">
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
            <x-field name="value" :label="__('Pixel value')" :help="__('Pixel ID, or full HTML for the custom type.')">
                <textarea name="value" rows="3" required class="input font-mono text-xs" placeholder="1234567890">{{ old('value') }}</textarea>
            </x-field>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                <button type="submit" class="btn-primary">{{ __('Create') }}</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
