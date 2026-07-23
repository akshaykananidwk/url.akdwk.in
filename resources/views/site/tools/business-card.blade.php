@extends('layouts.landing')

@section('title', __('Digital Business Card (vCard QR)') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10" x-data="vcardBuilder()">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('Digital Business Card') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Turn your contact details into a vCard people can save with one tap. Fill in the form to download a .vcf file, or generate a QR code that drops you straight into someone\'s address book — perfect for events, email signatures and the back of a printed card.') }}
        </p>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6 items-start">
        <div class="card card-pad space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">{{ __('Full name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="name" placeholder="Jane Doe" class="input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">{{ __('Job title') }}</label>
                    <input type="text" x-model="title" placeholder="Marketing Lead" class="input w-full">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Organization') }}</label>
                <input type="text" x-model="org" placeholder="Acme Inc." class="input w-full">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">{{ __('Phone') }}</label>
                    <input type="tel" x-model="phone" placeholder="+1 555 123 4567" class="input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">{{ __('Email') }}</label>
                    <input type="email" x-model="email" placeholder="jane@acme.com" class="input w-full">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1.5">{{ __('Website') }}</label>
                <input type="url" x-model="website" placeholder="https://acme.com" class="input w-full">
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <a class="btn-primary" :class="!name && 'pointer-events-none opacity-50'"
                   :href="vcfHref" download="contact.vcf">
                    <x-icon name="download" class="h-4 w-4"/> {{ __('Download .vcf') }}
                </a>
                <a class="btn-secondary" :class="!name && 'pointer-events-none opacity-50'"
                   :href="qrHref" target="_blank" rel="noopener">
                    <x-icon name="qr" class="h-4 w-4"/> {{ __('Get QR code') }}
                </a>
            </div>
        </div>

        {{-- Live preview --}}
        <div class="card card-pad">
            <h2 class="text-sm font-medium mb-3">{{ __('Preview') }}</h2>
            <div class="rounded-2xl ring-1 ring-slate-200 dark:ring-slate-800 p-5 bg-gradient-to-br from-white to-slate-50 dark:from-slate-900 dark:to-slate-800/60">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300 text-lg font-bold"
                          x-text="(name || '?').trim().charAt(0).toUpperCase()"></span>
                    <div class="min-w-0">
                        <div class="font-semibold truncate" x-text="name || @js(__('Your name'))"></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 truncate" x-text="[title, org].filter(Boolean).join(' · ') || @js(__('Title · Company'))"></div>
                    </div>
                </div>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300" x-show="phone">
                        <x-icon name="phone" class="h-4 w-4 text-slate-400"/><span x-text="phone"></span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300" x-show="email">
                        <x-icon name="mail" class="h-4 w-4 text-slate-400"/><span class="truncate" x-text="email"></span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300" x-show="website">
                        <x-icon name="globe" class="h-4 w-4 text-slate-400"/><span class="truncate" x-text="website"></span>
                    </div>
                </dl>
            </div>
            <pre class="mt-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3 text-xs overflow-x-auto text-slate-500 dark:text-slate-400" x-text="vcard"></pre>
        </div>
    </div>

    <div class="mt-10 card card-pad text-center bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/40 dark:to-slate-900">
        <h2 class="text-lg font-bold">{{ __('One link for everything you do') }}</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ __('Create a free bio page with your contact card, social links and latest content — all behind a single branded short link and QR code.') }}</p>
        <a href="{{ route('register') }}" class="btn-primary mt-4">{{ __('Build your page free') }}</a>
    </div>

</div>

<script>
    function vcardBuilder() {
        return {
            name: '', title: '', org: '', phone: '', email: '', website: '',
            esc(v) { return (v || '').replace(/([,;\\])/g, '\\$1').replace(/\n/g, '\\n'); },
            get vcard() {
                const lines = ['BEGIN:VCARD', 'VERSION:3.0'];
                lines.push('N:' + this.esc(this.name) + ';;;');
                lines.push('FN:' + this.esc(this.name || 'Contact'));
                if (this.org) lines.push('ORG:' + this.esc(this.org));
                if (this.title) lines.push('TITLE:' + this.esc(this.title));
                if (this.phone) lines.push('TEL;TYPE=CELL:' + this.esc(this.phone));
                if (this.email) lines.push('EMAIL;TYPE=INTERNET:' + this.esc(this.email));
                if (this.website) lines.push('URL:' + this.esc(this.website));
                lines.push('END:VCARD');
                return lines.join('\n');
            },
            get vcfHref() {
                return 'data:text/vcard;charset=utf-8,' + encodeURIComponent(this.vcard);
            },
            get qrHref() {
                return @js(route('ftools.qr')) + '?data=' + encodeURIComponent(this.vcard);
            },
        };
    }
</script>
@endsection
