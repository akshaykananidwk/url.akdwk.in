@extends('layouts.admin')

@section('title', __('SSO') . ' — ' . site_name())
@section('page-title', __('Single sign-on'))

@section('content')
<div class="space-y-5">

    <p class="text-sm text-slate-500 dark:text-slate-400">
        {{ __('Let your users sign in with Okta, Azure AD/Entra, Auth0, Keycloak, Google Workspace or any OIDC provider.') }}
    </p>

    {{-- Add SSO provider --}}
    <form method="POST" action="{{ route('admin.sso.store') }}" class="card card-pad space-y-4">
        @csrf
        <h2 class="font-semibold">{{ __('Add SSO provider') }}</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="name" :label="__('Name')">
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="input" placeholder="Okta" required>
            </x-field>
            <x-field name="scopes" :label="__('Scopes')">
                <input type="text" id="scopes" name="scopes" value="{{ old('scopes', 'openid email profile') }}" class="input" placeholder="openid email profile">
            </x-field>
            <x-field name="client_id" :label="__('Client ID')">
                <input type="text" id="client_id" name="client_id" value="{{ old('client_id') }}" class="input" required>
            </x-field>
            <x-field name="client_secret" :label="__('Client secret')">
                <input type="password" id="client_secret" name="client_secret" class="input" autocomplete="new-password">
            </x-field>
            <x-field name="issuer" :label="__('Issuer')" :help="__('OIDC discovery base, e.g. https://accounts.google.com — endpoints auto-discovered')" class="sm:col-span-2">
                <input type="url" id="issuer" name="issuer" value="{{ old('issuer') }}" class="input" placeholder="https://accounts.google.com">
            </x-field>
        </div>

        <details class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4">
            <summary class="cursor-pointer text-sm font-medium select-none">{{ __('Advanced — explicit endpoints (optional)') }}</summary>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Only needed when your provider has no discovery document. Leave blank to auto-discover from the issuer.') }}</p>
            <div class="mt-3 space-y-4">
                <x-field name="authorize_url" :label="__('Authorization URL')">
                    <input type="url" id="authorize_url" name="authorize_url" value="{{ old('authorize_url') }}" class="input">
                </x-field>
                <x-field name="token_url" :label="__('Token URL')">
                    <input type="url" id="token_url" name="token_url" value="{{ old('token_url') }}" class="input">
                </x-field>
                <x-field name="userinfo_url" :label="__('Userinfo URL')">
                    <input type="url" id="userinfo_url" name="userinfo_url" value="{{ old('userinfo_url') }}" class="input">
                </x-field>
            </div>
        </details>

        <div class="flex justify-end">
            <button class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add SSO provider') }}</button>
        </div>
    </form>

    {{-- Existing providers --}}
    <div class="space-y-3">
        @forelse($providers as $provider)
            <div class="card card-pad">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <x-icon name="lock" class="h-4 w-4 text-slate-400"/>
                            <span class="font-semibold truncate">{{ $provider->name }}</span>
                            @if($provider->active)
                                <span class="badge-green">{{ __('Active') }}</span>
                            @else
                                <span class="badge-gray">{{ __('Disabled') }}</span>
                            @endif
                        </div>
                        <div class="mt-3">
                            <span class="label">{{ __('Callback URL to register at your IdP') }}</span>
                            <div class="flex items-center gap-2">
                                <code class="mono text-xs break-all rounded-lg bg-slate-100 dark:bg-slate-800 px-2.5 py-2 flex-1 min-w-0">{{ url('/auth/sso/' . $provider->slug . '/callback') }}</code>
                                <button type="button" class="btn-ghost btn-sm shrink-0" onclick="copyText(@js(url('/auth/sso/' . $provider->slug . '/callback')))" aria-label="{{ __('Copy') }}">
                                    <x-icon name="copy" class="h-4 w-4"/>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap shrink-0">
                        <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'edit-sso-{{ $provider->id }}')">
                            <x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}
                        </button>
                        <form method="POST" action="{{ route('admin.sso.toggle', $provider) }}">
                            @csrf
                            <button class="btn-secondary btn-sm">{{ $provider->active ? __('Disable') : __('Enable') }}</button>
                        </form>
                        <x-confirm :action="route('admin.sso.destroy', $provider)" method="DELETE"
                                   :title="__('Remove this SSO provider?')"
                                   :message="__('Users will no longer be able to sign in with :name.', ['name' => $provider->name])">
                            <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                        </x-confirm>
                    </div>
                </div>
            </div>

            {{-- Edit modal --}}
            <x-modal name="edit-sso-{{ $provider->id }}" :title="__('Edit :name', ['name' => $provider->name])">
                <form method="POST" action="{{ route('admin.sso.update', $provider) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid sm:grid-cols-2 gap-4">
                        <x-field name="name_{{ $provider->id }}" :label="__('Name')">
                            <input type="text" name="name" value="{{ $provider->name }}" class="input" required>
                        </x-field>
                        <x-field name="scopes_{{ $provider->id }}" :label="__('Scopes')">
                            <input type="text" name="scopes" value="{{ $provider->scopes }}" class="input" placeholder="openid email profile">
                        </x-field>
                        <x-field name="client_id_{{ $provider->id }}" :label="__('Client ID')">
                            <input type="text" name="client_id" value="{{ $provider->client_id }}" class="input" required>
                        </x-field>
                        <x-field name="client_secret_{{ $provider->id }}" :label="__('Client secret')" :help="__('Leave unchanged to keep the current secret.')">
                            <input type="password" name="client_secret" class="input" autocomplete="new-password"
                                   placeholder="{{ $provider->client_secret ? '••••••••' : '' }}"
                                   value="{{ $provider->client_secret ? '••••••••' : '' }}">
                        </x-field>
                        <x-field name="issuer_{{ $provider->id }}" :label="__('Issuer')" :help="__('OIDC discovery base, e.g. https://accounts.google.com — endpoints auto-discovered')" class="sm:col-span-2">
                            <input type="url" name="issuer" value="{{ $provider->issuer }}" class="input" placeholder="https://accounts.google.com">
                        </x-field>
                    </div>

                    <details class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4" @if($provider->authorize_url || $provider->token_url || $provider->userinfo_url) open @endif>
                        <summary class="cursor-pointer text-sm font-medium select-none">{{ __('Advanced — explicit endpoints (optional)') }}</summary>
                        <div class="mt-3 space-y-4">
                            <x-field name="authorize_url_{{ $provider->id }}" :label="__('Authorization URL')">
                                <input type="url" name="authorize_url" value="{{ $provider->authorize_url }}" class="input">
                            </x-field>
                            <x-field name="token_url_{{ $provider->id }}" :label="__('Token URL')">
                                <input type="url" name="token_url" value="{{ $provider->token_url }}" class="input">
                            </x-field>
                            <x-field name="userinfo_url_{{ $provider->id }}" :label="__('Userinfo URL')">
                                <input type="url" name="userinfo_url" value="{{ $provider->userinfo_url }}" class="input">
                            </x-field>
                        </div>
                    </details>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn-primary">{{ __('Save changes') }}</button>
                    </div>
                </form>
            </x-modal>
        @empty
            <x-empty-state icon="lock" :title="__('No SSO providers yet')" :description="__('Add your first identity provider above to enable single sign-on.')"/>
        @endforelse
    </div>
</div>
@endsection
