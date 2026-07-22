@extends('layouts.app')

@section('title', __('Domains') . ' — ' . site_name())
@section('page-title', __('Domains'))

@section('content')
<div class="space-y-5">

    {{-- Add domain --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Add a custom domain') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('Use your own domain for short links, e.g. go.yourbrand.com.') }}</p>
        <form method="POST" action="{{ route('domains.store') }}" class="flex flex-col sm:flex-row gap-3 sm:items-start">
            @csrf
            <x-field name="domain" class="flex-1">
                <input type="text" name="domain" value="{{ old('domain') }}" required class="input" placeholder="go.yourbrand.com" inputmode="url" autocapitalize="none">
            </x-field>
            <button type="submit" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add domain') }}</button>
        </form>
        @error('verify')<p class="error">{{ $message }}</p>@enderror
    </div>

    {{-- DNS instructions --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-2">{{ __('DNS setup') }}</h2>
        <ol class="list-decimal ps-5 text-sm text-slate-600 dark:text-slate-300 space-y-1.5">
            <li>{{ __("Point an A record to this server's IP, or add a CNAME record pointing to :host.", ['host' => $appHost]) }}</li>
            <li>{{ __('Wait for DNS propagation (usually a few minutes, up to 24 hours).') }}</li>
            <li>{{ __('Click Verify next to your domain below.') }}</li>
        </ol>
        <p class="help">{{ __('Alternative: if your domain sits behind a proxy or CDN, create a file at /.well-known/shortl-verify on it containing your verification token (shown in the domain settings).') }}</p>
    </div>

    {{-- Domain list --}}
    @if($domains->isEmpty())
        <x-empty-state icon="globe" :title="__('No custom domains yet')" :description="__('Add your first branded domain above to build trust and recognition.')"/>
    @else
        <div class="card overflow-hidden">
            <table class="table-cards">
                <thead>
                    <tr>
                        <th>{{ __('Domain') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Links') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domains as $domain)
                        <tr>
                            <td data-label="{{ __('Domain') }}">
                                <span class="font-medium">{{ $domain->domain }}</span>
                            </td>
                            <td data-label="{{ __('Status') }}">
                                <span class="flex flex-wrap gap-1.5 justify-end sm:justify-start">
                                    @if($domain->isVerified())
                                        <span class="badge-green"><x-icon name="check" class="h-3.5 w-3.5"/> {{ __('Verified') }}</span>
                                        @if($domain->ssl)<span class="badge-green"><x-icon name="lock" class="h-3.5 w-3.5"/> SSL</span>@endif
                                    @else
                                        <span class="badge-amber">{{ __('Pending') }}</span>
                                    @endif
                                    @if(auth()->user()->default_domain === $domain->domain)
                                        <span class="badge-brand">{{ __('Default') }}</span>
                                    @endif
                                </span>
                            </td>
                            <td data-label="{{ __('Links') }}">{{ format_number($domain->links_count) }}</td>
                            <td data-label="{{ __('Actions') }}">
                                <span class="flex flex-wrap gap-1.5 justify-end">
                                    @unless($domain->isVerified())
                                        <form method="POST" action="{{ route('domains.verify', $domain) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary btn-sm"><x-icon name="refresh" class="h-4 w-4"/> {{ __('Verify') }}</button>
                                        </form>
                                    @endunless
                                    <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'domain-settings-{{ $domain->id }}')">
                                        <x-icon name="settings" class="h-4 w-4"/> {{ __('Settings') }}
                                    </button>
                                    <x-confirm :action="route('domains.destroy', $domain)" method="DELETE" :title="__('Remove this domain?')"
                                               :message="__('Links using this domain must be deleted or moved first.')">
                                        <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/></button>
                                    </x-confirm>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $domains->links() }}</div>

        {{-- Settings modals (outside table so they are valid markup) --}}
        @foreach($domains as $domain)
            <x-modal name="domain-settings-{{ $domain->id }}" :title="__('Domain settings') . ' — ' . $domain->domain">
                @unless($domain->isVerified())
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-950/40 p-3 mb-4 text-xs text-amber-800 dark:text-amber-300">
                        <p class="font-semibold mb-1">{{ __('Verification token') }}</p>
                        <p>{{ __('As an alternative to DNS, serve this token as the content of http://:domain/.well-known/shortl-verify:', ['domain' => $domain->domain]) }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="font-mono break-all">{{ $domain->verification_token }}</code>
                            <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js($domain->verification_token))" aria-label="{{ __('Copy token') }}">
                                <x-icon name="copy" class="h-4 w-4"/>
                            </button>
                        </div>
                    </div>
                @endunless
                <form method="POST" action="{{ route('domains.update', $domain) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-field name="index_redirect" :label="__('Root redirect')" :help="__('Visitors opening the bare domain are sent here.')">
                        <input type="text" name="index_redirect" value="{{ $domain->index_redirect }}" class="input" placeholder="https://yourbrand.com" inputmode="url">
                    </x-field>
                    <x-field name="not_found_redirect" :label="__('404 redirect')" :help="__('Where to send visitors when a short link does not exist.')">
                        <input type="text" name="not_found_redirect" value="{{ $domain->not_found_redirect }}" class="input" placeholder="https://yourbrand.com/not-found" inputmode="url">
                    </x-field>
                    <x-toggle name="is_default" :label="__('Use as my default domain')" :checked="auth()->user()->default_domain === $domain->domain"/>
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
