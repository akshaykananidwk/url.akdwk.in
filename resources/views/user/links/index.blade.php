@extends('layouts.app')

@section('title', __('Links') . ' — ' . site_name())
@section('page-title', __('Links'))

@section('content')
<div class="space-y-5" x-data="{ selected: [] }">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('links.create') }}" class="btn-primary btn-sm">
            <x-icon name="plus" class="h-4 w-4"/> {{ __('New link') }}
        </a>
        <a href="{{ route('links.bulk') }}" class="btn-secondary btn-sm">
            <x-icon name="bolt" class="h-4 w-4"/> {{ __('Bulk shorten') }}
        </a>
        <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'import-csv')">
            <x-icon name="upload" class="h-4 w-4"/> {{ __('Import CSV') }}
        </button>
        <a href="{{ route('links.export') }}" class="btn-secondary btn-sm">
            <x-icon name="download" class="h-4 w-4"/> {{ __('Export CSV') }}
        </a>

        <form id="bulk-delete-form" method="POST" action="{{ route('links.bulk-delete') }}" class="ms-auto"
              onsubmit="return confirm(@js(__('Delete the selected links? This action cannot be undone.')))">
            @csrf
            <button type="submit" class="btn-danger btn-sm" x-cloak x-show="selected.length">
                <x-icon name="trash" class="h-4 w-4"/>
                {{ __('Delete') }} (<span x-text="selected.length"></span>)
            </button>
        </form>
    </div>

    {{-- Search + filters --}}
    <form method="GET" action="{{ route('links.index') }}" class="card card-pad !p-4 grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="col-span-2 lg:col-span-1 relative">
            <input type="search" name="q" value="{{ request('q') }}" class="input ps-10" placeholder="{{ __('Search links…') }}">
            <span class="absolute inset-y-0 start-3 flex items-center text-slate-400 pointer-events-none">
                <x-icon name="search" class="h-4 w-4"/>
            </span>
        </div>
        <select name="space" class="input" onchange="this.form.submit()" aria-label="{{ __('Space') }}">
            <option value="">{{ __('All spaces') }}</option>
            @foreach($spaces as $space)
                <option value="{{ $space->id }}" @selected(request('space') == $space->id)>{{ $space->name }}</option>
            @endforeach
        </select>
        <select name="domain" class="input" onchange="this.form.submit()" aria-label="{{ __('Domain') }}">
            <option value="">{{ __('All domains') }}</option>
            <option value="main" @selected(request('domain') === 'main')>{{ __('Main domain') }}</option>
            @foreach($domains as $domain)
                <option value="{{ $domain->id }}" @selected(request('domain') == $domain->id)>{{ $domain->domain }}</option>
            @endforeach
        </select>
        <select name="status" class="input" onchange="this.form.submit()" aria-label="{{ __('Status') }}">
            <option value="">{{ __('All statuses') }}</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
            <option value="disabled" @selected(request('status') === 'disabled')>{{ __('Disabled') }}</option>
            <option value="archived" @selected(request('status') === 'archived')>{{ __('Archived') }}</option>
        </select>
        <select name="sort" class="input" onchange="this.form.submit()" aria-label="{{ __('Sort by') }}">
            <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>{{ __('Newest first') }}</option>
            <option value="clicks_count" @selected(request('sort') === 'clicks_count')>{{ __('Most clicks') }}</option>
            <option value="last_click_at" @selected(request('sort') === 'last_click_at')>{{ __('Recently clicked') }}</option>
            <option value="alias" @selected(request('sort') === 'alias')>{{ __('Alias (A–Z)') }}</option>
        </select>
    </form>

    {{-- Links list --}}
    @if($links->isEmpty())
        <x-empty-state icon="link" :title="__('No links found')" :description="__('Create your first short link or adjust the filters above.')">
            <a href="{{ route('links.create') }}" class="btn-primary btn-sm">{{ __('Create link') }}</a>
        </x-empty-state>
    @else
        <div class="space-y-3">
            @foreach($links as $link)
                <div class="card p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                    <input type="checkbox" name="ids[]" value="{{ $link->id }}" form="bulk-delete-form"
                           class="checkbox shrink-0" x-model="selected" aria-label="{{ __('Select link') }}">

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('links.edit', $link) }}" class="font-semibold text-sm truncate hover:text-brand-600">
                                {{ $link->shortUrl() }}
                            </a>
                            <button type="button" class="btn-ghost btn-sm !min-h-[32px] !px-1.5" onclick="copyText(@js($link->shortUrl()))" aria-label="{{ __('Copy link') }}">
                                <x-icon name="copy" class="h-4 w-4"/>
                            </button>
                            @if($link->space)
                                <span class="badge-gray">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $link->space->color }}"></span>
                                    {{ $link->space->name }}
                                </span>
                            @endif
                            @if($link->disabled)<span class="badge-red">{{ __('Disabled') }}</span>@endif
                            @if($link->archived_at)<span class="badge-gray">{{ __('Archived') }}</span>@endif
                            @if($link->isExpired())<span class="badge-amber">{{ __('Expired') }}</span>@endif
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500 truncate">{{ $link->destination }}</p>
                    </div>

                    <a href="{{ route('stats.link', $link) }}" class="badge-brand shrink-0 self-start sm:self-center min-h-[32px]">
                        {{ format_number($link->clicks_count) }} {{ __('clicks') }}
                    </a>

                    <div class="flex flex-wrap items-center gap-1 shrink-0">
                        <a href="{{ route('links.edit', $link) }}" class="btn-ghost btn-sm" aria-label="{{ __('Edit') }}" title="{{ __('Edit') }}">
                            <x-icon name="pencil" class="h-4 w-4"/>
                        </a>
                        <a href="{{ route('stats.link', $link) }}" class="btn-ghost btn-sm" aria-label="{{ __('Statistics') }}" title="{{ __('Statistics') }}">
                            <x-icon name="chart" class="h-4 w-4"/>
                        </a>
                        <a href="{{ route('qr.index') }}" class="btn-ghost btn-sm" aria-label="{{ __('QR code') }}" title="{{ __('QR code') }}">
                            <x-icon name="qr" class="h-4 w-4"/>
                        </a>
                        <form method="POST" action="{{ route('links.toggle', $link) }}">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm" aria-label="{{ $link->disabled ? __('Enable') : __('Disable') }}" title="{{ $link->disabled ? __('Enable') : __('Disable') }}">
                                <x-icon name="{{ $link->disabled ? 'check' : 'x' }}" class="h-4 w-4"/>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('links.duplicate', $link) }}">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm" aria-label="{{ __('Duplicate') }}" title="{{ __('Duplicate') }}">
                                <x-icon name="copy" class="h-4 w-4"/>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('links.archive', $link) }}">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm" aria-label="{{ $link->archived_at ? __('Restore') : __('Archive') }}" title="{{ $link->archived_at ? __('Restore') : __('Archive') }}">
                                <x-icon name="{{ $link->archived_at ? 'refresh' : 'folder' }}" class="h-4 w-4"/>
                            </button>
                        </form>
                        <x-confirm :action="route('links.destroy', $link)" method="DELETE" :title="__('Delete this link?')"
                                   :message="__('The link and all of its statistics will be permanently removed.')">
                            <button type="button" class="btn-ghost btn-sm text-rose-600 dark:text-rose-400" aria-label="{{ __('Delete') }}" title="{{ __('Delete') }}">
                                <x-icon name="trash" class="h-4 w-4"/>
                            </button>
                        </x-confirm>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $links->links() }}</div>
    @endif

    {{-- CSV import modal --}}
    <x-modal name="import-csv" :title="__('Import links from CSV')">
        <form method="POST" action="{{ route('links.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-field name="file" :label="__('CSV file')" :help="__('Columns: destination (or url), alias, title. A plain list of URLs also works.')">
                <input type="file" name="file" accept=".csv,.txt" required class="input !p-2.5">
            </x-field>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                <button type="submit" class="btn-primary">{{ __('Import') }}</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
