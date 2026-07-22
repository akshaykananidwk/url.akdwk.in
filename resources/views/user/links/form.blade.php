@extends('layouts.app')

@php
    $editing = $link !== null;
    $expiresValue = old('expires_at', $link?->expires_at?->format('Y-m-d\TH:i'));
@endphp

@section('title', ($editing ? __('Edit link') : __('Create link')) . ' — ' . site_name())
@section('page-title', $editing ? __('Edit link') : __('Create link'))

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    @if($editing)
        <div class="card card-pad !py-4 flex flex-wrap items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-xs text-slate-500">{{ __('Short URL') }}</p>
                <p class="font-semibold truncate">{{ $link->shortUrl() }}</p>
            </div>
            <button type="button" class="btn-secondary btn-sm" onclick="copyText(@js($link->shortUrl()))">
                <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy') }}
            </button>
            <a href="{{ route('stats.link', $link) }}" class="btn-secondary btn-sm">
                <x-icon name="chart" class="h-4 w-4"/> {{ __('View stats') }}
            </a>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('links.update', $link) : route('links.store') }}"
          x-data="{ tab: 'general' }" class="space-y-5">
        @csrf
        @if($editing) @method('PUT') @endif

        {{-- Tab pills --}}
        <div class="flex flex-wrap rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-0.5 text-sm font-medium bg-white dark:bg-slate-900">
            @foreach(['general' => __('General'), 'targeting' => __('Targeting'), 'social' => __('Social'), 'advanced' => __('Advanced')] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-brand-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                        class="flex-1 px-3 py-2 rounded-lg min-h-[40px]">{{ $label }}</button>
            @endforeach
        </div>

        {{-- ============================================================ General --}}
        <div x-show="tab === 'general'" class="card card-pad space-y-4">
            <x-field name="destination" :label="__('Destination URL')">
                <input type="text" name="destination" value="{{ old('destination', $link?->destination) }}" required
                       class="input" placeholder="https://example.com/my-long-page" inputmode="url">
            </x-field>

            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="alias" :label="__('Custom alias')" :help="__('Leave empty to generate one automatically.')">
                    <input type="text" name="alias" value="{{ old('alias', $link?->alias) }}" class="input" placeholder="my-campaign">
                </x-field>
                <x-field name="title" :label="__('Title')">
                    <input type="text" name="title" value="{{ old('title', $link?->title) }}" class="input" placeholder="{{ __('Internal name (optional)') }}">
                </x-field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="domain_id" :label="__('Domain')">
                    <select name="domain_id" class="input">
                        <option value="">{{ __('Main domain') }}</option>
                        @foreach($domains as $domain)
                            <option value="{{ $domain->id }}" @selected(old('domain_id', $link?->domain_id) == $domain->id)>{{ $domain->domain }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="space_id" :label="__('Space')">
                    <select name="space_id" class="input">
                        <option value="">{{ __('No space') }}</option>
                        @foreach($spaces as $space)
                            <option value="{{ $space->id }}" @selected(old('space_id', $link?->space_id) == $space->id)>{{ $space->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field name="tags" :label="__('Tags')" :help="__('Separate tags with commas.')">
                <input type="text" name="tags" value="{{ old('tags', $link ? implode(',', $link->tags ?? []) : '') }}"
                       class="input" placeholder="{{ __('marketing, summer, promo') }}">
            </x-field>

            <x-field name="notes" :label="__('Notes')">
                <textarea name="notes" rows="3" class="input" placeholder="{{ __('Private notes about this link (optional)') }}">{{ old('notes', $link?->notes) }}</textarea>
            </x-field>
        </div>

        {{-- ============================================================ Targeting --}}
        <div x-cloak x-show="tab === 'targeting'" class="space-y-5">

            {{-- Country rules --}}
            <div class="card card-pad space-y-3" x-data="repeater(@js(old('targeting.country', array_values($link->targeting['country'] ?? []))), {key: '', url: ''})">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-sm">{{ __('Country targeting') }}</h3>
                    <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add rule') }}</button>
                </div>
                <p class="help !mt-0">{{ __('Send visitors from a specific country to a different URL. Use 2-letter ISO codes.') }}</p>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" class="input sm:w-24 uppercase" maxlength="2" placeholder="US" x-model="row.key" :name="'targeting[country]['+i+'][key]'">
                        <input type="text" class="input flex-1" placeholder="https://example.com/us" inputmode="url" x-model="row.url" :name="'targeting[country]['+i+'][url]'">
                        <button type="button" class="btn-ghost btn-sm text-rose-600 self-start sm:self-center" @click="remove(i)" aria-label="{{ __('Remove rule') }}"><x-icon name="x" class="h-4 w-4"/></button>
                    </div>
                </template>
            </div>

            {{-- Platform rules --}}
            <div class="card card-pad space-y-3" x-data="repeater(@js(old('targeting.platform', array_values($link->targeting['platform'] ?? []))), {key: '', url: ''})">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-sm">{{ __('Platform targeting') }}</h3>
                    <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add rule') }}</button>
                </div>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <select class="input sm:w-40" x-model="row.key" :name="'targeting[platform]['+i+'][key]'">
                            <option value="">{{ __('Platform…') }}</option>
                            @foreach(['Android', 'iOS', 'Windows', 'macOS', 'Linux'] as $platform)
                                <option value="{{ $platform }}">{{ $platform }}</option>
                            @endforeach
                        </select>
                        <input type="text" class="input flex-1" placeholder="https://example.com/android" inputmode="url" x-model="row.url" :name="'targeting[platform]['+i+'][url]'">
                        <button type="button" class="btn-ghost btn-sm text-rose-600 self-start sm:self-center" @click="remove(i)" aria-label="{{ __('Remove rule') }}"><x-icon name="x" class="h-4 w-4"/></button>
                    </div>
                </template>
            </div>

            {{-- Language rules --}}
            <div class="card card-pad space-y-3" x-data="repeater(@js(old('targeting.language', array_values($link->targeting['language'] ?? []))), {key: '', url: ''})">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-sm">{{ __('Language targeting') }}</h3>
                    <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add rule') }}</button>
                </div>
                <p class="help !mt-0">{{ __('Use 2-letter language codes, e.g. en, de, es.') }}</p>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" class="input sm:w-24 lowercase" maxlength="2" placeholder="en" x-model="row.key" :name="'targeting[language]['+i+'][key]'">
                        <input type="text" class="input flex-1" placeholder="https://example.com/en" inputmode="url" x-model="row.url" :name="'targeting[language]['+i+'][url]'">
                        <button type="button" class="btn-ghost btn-sm text-rose-600 self-start sm:self-center" @click="remove(i)" aria-label="{{ __('Remove rule') }}"><x-icon name="x" class="h-4 w-4"/></button>
                    </div>
                </template>
            </div>

            {{-- Device rules --}}
            <div class="card card-pad space-y-3" x-data="repeater(@js(old('targeting.device', array_values($link->targeting['device'] ?? []))), {key: '', url: ''})">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-sm">{{ __('Device targeting') }}</h3>
                    <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add rule') }}</button>
                </div>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <select class="input sm:w-40" x-model="row.key" :name="'targeting[device]['+i+'][key]'">
                            <option value="">{{ __('Device…') }}</option>
                            <option value="mobile">{{ __('Mobile') }}</option>
                            <option value="tablet">{{ __('Tablet') }}</option>
                            <option value="desktop">{{ __('Desktop') }}</option>
                        </select>
                        <input type="text" class="input flex-1" placeholder="https://m.example.com" inputmode="url" x-model="row.url" :name="'targeting[device]['+i+'][url]'">
                        <button type="button" class="btn-ghost btn-sm text-rose-600 self-start sm:self-center" @click="remove(i)" aria-label="{{ __('Remove rule') }}"><x-icon name="x" class="h-4 w-4"/></button>
                    </div>
                </template>
            </div>

            {{-- Time rules --}}
            <div class="card card-pad space-y-3" x-data="repeater(@js(old('targeting.time', array_values($link->targeting['time'] ?? []))), {days: [], from: '', to: '', url: ''})">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-sm">{{ __('Time-based targeting') }}</h3>
                    <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add rule') }}</button>
                </div>
                <p class="help !mt-0">{{ __('Redirect to a different URL on certain days and hours.') }}</p>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-700 p-3 space-y-2">
                        <div class="flex flex-wrap gap-x-3 gap-y-1">
                            <template x-for="d in [0,1,2,3,4,5,6]" :key="d">
                                <label class="inline-flex items-center gap-1.5 text-xs font-medium min-h-[36px] cursor-pointer">
                                    <input type="checkbox" class="checkbox" :value="d" :name="'targeting[time]['+i+'][days][]'"
                                           :checked="(row.days || []).map(Number).includes(d)"
                                           @change="row.days = $event.target.checked ? [...(row.days || []), d] : (row.days || []).filter(x => Number(x) !== d)">
                                    <span x-text="dayNames[d]"></span>
                                </label>
                            </template>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="time" class="input sm:w-32" x-model="row.from" :name="'targeting[time]['+i+'][from]'" aria-label="{{ __('From') }}">
                            <input type="time" class="input sm:w-32" x-model="row.to" :name="'targeting[time]['+i+'][to]'" aria-label="{{ __('To') }}">
                            <input type="text" class="input flex-1" placeholder="https://example.com/after-hours" inputmode="url" x-model="row.url" :name="'targeting[time]['+i+'][url]'">
                            <button type="button" class="btn-ghost btn-sm text-rose-600 self-start sm:self-center" @click="remove(i)" aria-label="{{ __('Remove rule') }}"><x-icon name="x" class="h-4 w-4"/></button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Rotation --}}
            <div class="card card-pad space-y-3" x-data="repeater(@js(old('targeting.rotation', array_values($link->targeting['rotation'] ?? []))), {url: '', weight: 1})">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-sm">{{ __('A/B rotation') }}</h3>
                    <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add URL') }}</button>
                </div>
                <p class="help !mt-0">{{ __('Split traffic between multiple destinations by weight.') }}</p>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" class="input flex-1" placeholder="https://example.com/variant-a" inputmode="url" x-model="row.url" :name="'targeting[rotation]['+i+'][url]'">
                        <input type="number" min="1" class="input sm:w-28" placeholder="{{ __('Weight') }}" x-model="row.weight" :name="'targeting[rotation]['+i+'][weight]'" aria-label="{{ __('Weight') }}">
                        <button type="button" class="btn-ghost btn-sm text-rose-600 self-start sm:self-center" @click="remove(i)" aria-label="{{ __('Remove URL') }}"><x-icon name="x" class="h-4 w-4"/></button>
                    </div>
                </template>
            </div>
        </div>

        {{-- ============================================================ Social --}}
        <div x-cloak x-show="tab === 'social'" class="space-y-5">
            <div class="card card-pad space-y-4">
                <h3 class="font-semibold text-sm">{{ __('Social preview (Open Graph)') }}</h3>
                <x-field name="og.title" :label="__('Preview title')">
                    <input type="text" name="og[title]" value="{{ old('og.title', $link->og['title'] ?? '') }}" class="input">
                </x-field>
                <x-field name="og.description" :label="__('Preview description')">
                    <textarea name="og[description]" rows="2" class="input">{{ old('og.description', $link->og['description'] ?? '') }}</textarea>
                </x-field>
                <x-field name="og.image" :label="__('Preview image URL')">
                    <input type="text" name="og[image]" value="{{ old('og.image', $link->og['image'] ?? '') }}" class="input" placeholder="https://example.com/image.png" inputmode="url">
                </x-field>
            </div>

            <div class="card card-pad space-y-4">
                <h3 class="font-semibold text-sm">{{ __('UTM parameters') }}</h3>
                <p class="help !mt-0">{{ __('Appended to the destination URL for campaign tracking.') }}</p>
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach(['source', 'medium', 'campaign', 'term', 'content'] as $utmKey)
                        <x-field name="utm.{{ $utmKey }}" :label="'utm_' . $utmKey">
                            <input type="text" name="utm[{{ $utmKey }}]" value="{{ old('utm.' . $utmKey, $link->utm[$utmKey] ?? '') }}" class="input">
                        </x-field>
                    @endforeach
                </div>
            </div>

            <div class="card card-pad space-y-3">
                <h3 class="font-semibold text-sm">{{ __('Retargeting pixels') }}</h3>
                @forelse($pixels as $pixel)
                    <label class="flex items-center gap-3 min-h-touch cursor-pointer">
                        <input type="checkbox" name="pixel_ids[]" value="{{ $pixel->id }}" class="checkbox"
                               @checked(in_array($pixel->id, old('pixel_ids', $link?->pixels->pluck('id')->all() ?? [])))>
                        <span class="text-sm font-medium">{{ $pixel->name }}</span>
                        <span class="badge-gray">{{ $pixel->typeLabel() }}</span>
                    </label>
                @empty
                    <p class="text-sm text-slate-500">
                        {{ __('No pixels yet.') }}
                        <a href="{{ route('pixels.index') }}" class="text-brand-600 hover:underline">{{ __('Create one') }}</a>
                    </p>
                @endforelse
            </div>
        </div>

        {{-- ============================================================ Advanced --}}
        <div x-cloak x-show="tab === 'advanced'" class="space-y-5">
            <div class="card card-pad space-y-4">
                <h3 class="font-semibold text-sm">{{ __('Password protection') }}</h3>
                @if($editing && $link->password)
                    <div x-data="{ pw: '__keep__' }">
                        <input type="hidden" name="password" :value="pw">
                        <x-field name="password" :help="__('This link is password protected. Enter a new password to change it, or clear the field to keep the current one.')">
                            <input type="text" class="input" placeholder="{{ __('Leave unchanged') }}"
                                   @input="pw = $event.target.value || '__keep__'" autocomplete="off">
                        </x-field>
                    </div>
                @else
                    <x-field name="password" :help="__('Visitors must enter this password before being redirected.')">
                        <input type="text" name="password" value="{{ old('password') }}" class="input" placeholder="{{ __('Optional password') }}" autocomplete="off">
                    </x-field>
                @endif
            </div>

            <div class="card card-pad space-y-4">
                <h3 class="font-semibold text-sm">{{ __('Expiration') }}</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <x-field name="expires_at" :label="__('Expires at')">
                        <input type="datetime-local" name="expires_at" value="{{ $expiresValue }}" class="input">
                    </x-field>
                    <x-field name="max_clicks" :label="__('Maximum clicks')">
                        <input type="number" name="max_clicks" min="1" value="{{ old('max_clicks', $link?->max_clicks) }}" class="input" placeholder="{{ __('Unlimited') }}">
                    </x-field>
                </div>
                <x-field name="expired_redirect" :label="__('Redirect after expiration')" :help="__('Where to send visitors once the link has expired.')">
                    <input type="text" name="expired_redirect" value="{{ old('expired_redirect', $link?->expired_redirect) }}" class="input" placeholder="https://example.com/expired" inputmode="url">
                </x-field>
            </div>

            <div class="card card-pad space-y-4">
                <h3 class="font-semibold text-sm">{{ __('Options') }}</h3>
                <x-toggle name="disabled" :label="__('Disabled')" :checked="(bool) old('disabled', $link?->disabled ?? false)"/>
                <x-toggle name="cloaking" :label="__('Cloaking (show the short URL in an iframe)')" :checked="(bool) old('cloaking', $link?->cloaking ?? false)"/>
                <x-toggle name="public_stats" :label="__('Public statistics page')" :checked="(bool) old('public_stats', $link?->public_stats ?? false)"/>
            </div>

            <div class="card card-pad space-y-4">
                <h3 class="font-semibold text-sm">{{ __('Deep linking') }}</h3>
                <x-toggle name="deep_link[enabled]" :label="__('Open in native app when installed')"
                          :checked="(bool) old('deep_link.enabled', $link->deep_link['enabled'] ?? false)"/>
                <div class="grid sm:grid-cols-2 gap-4">
                    <x-field name="deep_link.ios" :label="__('iOS URI scheme')">
                        <input type="text" name="deep_link[ios]" value="{{ old('deep_link.ios', $link->deep_link['ios'] ?? '') }}" class="input" placeholder="myapp://product/1">
                    </x-field>
                    <x-field name="deep_link.android" :label="__('Android URI scheme')">
                        <input type="text" name="deep_link[android]" value="{{ old('deep_link.android', $link->deep_link['android'] ?? '') }}" class="input" placeholder="myapp://product/1">
                    </x-field>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('links.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ $editing ? __('Save changes') : __('Create link') }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    window.dayNames = @json([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')]);
    // Generic add/remove row repeater used by the targeting sections.
    window.repeater = (rows, blank) => ({
        rows: Array.isArray(rows) ? rows : Object.values(rows || {}),
        add() { this.rows.push(JSON.parse(JSON.stringify(blank))); },
        remove(i) { this.rows.splice(i, 1); },
    });
</script>
@endpush
