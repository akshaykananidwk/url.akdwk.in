@extends('layouts.app')

@section('title', __('Account') . ' — ' . site_name())
@section('page-title', __('Account settings'))

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- Profile --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Profile') }}</h2>
        <form method="POST" action="{{ route('account.profile') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-4">
                <img src="{{ $user->avatarUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover ring-2 ring-slate-200 dark:ring-slate-700">
                <x-field name="avatar" :label="__('Avatar')" class="flex-1">
                    <input type="file" name="avatar" accept="image/*" class="input !p-2.5">
                </x-field>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="name" :label="__('Name')">
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input">
                </x-field>
                <x-field name="email" :label="__('Email')">
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input">
                </x-field>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <x-field name="locale" :label="__('Language')">
                    <select name="locale" class="input">
                        @foreach(active_languages() as $language)
                            <option value="{{ $language->code }}" @selected(old('locale', $user->locale) === $language->code)>{{ $language->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="timezone" :label="__('Timezone')">
                    <select name="timezone" class="input">
                        @foreach(\DateTimeZone::listIdentifiers() as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $user->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="theme" :label="__('Theme')">
                    <select name="theme" class="input">
                        <option value="system" @selected(old('theme', $user->theme) === 'system')>{{ __('System') }}</option>
                        <option value="light" @selected(old('theme', $user->theme) === 'light')>{{ __('Light') }}</option>
                        <option value="dark" @selected(old('theme', $user->theme) === 'dark')>{{ __('Dark') }}</option>
                    </select>
                </x-field>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">{{ __('Save profile') }}</button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Password') }}</h2>
        <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-field name="current_password" :label="__('Current password')">
                <input type="password" name="current_password" required class="input" autocomplete="current-password">
            </x-field>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="password" :label="__('New password')">
                    <input type="password" name="password" required class="input" autocomplete="new-password">
                </x-field>
                <x-field name="password_confirmation" :label="__('Confirm new password')">
                    <input type="password" name="password_confirmation" required class="input" autocomplete="new-password">
                </x-field>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">{{ __('Change password') }}</button>
            </div>
        </form>
    </div>

    {{-- Two-factor --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Two-factor authentication') }}</h2>
        @if($user->hasTwoFactorEnabled())
            <p class="mb-4"><span class="badge-green"><x-icon name="shield" class="h-3.5 w-3.5"/> {{ __('Enabled') }}</span></p>
            <form method="POST" action="{{ route('account.two-factor.disable') }}" class="flex flex-col sm:flex-row gap-3 sm:items-start">
                @csrf
                @method('DELETE')
                <x-field name="password" class="flex-1">
                    <input type="password" name="password" required class="input" placeholder="{{ __('Confirm with your password') }}" autocomplete="current-password">
                </x-field>
                <button type="submit" class="btn-danger">{{ __('Disable 2FA') }}</button>
            </form>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('Add an extra layer of security by requiring a one-time code from your authenticator app at login.') }}</p>
            <a href="{{ route('account.two-factor') }}" class="btn-primary"><x-icon name="shield" class="h-4 w-4"/> {{ __('Enable two-factor authentication') }}</a>
        @endif
    </div>

    {{-- Notifications --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Notifications') }}</h2>
        <form method="POST" action="{{ route('account.notifications') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-toggle name="email_link_reports" :label="__('Weekly link performance report by email')" :checked="(bool) ($user->notification_prefs['email_link_reports'] ?? false)"/>
            <x-toggle name="email_billing" :label="__('Billing emails (invoices, renewals)')" :checked="(bool) ($user->notification_prefs['email_billing'] ?? true)"/>
            <x-toggle name="email_product" :label="__('Product news and tips')" :checked="(bool) ($user->notification_prefs['email_product'] ?? false)"/>
            <x-toggle name="inapp_clicks_milestones" :label="__('In-app alerts for click milestones')" :checked="(bool) ($user->notification_prefs['inapp_clicks_milestones'] ?? true)"/>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">{{ __('Save preferences') }}</button>
            </div>
        </form>
    </div>

    {{-- Sessions --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Active sessions') }}</h2>
        <div class="space-y-2 mb-5">
            @foreach($sessions as $session)
                <div class="flex items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3 text-sm">
                    <x-icon name="device" class="h-5 w-5 shrink-0 text-slate-400"/>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium">{{ \Illuminate\Support\Str::limit($session->user_agent ?: __('Unknown device'), 60) }}</span>
                        <span class="block text-xs text-slate-500">
                            {{ $session->ip_address }} · {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                        </span>
                    </span>
                    @if($session->id === $currentSessionId)
                        <span class="badge-green shrink-0">{{ __('Current') }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('account.logout-others') }}" class="flex flex-col sm:flex-row gap-3 sm:items-start">
            @csrf
            <x-field name="password" class="flex-1">
                <input type="password" name="password" required class="input" placeholder="{{ __('Confirm with your password') }}" autocomplete="current-password">
            </x-field>
            <button type="submit" class="btn-secondary">{{ __('Log out other sessions') }}</button>
        </form>
    </div>

    {{-- Login history --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Login history') }}</h2>
        @if($logins->isEmpty())
            <p class="text-sm text-slate-500 py-2">{{ __('No logins recorded yet.') }}</p>
        @else
            <div class="space-y-2">
                @foreach($logins as $login)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2 bg-slate-50 dark:bg-slate-800/60 text-sm">
                        <span class="min-w-0 flex-1">
                            <span class="font-medium">{{ $login->ip }}</span>
                            @if($login->country)<span class="text-slate-500"> · {{ $login->country }}</span>@endif
                        </span>
                        <span class="{{ $login->success ? 'badge-green' : 'badge-red' }}">{{ $login->success ? __('Success') : __('Failed') }}</span>
                        <span class="text-xs text-slate-500 shrink-0">{{ $login->created_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- White-label branding --}}
    @php($canBrand = auth()->user()->currentPlan()->hasFeature('remove_branding'))
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('White-label branding') }}</h2>
        @if($canBrand)
            <form method="POST" action="{{ route('account.branding') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid sm:grid-cols-2 gap-4">
                    <x-field name="brand_name" :label="__('Brand name')">
                        <input type="text" name="brand_name" value="{{ old('brand_name', auth()->user()->branding['name'] ?? '') }}" class="input">
                    </x-field>
                    <x-field name="brand_color" :label="__('Brand color')">
                        <input type="color" name="brand_color" value="{{ old('brand_color', auth()->user()->branding['color'] ?? '#6366f1') }}" class="input !p-1.5 h-11 w-full">
                    </x-field>
                </div>
                <x-field name="brand_logo" :label="__('Logo')" :help="__('Shown on your bio pages and link pages instead of the default branding.')">
                    @if(auth()->user()->branding['logo'] ?? null)
                        <img src="{{ storage_url(auth()->user()->branding['logo']) }}" alt="" class="mb-2 h-10 w-auto rounded-lg object-contain">
                    @endif
                    <input type="file" name="brand_logo" accept="image/*" class="input !p-2.5">
                </x-field>
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">{{ __('Save branding') }}</button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ __('Replace the default branding on your bio pages and link pages with your own name, color and logo.') }}
            </p>
            <a href="{{ route('billing.plans') }}" class="btn-secondary">
                <x-icon name="sparkles" class="h-4 w-4"/> {{ __('White-label branding is available on higher plans.') }}
            </a>
        @endif
    </div>

    {{-- Danger zone --}}
    <div class="card card-pad ring-2 !ring-rose-200 dark:!ring-rose-900">
        <h2 class="font-semibold text-rose-600 dark:text-rose-400 mb-4">{{ __('Danger zone') }}</h2>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('account.export') }}" class="btn-secondary">
                <x-icon name="download" class="h-4 w-4"/> {{ __('Export my data (JSON)') }}
            </a>
            <button type="button" class="btn-danger" @click="$dispatch('open-modal', 'delete-account')">
                <x-icon name="trash" class="h-4 w-4"/> {{ __('Delete my account') }}
            </button>
        </div>
    </div>

    <x-modal name="delete-account" :title="__('Delete account')">
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
            {{ __('This permanently deletes your account, links, statistics, domains and bio pages. This action cannot be undone.') }}
        </p>
        <form method="POST" action="{{ route('account.destroy') }}" class="space-y-4">
            @csrf
            @method('DELETE')
            <x-field name="password" :label="__('Your password')">
                <input type="password" name="password" required class="input" autocomplete="current-password">
            </x-field>
            <x-field name="confirm_text" :label="__('Type DELETE to confirm')">
                <input type="text" name="confirm_text" required class="input" placeholder="DELETE" autocomplete="off">
            </x-field>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                <button type="submit" class="btn-danger">{{ __('Delete forever') }}</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
