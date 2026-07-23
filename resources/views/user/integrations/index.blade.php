@extends('layouts.app')

@section('title', __('Integrations') . ' — ' . site_name())
@section('page-title', __('Integrations'))

@section('content')
<div class="space-y-5 max-w-4xl">

    {{-- Chat bots: connect Telegram / Slack / Discord --}}
    <div class="card card-pad">
        <h2 class="font-semibold">{{ __('Chat bots') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Connect a chat account, then shorten links by messaging the bot.') }}</p>

        @if($linkCode)
            <div class="mt-3 rounded-xl bg-brand-50 dark:bg-brand-950 text-brand-800 dark:text-brand-200 p-4 text-sm">
                @if($linkProvider === 'telegram' && $telegramBot)
                    {{ __('Open the bot and it will connect automatically:') }}
                    <a href="https://t.me/{{ $telegramBot }}?start={{ $linkCode }}" target="_blank" class="btn-primary btn-sm mt-2">{{ __('Open Telegram bot') }}</a>
                @else
                    {{ __('Send this to the bot to connect:') }}
                    <div class="mono text-lg font-bold mt-1">/start {{ $linkCode }}</div>
                @endif
                <div class="help mt-1">{{ __('This code expires in 15 minutes.') }}</div>
            </div>
        @endif

        <div class="mt-4 grid sm:grid-cols-3 gap-3">
            @foreach(['telegram' => 'Telegram', 'slack' => 'Slack', 'discord' => 'Discord'] as $prov => $label)
                <div class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm">{{ $label }}</span>
                        @if(isset($accounts[$prov]))<span class="badge-green">{{ __('Connected') }}</span>@endif
                    </div>
                    @if(isset($accounts[$prov]))
                        <div class="text-xs text-slate-500 mt-1 truncate">{{ $accounts[$prov]->external_name ?: $accounts[$prov]->external_id }}</div>
                        <x-confirm :action="route('integrations.disconnect', $accounts[$prov])" method="DELETE" :title="__('Disconnect :p?', ['p' => $label])">
                            <button type="button" class="btn-ghost btn-sm text-rose-600 mt-2">{{ __('Disconnect') }}</button>
                        </x-confirm>
                    @else
                        <form method="POST" action="{{ route('integrations.connect', $prov) }}" class="mt-2">@csrf
                            <button class="btn-secondary btn-sm w-full">{{ __('Connect') }}</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="help mt-2">{{ __('Bots must first be configured by an admin (Admin → Settings → Bots).') }}</p>
    </div>

    {{-- Click alerts --}}
    <div class="card card-pad">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ __('Click alerts') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Get notified on Slack, Discord or Telegram when your links are clicked.') }}</p>
            </div>
            <button class="btn-primary btn-sm" @click="$dispatch('open-modal','new-channel')"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add') }}</button>
        </div>

        <div class="mt-4 space-y-2">
            @forelse($channels as $channel)
                <div class="flex flex-wrap items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                    <span class="badge-brand">{{ $channelTypes[$channel->type] ?? $channel->type }}</span>
                    <span class="text-sm text-slate-500 truncate min-w-0 flex-1 mono">{{ \Illuminate\Support\Str::limit($channel->target, 40) }}</span>
                    <span class="text-xs text-slate-500">
                        {{ $channel->instant ? __('every click') : ($channel->milestone ? __('every :n clicks', ['n' => $channel->milestone]) : __('off')) }}
                    </span>
                    @if($channel->active)<span class="badge-green">{{ __('On') }}</span>@else<span class="badge-gray">{{ __('Off') }}</span>@endif
                    <div class="flex gap-1 ms-auto">
                        <form method="POST" action="{{ route('integrations.channels.test', $channel) }}">@csrf
                            <button class="btn-ghost btn-sm" title="{{ __('Send test') }}"><x-icon name="bolt" class="h-4 w-4"/></button>
                        </form>
                        <form method="POST" action="{{ route('integrations.channels.toggle', $channel) }}">@csrf
                            <button class="btn-ghost btn-sm">{{ $channel->active ? __('Disable') : __('Enable') }}</button>
                        </form>
                        <x-confirm :action="route('integrations.channels.destroy', $channel)" method="DELETE" :title="__('Remove this channel?')">
                            <button type="button" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="h-4 w-4"/></button>
                        </x-confirm>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 py-3 text-center">{{ __('No alert channels yet.') }}</p>
            @endforelse
        </div>
    </div>

    {{-- UTM templates --}}
    <div class="card card-pad">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ __('UTM templates') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Save UTM parameter sets and apply them to links in one click.') }}</p>
            </div>
            <button class="btn-primary btn-sm" @click="$dispatch('open-modal','new-utm')"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add') }}</button>
        </div>

        <div class="mt-4 grid sm:grid-cols-2 gap-3">
            @forelse($templates as $t)
                <div class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm">{{ $t->name }}</span>
                        <x-confirm :action="route('integrations.utm.destroy', $t)" method="DELETE" :title="__('Delete template?')">
                            <button type="button" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="h-4 w-4"/></button>
                        </x-confirm>
                    </div>
                    <div class="mt-1.5 flex flex-wrap gap-1 text-xs">
                        @foreach($t->toUtm() as $k => $v)
                            <span class="badge-gray">{{ $k }}={{ $v }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 py-3 text-center sm:col-span-2">{{ __('No UTM templates yet.') }}</p>
            @endforelse
        </div>
    </div>

    {{-- AI status --}}
    <div class="card card-pad">
        <h2 class="font-semibold">{{ __('AI assistant') }}</h2>
        @if(setting('ai_key'))
            <p class="text-sm mt-1"><span class="badge-green">{{ __('Enabled') }}</span>
                <span class="text-slate-500">{{ __('Alias & tag suggestions appear on the link form. Spam scanning: :s', ['s' => setting('ai_spam_check') ? __('on') : __('off')]) }}</span></p>
        @else
            <p class="text-sm text-slate-500 mt-1">{{ __('Not configured. An admin can enable it in Admin → Settings → AI to get automatic alias/tag suggestions and spam detection.') }}</p>
        @endif
    </div>

    {{-- Modals --}}
    <x-modal name="new-channel" :title="__('Add alert channel')">
        <form method="POST" action="{{ route('integrations.channels.store') }}" class="space-y-4" x-data="{ type: 'slack' }">
            @csrf
            <x-field name="type" :label="__('Type')">
                <select name="type" class="input" x-model="type">
                    @foreach($channelTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                </select>
            </x-field>
            <x-field name="target" x-show="type !== 'telegram'"
                     :label="__('Incoming webhook URL')"
                     :help="__('Slack: create an Incoming Webhook. Discord: Channel → Integrations → Webhooks.')">
                <input name="target" class="input" placeholder="https://hooks.slack.com/services/…" spellcheck="false">
            </x-field>
            <div x-show="type === 'telegram'" x-cloak>
                <x-field name="target" :label="__('Bot token | Chat ID')" :help="__('Create a bot with @BotFather, then use botToken|chatId (separated by a pipe).')">
                    <input name="target" class="input" placeholder="123456:ABC…|987654321" spellcheck="false">
                </x-field>
            </div>
            <div class="grid grid-cols-2 gap-3 items-end">
                <x-field name="milestone" :label="__('Alert every N clicks')" :help="__('0 to disable')">
                    <input type="number" name="milestone" class="input" value="0" min="0">
                </x-field>
                <div class="pb-2"><x-toggle name="instant" :label="__('Alert on every click')"/></div>
            </div>
            <button class="btn-primary w-full">{{ __('Add channel') }}</button>
        </form>
    </x-modal>

    <x-modal name="new-utm" :title="__('New UTM template')">
        <form method="POST" action="{{ route('integrations.utm.store') }}" class="space-y-3">
            @csrf
            <x-field name="name" :label="__('Template name')"><input name="name" class="input" required></x-field>
            <div class="grid grid-cols-2 gap-3">
                <x-field name="source" label="utm_source"><input name="source" class="input" placeholder="newsletter"></x-field>
                <x-field name="medium" label="utm_medium"><input name="medium" class="input" placeholder="email"></x-field>
                <x-field name="campaign" label="utm_campaign"><input name="campaign" class="input" placeholder="launch"></x-field>
                <x-field name="term" label="utm_term"><input name="term" class="input"></x-field>
                <x-field name="content" label="utm_content" class="col-span-2"><input name="content" class="input"></x-field>
            </div>
            <button class="btn-primary w-full">{{ __('Save template') }}</button>
        </form>
    </x-modal>
</div>
@endsection
