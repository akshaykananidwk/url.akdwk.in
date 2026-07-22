@extends('layouts.app')

@section('title', __('Tools') . ' — ' . site_name())
@section('page-title', __('Tools'))

@section('content')
<div class="space-y-5">

    @if(session('created_link'))
        <div class="card card-pad !py-4 flex flex-wrap items-center gap-3 ring-2 ring-brand-500">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
                <x-icon name="check" class="h-5 w-5"/>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-slate-500">{{ __('Your new short link') }}</p>
                <p class="font-semibold truncate">{{ session('created_link') }}</p>
            </div>
            <button type="button" class="btn-primary btn-sm" onclick="copyText(@js(session('created_link')))">
                <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy') }}
            </button>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-5">
        {{-- File to link --}}
        <div class="card card-pad">
            <h2 class="font-semibold mb-1">{{ __('File to link') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('Upload a file and get a short link that serves it.') }}</p>
            <form method="POST" action="{{ route('tools.file') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <x-field name="file" :label="__('File')" :help="__('Max 20 MB. PDF, images, archives, documents, audio, video.')">
                    <input type="file" name="file" required class="input !p-2.5">
                </x-field>
                <button type="submit" class="btn-primary w-full"><x-icon name="upload" class="h-4 w-4"/> {{ __('Upload & shorten') }}</button>
            </form>
        </div>

        {{-- vCard --}}
        <div class="card card-pad">
            <h2 class="font-semibold mb-1">{{ __('vCard link') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('A short link that saves your contact card to the phone.') }}</p>
            <form method="POST" action="{{ route('tools.vcard') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="first_name" :label="__('First name')">
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required class="input">
                    </x-field>
                    <x-field name="last_name" :label="__('Last name')">
                        <input type="text" name="last_name" value="{{ old('last_name') }}" class="input">
                    </x-field>
                </div>
                <x-field name="organization" :label="__('Organization')">
                    <input type="text" name="organization" value="{{ old('organization') }}" class="input">
                </x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="phone" :label="__('Phone')">
                        <input type="tel" name="phone" value="{{ old('phone') }}" class="input">
                    </x-field>
                    <x-field name="email" :label="__('Email')">
                        <input type="email" name="email" value="{{ old('email') }}" class="input">
                    </x-field>
                </div>
                <x-field name="website" :label="__('Website')">
                    <input type="text" name="website" value="{{ old('website') }}" class="input" placeholder="https://" inputmode="url">
                </x-field>
                <x-field name="address" :label="__('Address')">
                    <input type="text" name="address" value="{{ old('address') }}" class="input">
                </x-field>
                <button type="submit" class="btn-primary w-full"><x-icon name="user" class="h-4 w-4"/> {{ __('Create vCard link') }}</button>
            </form>
        </div>

        {{-- WhatsApp --}}
        <div class="card card-pad">
            <h2 class="font-semibold mb-1">{{ __('WhatsApp link') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('A click-to-chat link that opens WhatsApp with a prefilled message.') }}</p>
            <form method="POST" action="{{ route('tools.whatsapp') }}" class="space-y-3">
                @csrf
                <x-field name="phone" :label="__('Phone number')" :help="__('Include the country code, e.g. +1 555 000 1234.')">
                    <input type="tel" name="phone" value="{{ old('phone') }}" required class="input" placeholder="+1 555 000 1234">
                </x-field>
                <x-field name="message" :label="__('Prefilled message')">
                    <textarea name="message" rows="3" class="input" placeholder="{{ __('Hi! I would like to know more about…') }}">{{ old('message') }}</textarea>
                </x-field>
                <button type="submit" class="btn-primary w-full"><x-icon name="phone" class="h-4 w-4"/> {{ __('Create WhatsApp link') }}</button>
            </form>
        </div>
    </div>

    {{-- Share helper --}}
    <div class="card card-pad" x-data="{ url: '' }">
        <h2 class="font-semibold mb-1">{{ __('Share a link') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('Paste any URL and open it in your favourite network with one tap.') }}</p>
        <input type="url" class="input mb-4" x-model="url" placeholder="https://example.com/abc" inputmode="url" aria-label="{{ __('URL to share') }}">
        <div class="flex flex-wrap gap-2" :class="url ? '' : 'opacity-50 pointer-events-none'">
            <a class="btn-secondary btn-sm" :href="'mailto:?body=' + encodeURIComponent(url)"><x-icon name="mail" class="h-4 w-4"/> {{ __('Email') }}</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://wa.me/?text=' + encodeURIComponent(url)">WhatsApp</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url)">X / Twitter</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)">Facebook</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://t.me/share/url?url=' + encodeURIComponent(url)">Telegram</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://www.reddit.com/submit?url=' + encodeURIComponent(url)">Reddit</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(url)">Pinterest</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url)">LinkedIn</a>
            <a class="btn-secondary btn-sm" target="_blank" rel="noopener" :href="'https://www.threads.net/intent/post?text=' + encodeURIComponent(url)">Threads</a>
        </div>
    </div>
</div>
@endsection
