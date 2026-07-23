@extends('layouts.admin')

@section('title', __('AI settings') . ' — ' . site_name())
@section('page-title', __('Settings'))

@section('content')
@include('admin.settings._layout')

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.settings.update', 'ai') }}" class="card card-pad space-y-4">
        @csrf @method('PUT')

        <div>
            <h2 class="font-semibold">{{ __('AI assistant') }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('Adds automatic alias & tag suggestions on the link form and optional spam/phishing detection. Bring your own API key.') }}</p>
        </div>

        <x-field name="ai_provider" :label="__('Provider')">
            <select name="ai_provider" class="input">
                <option value="anthropic" @selected(setting('ai_provider','anthropic')==='anthropic')>Anthropic (Claude)</option>
                <option value="openai" @selected(setting('ai_provider')==='openai')>OpenAI (GPT)</option>
            </select>
        </x-field>

        <x-field name="ai_key" :label="__('API key')" :help="__('Stored encrypted. Leave the dots to keep the saved key.')">
            <input name="ai_key" type="password" class="input" value="{{ setting('ai_key') ? '••••••••' : '' }}" autocomplete="off" spellcheck="false">
        </x-field>

        <x-field name="ai_model" :label="__('Model')" :help="__('e.g. claude-haiku-4-5-20251001 or gpt-4o-mini. Leave blank for the default.')">
            <input name="ai_model" class="input" value="{{ setting('ai_model') }}" spellcheck="false">
        </x-field>

        <x-toggle name="ai_spam_check" :checked="(bool) setting('ai_spam_check')" :label="__('Scan new links for spam / phishing with AI')"/>

        <button class="btn-primary">{{ __('Save settings') }}</button>
    </form>
</div>
@endsection
