@extends('layouts.app')

@section('title', __('Team') . ' — ' . site_name())
@section('page-title', __('Team'))

@section('content')
<div class="space-y-5">

    @if(session('invite_url'))
        <div class="card card-pad !py-4 flex flex-wrap items-center gap-3 ring-2 ring-brand-500">
            <div class="min-w-0 flex-1">
                <p class="text-xs text-slate-500">{{ __('Invitation link — share it directly if the email does not arrive') }}</p>
                <p class="font-semibold truncate text-sm">{{ session('invite_url') }}</p>
            </div>
            <button type="button" class="btn-primary btn-sm" onclick="copyText(@js(session('invite_url')))">
                <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy') }}
            </button>
        </div>
    @endif

    {{-- My workspace --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('My workspace') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">{{ __('Invite people to collaborate on your links.') }}</p>

        <form method="POST" action="{{ route('team.invite') }}" class="flex flex-col sm:flex-row gap-3 sm:items-start mb-5">
            @csrf
            <x-field name="email" class="flex-1">
                <input type="email" name="email" value="{{ old('email') }}" required class="input" placeholder="teammate@example.com">
            </x-field>
            <x-field name="role">
                <select name="role" class="input sm:w-40" aria-label="{{ __('Role') }}">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" @selected(old('role', 'editor') === $key)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </x-field>
            <button type="submit" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> {{ __('Invite') }}</button>
        </form>

        @if($members->isEmpty())
            <x-empty-state icon="users" :title="__('No team members yet')" :description="__('Invite a teammate above to share your workspace.')"/>
        @else
            <div class="space-y-3">
                @foreach($members as $member)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm truncate">
                                {{ $member->user?->name ?? $member->email }}
                                @unless($member->accepted_at)
                                    <span class="badge-amber ms-1">{{ __('Pending') }}</span>
                                @endunless
                            </p>
                            <p class="text-xs text-slate-500 truncate">{{ $member->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('team.update', $member) }}">
                            @csrf
                            @method('PUT')
                            <select name="role" class="input !w-36" onchange="this.form.submit()" aria-label="{{ __('Role') }}">
                                @foreach($roles as $key => $label)
                                    <option value="{{ $key }}" @selected($member->role === $key)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <x-confirm :action="route('team.destroy', $member)" method="DELETE" :title="__('Remove this member?')"
                                   :message="__('They will lose access to your workspace immediately.')" :button="__('Remove')">
                            <button type="button" class="btn-danger btn-sm self-start sm:self-center"><x-icon name="trash" class="h-4 w-4"/> {{ __('Remove') }}</button>
                        </x-confirm>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Workspaces I belong to --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-4">{{ __('Workspaces I belong to') }}</h2>
        @if($workspaces->isEmpty())
            <p class="text-sm text-slate-500 py-4 text-center">{{ __('You have not joined any other workspace.') }}</p>
        @else
            <div class="space-y-3">
                @foreach($workspaces as $workspace)
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm truncate">{{ $workspace->owner?->name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $workspace->owner?->email }}</p>
                        </div>
                        <span class="badge-brand">{{ __($roles[$workspace->role] ?? $workspace->role) }}</span>
                        <x-confirm :action="route('team.destroy', $workspace)" method="DELETE" :title="__('Leave this workspace?')"
                                   :message="__('You will lose access to its links. The owner can invite you again.')" :button="__('Leave')">
                            <button type="button" class="btn-danger btn-sm self-start sm:self-center"><x-icon name="logout" class="h-4 w-4"/> {{ __('Leave') }}</button>
                        </x-confirm>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
