@extends('layouts.admin')

@section('title', __('Users') . ' — ' . site_name())
@section('page-title', __('Users'))

@section('content')
<div class="space-y-5">

    {{-- Search + filters --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="card card-pad">
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <label for="q" class="label">{{ __('Search') }}</label>
                <input type="search" id="q" name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Name or email…') }}">
            </div>
            <div>
                <label for="status" class="label">{{ __('Status') }}</label>
                <select id="status" name="status" class="input">
                    <option value="">{{ __('Any') }}</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>{{ __('Suspended') }}</option>
                </select>
            </div>
            <div>
                <label for="role" class="label">{{ __('Role') }}</label>
                <select id="role" name="role" class="input">
                    <option value="">{{ __('Any') }}</option>
                    @foreach(['user' => __('User'), 'staff' => __('Staff'), 'admin' => __('Admin')] as $r => $label)
                        <option value="{{ $r }}" @selected(request('role') === $r)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="plan" class="label">{{ __('Plan') }}</label>
                <select id="plan" name="plan" class="input">
                    <option value="">{{ __('Any') }}</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request('plan') == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button class="btn-primary btn-sm">{{ __('Filter') }}</button>
            @if(request()->hasAny(['q', 'status', 'role', 'plan']))
                <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm">{{ __('Reset') }}</a>
            @endif
        </div>
    </form>

    {{-- Users table --}}
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Plan') }}</th>
                    <th>{{ __('Links') }}</th>
                    <th>{{ __('Role') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Joined') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td data-label="{{ __('User') }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <img src="{{ $u->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full shrink-0">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium">{{ $u->name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ $u->email }}</span>
                                </span>
                            </span>
                        </td>
                        <td data-label="{{ __('Plan') }}">{{ $u->plan?->name ?? '—' }}</td>
                        <td data-label="{{ __('Links') }}">{{ format_number($u->links_count) }}</td>
                        <td data-label="{{ __('Role') }}">
                            @if($u->role === 'admin')<span class="badge-brand">{{ __('Admin') }}</span>
                            @elseif($u->role === 'staff')<span class="badge-amber">{{ __('Staff') }}</span>
                            @else<span class="badge-gray">{{ __('User') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Status') }}">
                            @if($u->suspended_at)<span class="badge-red">{{ __('Suspended') }}</span>
                            @else<span class="badge-green">{{ __('Active') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Joined') }}">{{ $u->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn-secondary btn-sm">
                                <x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No users found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
