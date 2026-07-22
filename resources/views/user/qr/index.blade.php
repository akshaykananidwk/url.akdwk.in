@extends('layouts.app')

@section('title', __('QR Codes') . ' — ' . site_name())
@section('page-title', __('QR Codes'))

@section('content')
<div class="space-y-5">

    {{-- Create form --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Create a QR code') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('QR codes are dynamic — you can swap the destination link after printing, and scans are tracked.') }}</p>

        <form method="POST" action="{{ route('qr.store') }}" enctype="multipart/form-data" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @csrf
            <x-field name="name" :label="__('Name')">
                <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="{{ __('Store window poster') }}">
            </x-field>
            <x-field name="link_id" :label="__('Link')" class="sm:col-span-1 lg:col-span-2">
                <select name="link_id" required class="input">
                    <option value="">{{ __('Choose a link…') }}</option>
                    @foreach($links as $l)
                        <option value="{{ $l->id }}" @selected(old('link_id') == $l->id)>{{ $l->alias }} — {{ \Illuminate\Support\Str::limit($l->destination, 50) }}</option>
                    @endforeach
                </select>
            </x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field name="fg" :label="__('Foreground')">
                    <input type="color" name="fg" value="{{ old('fg', '#000000') }}" class="input !p-1 h-11">
                </x-field>
                <x-field name="bg" :label="__('Background')">
                    <input type="color" name="bg" value="{{ old('bg', '#ffffff') }}" class="input !p-1 h-11">
                </x-field>
            </div>
            <x-field name="ec_level" :label="__('Error correction')">
                <select name="ec_level" class="input">
                    <option value="low" @selected(old('ec_level') === 'low')>{{ __('Low') }}</option>
                    <option value="medium" @selected(old('ec_level', 'medium') === 'medium')>{{ __('Medium') }}</option>
                    <option value="quartile" @selected(old('ec_level') === 'quartile')>{{ __('Quartile') }}</option>
                    <option value="high" @selected(old('ec_level') === 'high')>{{ __('High') }}</option>
                </select>
            </x-field>
            <x-field name="frame_text" :label="__('Frame text')" :help="__('Short caption under the code, e.g. Scan me.')">
                <input type="text" name="frame_text" value="{{ old('frame_text') }}" maxlength="40" class="input" placeholder="{{ __('Scan me') }}">
            </x-field>
            <x-field name="logo" :label="__('Center logo')">
                <input type="file" name="logo" accept="image/*" class="input !p-2.5">
            </x-field>
            <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
                <button type="submit" class="btn-primary"><x-icon name="qr" class="h-4 w-4"/> {{ __('Create QR code') }}</button>
            </div>
        </form>
    </div>

    {{-- QR grid --}}
    @if($codes->isEmpty())
        <x-empty-state icon="qr" :title="__('No QR codes yet')" :description="__('Create your first QR code above and download it as PNG, SVG or PDF.')"/>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($codes as $code)
                <div class="card p-4 flex flex-col items-center text-center">
                    <div class="rounded-xl bg-white p-2 ring-1 ring-slate-200 dark:ring-slate-700">
                        <img src="{{ route('qr.render', [$code, 'png']) }}" alt="{{ $code->name }}" loading="lazy" class="h-28 w-28 sm:h-32 sm:w-32 object-contain">
                    </div>
                    <p class="mt-3 font-semibold text-sm truncate w-full">{{ $code->name }}</p>
                    <p class="text-xs text-slate-500 truncate w-full">{{ $code->link?->shortUrl() ?? __('Link deleted') }}</p>
                    <span class="badge-brand mt-2">{{ format_number($code->link?->qr_scans_count ?? 0) }} {{ __('scans') }}</span>

                    <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                        @foreach(['png', 'svg', 'pdf'] as $format)
                            <a href="{{ route('qr.render', [$code, $format]) . '?download=1' }}" class="btn-secondary btn-sm uppercase">{{ $format }}</a>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-1.5">
                        <button type="button" class="btn-ghost btn-sm" @click="$dispatch('open-modal', 'edit-qr-{{ $code->id }}')" aria-label="{{ __('Edit') }}">
                            <x-icon name="pencil" class="h-4 w-4"/>
                        </button>
                        <x-confirm :action="route('qr.destroy', $code)" method="DELETE" :title="__('Delete this QR code?')"
                                   :message="__('Printed copies will stop working once deleted.')">
                            <button type="button" class="btn-ghost btn-sm text-rose-600 dark:text-rose-400" aria-label="{{ __('Delete') }}">
                                <x-icon name="trash" class="h-4 w-4"/>
                            </button>
                        </x-confirm>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $codes->links() }}</div>

        @foreach($codes as $code)
            <x-modal name="edit-qr-{{ $code->id }}" :title="__('Edit QR code')">
                <form method="POST" action="{{ route('qr.update', $code) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-field name="name" :label="__('Name')">
                        <input type="text" name="name" value="{{ $code->name }}" required class="input">
                    </x-field>
                    <x-field name="link_id" :label="__('Link')">
                        <select name="link_id" required class="input">
                            @foreach($links as $l)
                                <option value="{{ $l->id }}" @selected($code->link_id == $l->id)>{{ $l->alias }} — {{ \Illuminate\Support\Str::limit($l->destination, 50) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <div class="grid grid-cols-2 gap-4">
                        <x-field name="fg" :label="__('Foreground')">
                            <input type="color" name="fg" value="{{ $code->options['fg'] ?? '#000000' }}" class="input !p-1 h-11">
                        </x-field>
                        <x-field name="bg" :label="__('Background')">
                            <input type="color" name="bg" value="{{ $code->options['bg'] ?? '#ffffff' }}" class="input !p-1 h-11">
                        </x-field>
                    </div>
                    <x-field name="ec_level" :label="__('Error correction')">
                        <select name="ec_level" class="input">
                            @foreach(['low' => __('Low'), 'medium' => __('Medium'), 'quartile' => __('Quartile'), 'high' => __('High')] as $level => $label)
                                <option value="{{ $level }}" @selected(($code->options['ec_level'] ?? 'medium') === $level)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="frame_text" :label="__('Frame text')">
                        <input type="text" name="frame_text" value="{{ $code->options['frame_text'] ?? '' }}" maxlength="40" class="input">
                    </x-field>
                    <x-field name="logo" :label="__('Center logo')" :help="__('Upload a new image to replace the current logo.')">
                        <input type="file" name="logo" accept="image/*" class="input !p-2.5">
                    </x-field>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endif
</div>
@endsection
