@extends('layouts.admin')

@section('title', __('Waitlist') . ' — ' . site_name())
@section('page-title', __('Waitlist'))

@section('content')
<div class="space-y-5">

    {{-- Stat cards: total + per-feature counts --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <x-stat-card :label="__('Total signups')" :value="format_number($total)" icon="users"/>
        @foreach($byFeature as $feature => $count)
            <x-stat-card :label="$feature" :value="format_number($count)" icon="megaphone"/>
        @endforeach
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.waitlist') }}" class="card card-pad">
        <div class="grid sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <label for="feature" class="label">{{ __('Feature') }}</label>
                <select id="feature" name="feature" class="input">
                    <option value="">{{ __('All features') }}</option>
                    @foreach($byFeature->keys() as $feature)
                        <option value="{{ $feature }}" @selected(request('feature') === $feature)>{{ $feature }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button class="btn-primary btn-sm">{{ __('Filter') }}</button>
            @if(request('feature'))
                <a href="{{ route('admin.waitlist') }}" class="btn-secondary btn-sm">{{ __('Reset') }}</a>
            @endif
        </div>
    </form>

    {{-- CSV export note --}}
    <div class="card card-pad flex items-start gap-3 text-sm text-slate-500 dark:text-slate-400">
        <x-icon name="download" class="h-5 w-5 shrink-0 text-brand-600"/>
        <p>{{ __('Need these addresses elsewhere? Copy the emails below or export the waitlist_entries table to CSV from your database console.') }}</p>
    </div>

    {{-- Entries table --}}
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Feature') }}</th>
                    <th>{{ __('IP') }}</th>
                    <th>{{ __('Joined') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td data-label="{{ __('Email') }}">
                            <span class="font-medium break-all">{{ $entry->email }}</span>
                        </td>
                        <td data-label="{{ __('Feature') }}">
                            <span class="badge-amber">{{ $entry->feature }}</span>
                        </td>
                        <td data-label="{{ __('IP') }}" class="text-slate-500">{{ $entry->ip ?: '—' }}</td>
                        <td data-label="{{ __('Joined') }}">{{ $entry->created_at->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No signups yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $entries->links() }}
</div>
@endsection
