@extends('layouts.admin')

@section('title', __('Edit user') . ' — ' . site_name())
@section('page-title', $user->name)

@section('content')
<div class="space-y-5">

    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline min-h-touch">
        <x-icon name="chevron-right" class="h-4 w-4 rotate-180 rtl:rotate-0"/> {{ __('Back to users') }}
    </a>

    <div class="grid lg:grid-cols-3 gap-5 items-start">

        {{-- Profile form --}}
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card card-pad lg:col-span-2 space-y-4"
              x-data="{ role: @js(old('role', $user->role)) }">
            @csrf
            @method('PUT')
            <h2 class="font-semibold">{{ __('Profile') }}</h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <x-field name="name" :label="__('Name')">
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                </x-field>
                <x-field name="email" :label="__('Email')">
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                </x-field>
                <x-field name="password" :label="__('New password')" :help="__('Leave blank to keep the current password.')">
                    <input type="password" id="password" name="password" class="input" autocomplete="new-password">
                </x-field>
                <x-field name="role" :label="__('Role')">
                    <select id="role" name="role" class="input" x-model="role">
                        <option value="user">{{ __('User') }}</option>
                        <option value="staff">{{ __('Staff') }}</option>
                        <option value="admin">{{ __('Admin') }}</option>
                    </select>
                </x-field>
            </div>

            {{-- Staff permissions --}}
            <div x-cloak x-show="role === 'staff'" class="rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-4">
                <p class="label mb-3">{{ __('Staff permissions') }}</p>
                <div class="grid sm:grid-cols-2 gap-2">
                    @foreach($adminPermissions as $key => $label)
                        <label class="inline-flex items-center gap-2.5 min-h-touch cursor-pointer text-sm">
                            <input type="checkbox" name="permissions[{{ $key }}]" value="1" class="checkbox"
                                   @checked(in_array($key, $user->permissions ?? []))>
                            {{ __($label) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <h3 class="font-semibold pt-2">{{ __('Subscription') }}</h3>
            <div class="grid sm:grid-cols-3 gap-4">
                <x-field name="plan_id" :label="__('Plan')">
                    <select id="plan_id" name="plan_id" class="input">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id', $user->plan_id) == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="plan_cycle" :label="__('Billing cycle')">
                    <select id="plan_cycle" name="plan_cycle" class="input">
                        @foreach(['monthly' => __('Monthly'), 'yearly' => __('Yearly'), 'lifetime' => __('Lifetime')] as $c => $label)
                            <option value="{{ $c }}" @selected(old('plan_cycle', $user->plan_cycle) === $c)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="plan_expires_at" :label="__('Plan expires')" :help="__('Leave empty for no expiry.')">
                    <input type="datetime-local" id="plan_expires_at" name="plan_expires_at" class="input"
                           value="{{ old('plan_expires_at', $user->plan_expires_at?->format('Y-m-d\TH:i')) }}">
                </x-field>
            </div>

            <div class="pt-2">
                <button class="btn-primary">{{ __('Save changes') }}</button>
            </div>
        </form>

        <div class="space-y-5">
            {{-- Info card --}}
            <div class="card card-pad">
                <div class="flex items-center gap-3">
                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-12 w-12 rounded-full">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                    </div>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <dt class="text-slate-500">{{ __('Links') }}</dt><dd class="text-end font-medium">{{ format_number($user->links_count) }}</dd>
                    <dt class="text-slate-500">{{ __('Spaces') }}</dt><dd class="text-end font-medium">{{ format_number($user->spaces_count) }}</dd>
                    <dt class="text-slate-500">{{ __('Domains') }}</dt><dd class="text-end font-medium">{{ format_number($user->domains_count) }}</dd>
                    <dt class="text-slate-500">{{ __('Bio pages') }}</dt><dd class="text-end font-medium">{{ format_number($user->bio_pages_count) }}</dd>
                    <dt class="text-slate-500">{{ __('Joined') }}</dt><dd class="text-end font-medium">{{ $user->created_at->format('M j, Y') }}</dd>
                    <dt class="text-slate-500">{{ __('Last login') }}</dt><dd class="text-end font-medium">{{ $user->last_login_at?->diffForHumans() ?? __('Never') }}</dd>
                </dl>
                <div class="mt-4 flex items-center gap-2 flex-wrap">
                    @if($user->email_verified_at)
                        <span class="badge-green">{{ __('Email verified') }}</span>
                    @else
                        <span class="badge-amber">{{ __('Unverified') }}</span>
                        <form method="POST" action="{{ route('admin.users.verify', $user) }}">
                            @csrf
                            <button class="btn-secondary btn-sm">{{ __('Mark verified') }}</button>
                        </form>
                    @endif
                    @if($user->suspended_at)
                        <span class="badge-red">{{ __('Suspended') }}</span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="card card-pad space-y-2">
                <h2 class="font-semibold mb-1">{{ __('Actions') }}</h2>

                @unless($user->isAdmin())
                    <x-confirm :action="route('admin.users.impersonate', $user)" method="POST"
                               :title="__('Impersonate this user?')"
                               :message="__('You will be logged in as :name. Log out to return to your admin account.', ['name' => $user->name])"
                               :button="__('Impersonate')">
                        <button type="button" class="btn-secondary btn-sm w-full justify-center">
                            <x-icon name="eye" class="h-4 w-4"/> {{ __('Impersonate') }}
                        </button>
                    </x-confirm>
                @endunless

                @if($user->role !== 'admin')
                    <x-confirm :action="route('admin.users.suspend', $user)" method="POST"
                               :title="$user->suspended_at ? __('Unsuspend this user?') : __('Suspend this user?')"
                               :message="$user->suspended_at ? __('Their links will start redirecting again.') : __('All of their links will stop redirecting.')"
                               :button="$user->suspended_at ? __('Unsuspend') : __('Suspend')">
                        <button type="button" class="btn-secondary btn-sm w-full justify-center">
                            <x-icon name="shield" class="h-4 w-4"/>
                            {{ $user->suspended_at ? __('Unsuspend user') : __('Suspend user') }}
                        </button>
                    </x-confirm>
                @endif

                <x-confirm :action="route('admin.users.destroy', $user)" method="DELETE"
                           :title="__('Delete this user?')"
                           :message="__('All of their links, spaces, domains and pages will be permanently deleted.')">
                    <button type="button" class="btn-danger btn-sm w-full justify-center">
                        <x-icon name="trash" class="h-4 w-4"/> {{ __('Delete user') }}
                    </button>
                </x-confirm>
            </div>
        </div>
    </div>

    {{-- Recent payments --}}
    <div class="card">
        <div class="card-pad !pb-0"><h2 class="font-semibold">{{ __('Recent payments') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="table-cards">
                <thead>
                    <tr>
                        <th>{{ __('Invoice') }}</th>
                        <th>{{ __('Plan') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Gateway') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td data-label="{{ __('Invoice') }}" class="font-mono text-xs">{{ $p->invoice_number ?? '#' . $p->id }}</td>
                            <td data-label="{{ __('Plan') }}">{{ $p->plan?->name ?? '—' }}</td>
                            <td data-label="{{ __('Total') }}" class="font-medium">{{ format_money($p->total, $p->currency) }}</td>
                            <td data-label="{{ __('Gateway') }}">{{ $p->gateway }}</td>
                            <td data-label="{{ __('Status') }}">
                                <span class="{{ $p->status === 'completed' ? 'badge-green' : ($p->status === 'pending' ? 'badge-amber' : 'badge-red') }}">{{ __(ucfirst($p->status)) }}</span>
                            </td>
                            <td data-label="{{ __('Date') }}">{{ $p->created_at->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-slate-500 py-8 sm:table-cell">{{ __('No payments yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
