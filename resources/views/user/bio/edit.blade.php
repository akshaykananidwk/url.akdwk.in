@extends('layouts.app')

@section('title', __('Edit bio page') . ' — ' . site_name())
@section('page-title', $page->title)

@section('content')
<div class="space-y-5" x-data="{ tab: 'blocks' }">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $page->url() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:underline">
            <x-icon name="external" class="h-4 w-4"/> {{ $page->url() }}
        </a>
        <div class="flex gap-2">
            <button class="btn-ghost btn-sm" onclick="copyText(@js($page->url()))"><x-icon name="copy" class="h-4 w-4"/> {{ __('Copy') }}</button>
            <a href="{{ route('bio.index') }}" class="btn-secondary btn-sm">{{ __('All pages') }}</a>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-0.5 text-sm font-medium w-fit">
        <button @click="tab = 'blocks'" :class="tab === 'blocks' ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'" class="px-4 py-2 rounded-lg min-h-[40px]">{{ __('Blocks') }}</button>
        <button @click="tab = 'design'" :class="tab === 'design' ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'" class="px-4 py-2 rounded-lg min-h-[40px]">{{ __('Design') }}</button>
        <button @click="tab = 'settings'" :class="tab === 'settings' ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'" class="px-4 py-2 rounded-lg min-h-[40px]">{{ __('Settings') }}</button>
    </div>

    {{-- ============================== BLOCKS ============================== --}}
    <div x-show="tab === 'blocks'" class="space-y-4">
        {{-- Start from a template --}}
        <div class="card card-pad">
            <h2 class="font-semibold mb-1">{{ __('Start from a template') }}</h2>
            <p class="help mb-3">{{ __('Applies a theme + starter blocks you can then edit.') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach($templates as $key => $t)
                    <form method="POST" action="{{ route('bio.apply-template', $page) }}">
                        @csrf
                        <input type="hidden" name="template" value="{{ $key }}">
                        <button type="submit" class="btn-secondary btn-sm">{{ $t['name'] }}</button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="card card-pad">
            <h2 class="font-semibold mb-3">{{ __('Add a block') }}</h2>
            <form method="POST" action="{{ route('bio.blocks.store', $page) }}" class="space-y-3"
                  x-data="{ type: 'link' }">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-field name="type" :label="__('Block type')">
                        <select name="type" class="input" x-model="type">
                            @foreach($blockTypes as $key => $label)
                                <option value="{{ $key }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                </div>

                {{-- Per-type content fields --}}
                <div x-show="type === 'link'" class="grid sm:grid-cols-2 gap-3">
                    <input type="text" name="content[title]" class="input" placeholder="{{ __('Button title') }}">
                    <input type="url" name="content[url]" class="input" placeholder="https://…">
                </div>
                <div x-show="type === 'heading'"><input type="text" name="content[text]" class="input" placeholder="{{ __('Heading text') }}"></div>
                <div x-show="type === 'text'"><textarea name="content[text]" rows="3" class="input" placeholder="{{ __('Your text…') }}"></textarea></div>
                <div x-show="type === 'image'"><input type="url" name="content[url]" class="input" placeholder="{{ __('Image URL') }}"></div>
                <div x-show="type === 'video'"><input type="url" name="content[url]" class="input" placeholder="{{ __('YouTube / video URL') }}"></div>
                <div x-show="type === 'email_form'"><input type="text" name="content[title]" class="input" placeholder="{{ __('Form title (e.g. Join my newsletter)') }}"></div>
                <div x-show="type === 'whatsapp'" class="grid sm:grid-cols-2 gap-3">
                    <input type="text" name="content[phone]" class="input" placeholder="{{ __('Phone with country code') }}">
                    <input type="text" name="content[text]" class="input" placeholder="{{ __('Prefilled message (optional)') }}">
                </div>
                <div x-show="type === 'phone'"><input type="text" name="content[phone]" class="input" placeholder="{{ __('Phone number') }}"></div>
                <div x-show="type === 'vcard'" class="grid sm:grid-cols-2 gap-3">
                    <input type="text" name="content[name]" class="input" placeholder="{{ __('Full name') }}">
                    <input type="text" name="content[phone]" class="input" placeholder="{{ __('Phone') }}">
                    <input type="email" name="content[email]" class="input" placeholder="{{ __('Email') }}">
                    <input type="text" name="content[organization]" class="input" placeholder="{{ __('Company') }}">
                </div>
                <p x-show="type === 'socials'" class="help">{{ __('Shows the social icons configured in Settings.') }}</p>
                <div x-show="type === 'music'" class="space-y-3">
                    <input type="text" name="content[title]" class="input" placeholder="{{ __('Section title, e.g. Listen now') }}">
                    <textarea name="content[note]" rows="2" class="input" placeholder="{{ __('Optional note') }}"></textarea>
                </div>
                <div x-show="type === 'tip'" class="space-y-3">
                    <input type="text" name="content[headline]" class="input" placeholder="{{ __('Support me 🙌') }}">
                    <div class="grid sm:grid-cols-2 gap-3">
                        <select name="content[method]" class="input">
                            <option value="upi">{{ __('UPI') }}</option>
                            <option value="paypal">{{ __('PayPal') }}</option>
                            <option value="url">{{ __('Payment URL') }}</option>
                        </select>
                        <input type="text" name="content[currency]" class="input" placeholder="{{ __('Currency, e.g. INR / USD') }}">
                    </div>
                    <x-field name="content.target" :help="__('UPI ID, PayPal.me username, or a full payment URL.')">
                        <input type="text" name="content[target]" class="input" placeholder="{{ __('name@upi / paypal.me/you / https://…') }}">
                    </x-field>
                    <div class="grid grid-cols-3 gap-3">
                        <input type="number" name="content[amounts][]" class="input" placeholder="50">
                        <input type="number" name="content[amounts][]" class="input" placeholder="100">
                        <input type="number" name="content[amounts][]" class="input" placeholder="200">
                    </div>
                </div>
                <div x-show="type === 'app'" class="space-y-3">
                    <input type="text" name="content[title]" class="input" placeholder="{{ __('Button title, e.g. Get the app') }}">
                    <input type="url" name="content[ios]" class="input" placeholder="{{ __('App Store URL') }}">
                    <input type="url" name="content[android]" class="input" placeholder="{{ __('Google Play URL') }}">
                    <input type="url" name="content[fallback]" class="input" placeholder="{{ __('Fallback URL') }}">
                </div>

                <details class="text-sm">
                    <summary class="cursor-pointer text-slate-500 min-h-touch inline-flex items-center">{{ __('Schedule (optional)') }}</summary>
                    <div class="grid sm:grid-cols-2 gap-3 mt-2">
                        <x-field name="starts_at" :label="__('Show from')"><input type="datetime-local" name="starts_at" class="input"></x-field>
                        <x-field name="ends_at" :label="__('Until')"><input type="datetime-local" name="ends_at" class="input"></x-field>
                    </div>
                </details>

                <button type="submit" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add block') }}</button>
            </form>
        </div>

        <div class="space-y-2">
            @forelse($page->blocks as $i => $block)
                <div class="card card-pad !p-4 flex items-center gap-3">
                    <div class="flex flex-col gap-1">
                        @if($i > 0)
                            <form method="POST" action="{{ route('bio.blocks.reorder', $page) }}">
                                @csrf
                                @foreach($page->blocks as $j => $b)
                                    <input type="hidden" name="order[]" value="{{ $j === $i ? $page->blocks[$i-1]->id : ($j === $i - 1 ? $block->id : $b->id) }}">
                                @endforeach
                                <button class="btn-ghost btn-sm !min-h-[32px]" aria-label="{{ __('Move up') }}">▲</button>
                            </form>
                        @endif
                        @if($i < $page->blocks->count() - 1)
                            <form method="POST" action="{{ route('bio.blocks.reorder', $page) }}">
                                @csrf
                                @foreach($page->blocks as $j => $b)
                                    <input type="hidden" name="order[]" value="{{ $j === $i ? $page->blocks[$i+1]->id : ($j === $i + 1 ? $block->id : $b->id) }}">
                                @endforeach
                                <button class="btn-ghost btn-sm !min-h-[32px]" aria-label="{{ __('Move down') }}">▼</button>
                            </form>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="badge-brand">{{ __($blockTypes[$block->type] ?? $block->type) }}</span>
                            @if(! $block->active)<span class="badge-gray">{{ __('Hidden') }}</span>@endif
                            @if($block->starts_at || $block->ends_at)<span class="badge-amber">{{ __('Scheduled') }}</span>@endif
                        </div>
                        <div class="text-sm mt-1 truncate">
                            {{ $block->content['title'] ?? $block->content['text'] ?? $block->content['url'] ?? $block->content['phone'] ?? $block->content['name'] ?? '—' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ format_number($block->clicks) }} {{ __('clicks') }}</div>
                    </div>
                    <button class="btn-ghost btn-sm" @click="$dispatch('open-modal', 'edit-block-{{ $block->id }}')" aria-label="{{ __('Edit') }}">
                        <x-icon name="pencil" class="h-4 w-4"/>
                    </button>
                    <x-confirm :action="route('bio.blocks.destroy', $block)" method="DELETE" :title="__('Remove this block?')">
                        <button type="button" class="btn-ghost btn-sm text-rose-600" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                    </x-confirm>
                </div>

                <x-modal name="edit-block-{{ $block->id }}" :title="__('Edit block')">
                    <form method="POST" action="{{ route('bio.blocks.update', $block) }}" class="space-y-3">
                        @csrf @method('PUT')
                        @php($c = $block->content ?? [])
                        @switch($block->type)
                            @case('link')
                                <input type="text" name="content[title]" class="input" placeholder="{{ __('Button title') }}" value="{{ $c['title'] ?? '' }}">
                                <input type="url" name="content[url]" class="input" placeholder="https://…" value="{{ $c['url'] ?? '' }}">
                                @break
                            @case('heading')
                                <input type="text" name="content[text]" class="input" value="{{ $c['text'] ?? '' }}">
                                @break
                            @case('text')
                                <textarea name="content[text]" rows="3" class="input">{{ $c['text'] ?? '' }}</textarea>
                                @break
                            @case('image')
                            @case('video')
                                <input type="url" name="content[url]" class="input" value="{{ $c['url'] ?? '' }}">
                                @break
                            @case('email_form')
                                <input type="text" name="content[title]" class="input" value="{{ $c['title'] ?? '' }}">
                                @break
                            @case('whatsapp')
                                <input type="text" name="content[phone]" class="input" placeholder="{{ __('Phone') }}" value="{{ $c['phone'] ?? '' }}">
                                <input type="text" name="content[text]" class="input" placeholder="{{ __('Message') }}" value="{{ $c['text'] ?? '' }}">
                                @break
                            @case('phone')
                                <input type="text" name="content[phone]" class="input" value="{{ $c['phone'] ?? '' }}">
                                @break
                            @case('vcard')
                                <input type="text" name="content[name]" class="input" placeholder="{{ __('Full name') }}" value="{{ $c['name'] ?? '' }}">
                                <input type="text" name="content[phone]" class="input" placeholder="{{ __('Phone') }}" value="{{ $c['phone'] ?? '' }}">
                                <input type="email" name="content[email]" class="input" placeholder="{{ __('Email') }}" value="{{ $c['email'] ?? '' }}">
                                <input type="text" name="content[organization]" class="input" placeholder="{{ __('Company') }}" value="{{ $c['organization'] ?? '' }}">
                                @break
                            @case('music')
                                <input type="text" name="content[title]" class="input" placeholder="{{ __('Section title, e.g. Listen now') }}" value="{{ $c['title'] ?? '' }}">
                                <textarea name="content[note]" rows="2" class="input" placeholder="{{ __('Optional note') }}">{{ $c['note'] ?? '' }}</textarea>
                                @break
                            @case('tip')
                                <input type="text" name="content[headline]" class="input" placeholder="{{ __('Support me 🙌') }}" value="{{ $c['headline'] ?? '' }}">
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <select name="content[method]" class="input">
                                        <option value="upi" @selected(($c['method'] ?? '') === 'upi')>{{ __('UPI') }}</option>
                                        <option value="paypal" @selected(($c['method'] ?? '') === 'paypal')>{{ __('PayPal') }}</option>
                                        <option value="url" @selected(($c['method'] ?? '') === 'url')>{{ __('Payment URL') }}</option>
                                    </select>
                                    <input type="text" name="content[currency]" class="input" placeholder="{{ __('Currency') }}" value="{{ $c['currency'] ?? '' }}">
                                </div>
                                <x-field name="content.target" :help="__('UPI ID, PayPal.me username, or a full payment URL.')">
                                    <input type="text" name="content[target]" class="input" placeholder="{{ __('name@upi / paypal.me/you / https://…') }}" value="{{ $c['target'] ?? '' }}">
                                </x-field>
                                <div class="grid grid-cols-3 gap-3">
                                    @for($ai = 0; $ai < 3; $ai++)
                                        <input type="number" name="content[amounts][]" class="input" placeholder="{{ [50, 100, 200][$ai] }}" value="{{ $c['amounts'][$ai] ?? '' }}">
                                    @endfor
                                </div>
                                @break
                            @case('app')
                                <input type="text" name="content[title]" class="input" placeholder="{{ __('Button title, e.g. Get the app') }}" value="{{ $c['title'] ?? '' }}">
                                <input type="url" name="content[ios]" class="input" placeholder="{{ __('App Store URL') }}" value="{{ $c['ios'] ?? '' }}">
                                <input type="url" name="content[android]" class="input" placeholder="{{ __('Google Play URL') }}" value="{{ $c['android'] ?? '' }}">
                                <input type="url" name="content[fallback]" class="input" placeholder="{{ __('Fallback URL') }}" value="{{ $c['fallback'] ?? '' }}">
                                @break
                        @endswitch
                        <div class="grid sm:grid-cols-2 gap-3">
                            <x-field name="starts_at" :label="__('Show from')">
                                <input type="datetime-local" name="starts_at" class="input" value="{{ $block->starts_at?->format('Y-m-d\TH:i') }}">
                            </x-field>
                            <x-field name="ends_at" :label="__('Until')">
                                <input type="datetime-local" name="ends_at" class="input" value="{{ $block->ends_at?->format('Y-m-d\TH:i') }}">
                            </x-field>
                        </div>
                        <x-toggle name="active" :checked="$block->active" :label="__('Visible')"/>
                        <button type="submit" class="btn-primary w-full">{{ __('Save block') }}</button>
                    </form>
                </x-modal>
            @empty
                <x-empty-state icon="sparkles" :title="__('No blocks yet')" :description="__('Add your first link button above.')"/>
            @endforelse
        </div>
    </div>

    {{-- ============================== DESIGN + SETTINGS (one form) ============================== --}}
    <form method="POST" action="{{ route('bio.update', $page) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf @method('PUT')

        <div x-show="tab === 'design'" x-cloak class="space-y-4">
            <div class="card card-pad space-y-4">
                <h2 class="font-semibold">{{ __('Theme') }}</h2>
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                    @foreach($themes as $theme)
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="{{ $theme }}" class="peer sr-only" @checked(($page->theme ?? 'default') === $theme)>
                            <span class="block rounded-xl px-2 py-3 text-center text-xs font-medium ring-1 ring-slate-200 dark:ring-slate-700 peer-checked:ring-2 peer-checked:ring-brand-500 capitalize min-h-touch">{{ $theme }}</span>
                        </label>
                    @endforeach
                </div>
                <x-field name="font" :label="__('Font')">
                    <select name="font" class="input">
                        @foreach($fonts as $font)
                            <option value="{{ $font }}" @selected($page->font === $font)>{{ $font }}</option>
                        @endforeach
                    </select>
                </x-field>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach(['bg' => __('Background'), 'card' => __('Cards'), 'text' => __('Text'), 'button' => __('Buttons'), 'button_text' => __('Button text')] as $ck => $cl)
                        <x-field name="colors.{{ $ck }}" :label="$cl">
                            <input type="color" name="colors[{{ $ck }}]" value="{{ $page->colors[$ck] ?? '#000000' }}" class="input !p-1 h-11">
                        </x-field>
                    @endforeach
                </div>
                <p class="help">{{ __('Colors override the theme defaults. Leave black to use theme colors.') }}</p>
            </div>
            <div class="card card-pad grid sm:grid-cols-2 gap-4">
                <x-field name="avatar" :label="__('Profile photo')">
                    <input type="file" name="avatar" accept="image/*" class="input !py-2.5">
                </x-field>
                <x-field name="cover" :label="__('Cover image')">
                    <input type="file" name="cover" accept="image/*" class="input !py-2.5">
                </x-field>
            </div>
        </div>

        <div x-show="tab === 'settings'" x-cloak class="space-y-4">
            <div class="card card-pad grid sm:grid-cols-2 gap-4">
                <x-field name="username" :label="__('Username')">
                    <input type="text" name="username" class="input" value="{{ old('username', $page->username) }}" required>
                </x-field>
                <x-field name="title" :label="__('Title')">
                    <input type="text" name="title" class="input" value="{{ old('title', $page->title) }}" required>
                </x-field>
                <x-field name="bio" :label="__('Bio text')" class="sm:col-span-2">
                    <textarea name="bio" rows="3" class="input">{{ old('bio', $page->bio) }}</textarea>
                </x-field>
                <x-field name="domain_id" :label="__('Custom domain')">
                    <select name="domain_id" class="input">
                        <option value="">{{ __('Default domain') }}</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected($page->domain_id === $domain->id)>{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </x-field>
                <div class="flex items-end pb-2">
                    <x-toggle name="active" :checked="$page->active" :label="__('Page is live')"/>
                </div>
            </div>

            <div class="card card-pad space-y-4">
                <h2 class="font-semibold">{{ __('Social icons') }}</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach(['twitter', 'instagram', 'facebook', 'youtube', 'tiktok', 'linkedin', 'github', 'website'] as $social)
                        <x-field name="socials.{{ $social }}" :label="ucfirst($social)">
                            <input type="url" name="socials[{{ $social }}]" class="input" placeholder="https://…" value="{{ $page->socials[$social] ?? '' }}">
                        </x-field>
                    @endforeach
                </div>
            </div>

            <div class="card card-pad space-y-4">
                <h2 class="font-semibold">{{ __('SEO') }}</h2>
                <x-field name="seo.title" :label="__('Meta title')">
                    <input type="text" name="seo[title]" class="input" value="{{ $page->seo['title'] ?? '' }}">
                </x-field>
                <x-field name="seo.description" :label="__('Meta description')">
                    <textarea name="seo[description]" rows="2" class="input">{{ $page->seo['description'] ?? '' }}</textarea>
                </x-field>
                <x-toggle name="seo[noindex]" :checked="(bool) ($page->seo['noindex'] ?? false)" :label="__('Hide from search engines')"/>
            </div>
        </div>

        <div x-show="tab === 'design' || tab === 'settings'" x-cloak class="sticky bottom-20 lg:bottom-4">
            <button type="submit" class="btn-primary w-full sm:w-auto shadow-lg">{{ __('Save changes') }}</button>
        </div>
    </form>
</div>
@endsection
