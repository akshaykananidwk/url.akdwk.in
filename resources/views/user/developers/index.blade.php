@extends('layouts.app')

@section('title', __('Developers') . ' — ' . site_name())
@section('page-title', __('Developers'))

@section('content')
<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Automate your links with the REST API and webhooks.') }}</p>
        <a href="{{ route('developers.docs') }}" class="btn-secondary btn-sm shrink-0">
            <x-icon name="doc" class="h-4 w-4"/> {{ __('API documentation') }}
        </a>
    </div>

    {{-- API keys --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('API keys') }}</h2>

        @if(session('new_api_key'))
            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 ring-1 ring-emerald-200 dark:ring-emerald-900 p-4 mb-4">
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 mb-2">
                    {{ __('Copy your new API key now — it will not be shown again.') }}
                </p>
                <div class="flex items-center gap-2">
                    <code class="font-mono text-xs break-all flex-1">{{ session('new_api_key') }}</code>
                    <button type="button" class="btn-primary btn-sm shrink-0" onclick="copyText(@js(session('new_api_key')))">
                        <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy') }}
                    </button>
                </div>
            </div>
        @endif

        @if($apiEnabled)
            <form method="POST" action="{{ route('developers.keys.store') }}" class="flex flex-col sm:flex-row gap-3 sm:items-start mb-5">
                @csrf
                <x-field name="name" class="flex-1">
                    <input type="text" name="name" value="{{ old('name') }}" required class="input" placeholder="{{ __('Key name, e.g. Zapier') }}">
                </x-field>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> {{ __('Create key') }}</button>
            </form>
        @else
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 p-4 mb-5 text-sm text-amber-800 dark:text-amber-300">
                {{ __('API access is not available on your plan.') }}
                <a href="{{ route('billing.plans') }}" class="font-semibold underline">{{ __('Upgrade to unlock it.') }}</a>
            </div>
        @endif

        @if($keys->isEmpty())
            <p class="text-sm text-slate-500 py-4 text-center">{{ __('No API keys yet.') }}</p>
        @else
            <div class="space-y-3">
                @foreach($keys as $key)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm truncate">{{ $key->name }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $key->key_prefix }}••••••••</p>
                        </div>
                        <span class="text-xs text-slate-500">
                            {{ __('Last used:') }} {{ $key->last_used_at?->diffForHumans() ?? __('never') }}
                        </span>
                        <x-confirm :action="route('developers.keys.destroy', $key)" method="DELETE" :title="__('Revoke this API key?')"
                                   :message="__('Requests using it will stop working immediately.')" :button="__('Revoke')">
                            <button type="button" class="btn-danger btn-sm self-start sm:self-center"><x-icon name="trash" class="h-4 w-4"/> {{ __('Revoke') }}</button>
                        </x-confirm>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Webhooks --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Webhooks') }}</h2>

        @if($webhooksEnabled)
            <form method="POST" action="{{ route('developers.webhooks.store') }}" class="space-y-4 mb-5">
                @csrf
                <x-field name="url" :label="__('Endpoint URL')">
                    <input type="url" name="url" value="{{ old('url') }}" required class="input" placeholder="https://example.com/hooks/shortl" inputmode="url">
                </x-field>
                <x-field name="events" :label="__('Events')">
                    <div class="flex flex-wrap gap-x-5 gap-y-1">
                        @foreach($events as $event => $label)
                            <label class="inline-flex items-center gap-2 min-h-touch cursor-pointer text-sm">
                                <input type="checkbox" name="events[]" value="{{ $event }}" class="checkbox"
                                       @checked(in_array($event, old('events', [])))>
                                {{ __($label) }} <code class="text-xs text-slate-400">{{ $event }}</code>
                            </label>
                        @endforeach
                    </div>
                </x-field>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> {{ __('Create webhook') }}</button>
            </form>
        @else
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 p-4 mb-5 text-sm text-amber-800 dark:text-amber-300">
                {{ __('Webhooks are not available on your plan.') }}
                <a href="{{ route('billing.plans') }}" class="font-semibold underline">{{ __('Upgrade to unlock them.') }}</a>
            </div>
        @endif

        @if($webhooks->isEmpty())
            <p class="text-sm text-slate-500 py-4 text-center">{{ __('No webhooks yet.') }}</p>
        @else
            <div class="space-y-3">
                @foreach($webhooks as $webhook)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm truncate">{{ $webhook->url }}</p>
                            <p class="mt-1 flex flex-wrap gap-1">
                                @foreach($webhook->events ?? [] as $event)
                                    <span class="badge-gray">{{ $event }}</span>
                                @endforeach
                                @if($webhook->failures > 0)
                                    <span class="badge-red">{{ __(':count failures', ['count' => $webhook->failures]) }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="{{ $webhook->active ? 'badge-green' : 'badge-gray' }}">{{ $webhook->active ? __('Active') : __('Paused') }}</span>
                        <form method="POST" action="{{ route('developers.webhooks.toggle', $webhook) }}">
                            @csrf
                            <button type="submit" class="btn-secondary btn-sm">
                                {{ $webhook->active ? __('Disable') : __('Enable') }}
                            </button>
                        </form>
                        <x-confirm :action="route('developers.webhooks.destroy', $webhook)" method="DELETE" :title="__('Delete this webhook?')">
                            <button type="button" class="btn-danger btn-sm self-start sm:self-center"><x-icon name="trash" class="h-4 w-4"/></button>
                        </x-confirm>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
