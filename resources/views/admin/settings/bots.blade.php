@extends('layouts.admin')

@section('title', __('Bots') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<div class="max-w-2xl space-y-5">
    <form method="POST" action="{{ route('admin.settings.update', 'bots') }}" class="card card-pad space-y-4">
        @csrf @method('PUT')

        {{-- Telegram --}}
        <div>
            <h2 class="font-semibold flex items-center gap-2">{{ __('Telegram bot') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Create a bot with @BotFather, paste its token, then click "Connect webhook". Users link their account from Integrations and can shorten links by messaging the bot.') }}</p>
        </div>
        <x-field name="telegram_bot_token" :label="__('Bot token')" :help="__('Stored encrypted. Leave the dots to keep the saved token.')">
            <input name="telegram_bot_token" type="password" class="input" value="{{ setting('telegram_bot_token') ? '••••••••' : '' }}" autocomplete="off" spellcheck="false">
        </x-field>
        @if(setting('telegram_bot_username'))
            <p class="text-sm"><span class="badge-green">{{ __('Connected') }}</span> <a href="https://t.me/{{ setting('telegram_bot_username') }}" target="_blank" class="text-brand-600 hover:underline">@{{ setting('telegram_bot_username') }}</a></p>
        @endif

        <hr class="border-slate-100 dark:border-slate-800">

        {{-- Slack --}}
        <div>
            <h2 class="font-semibold">{{ __('Slack slash command') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Create a Slack app with a /shorten command pointing at the URL below, then paste its Signing Secret.') }}</p>
        </div>
        <x-field name="slack_signing_secret" :label="__('Signing secret')" :help="__('Leave the dots to keep the saved value.')">
            <input name="slack_signing_secret" type="password" class="input" value="{{ setting('slack_signing_secret') ? '••••••••' : '' }}" autocomplete="off" spellcheck="false">
        </x-field>
        <div class="text-xs">
            <span class="text-slate-500">{{ __('Command Request URL:') }}</span>
            <code class="mono block mt-1 p-2 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-x-auto">{{ url('/webhooks/slack/command') }}</code>
        </div>

        <hr class="border-slate-100 dark:border-slate-800">

        {{-- Discord --}}
        <div>
            <h2 class="font-semibold">{{ __('Discord bot') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('In the Discord Developer Portal, set the Interactions Endpoint URL below and paste your app\'s Public Key. Add a /shorten command with a "url" option.') }}</p>
        </div>
        <x-field name="discord_public_key" :label="__('Public key')">
            <input name="discord_public_key" class="input" value="{{ setting('discord_public_key') }}" spellcheck="false">
        </x-field>
        <div class="text-xs">
            <span class="text-slate-500">{{ __('Interactions Endpoint URL:') }}</span>
            <code class="mono block mt-1 p-2 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-x-auto">{{ url('/webhooks/discord') }}</code>
        </div>

        <button class="btn-primary">{{ __('Save bot settings') }}</button>
    </form>

    {{-- Telegram connect action --}}
    <form method="POST" action="{{ route('admin.settings.telegram-webhook') }}" class="card card-pad flex items-center justify-between gap-3">
        @csrf
        <div>
            <div class="font-medium text-sm">{{ __('Connect Telegram webhook') }}</div>
            <div class="text-xs text-slate-500">{{ __('Registers this site as the bot\'s webhook. Run after saving the token.') }}</div>
        </div>
        <button class="btn-secondary shrink-0">{{ __('Connect webhook') }}</button>
    </form>
</div>
@endsection
