@extends('layouts.admin')

@section('title', __('Links') . ' — ' . site_name())
@section('page-title', __('Links'))

@section('content')
<div class="space-y-5">

    <form method="GET" action="{{ route('admin.links.index') }}" class="card card-pad flex flex-col sm:flex-row gap-3">
        <input type="search" name="q" value="{{ request('q') }}" class="input sm:max-w-xs" placeholder="{{ __('Search alias or destination…') }}">
        @if(request('user'))
            <input type="hidden" name="user" value="{{ request('user') }}">
        @endif
        <div class="flex gap-2">
            <button class="btn-primary btn-sm">{{ __('Search') }}</button>
            @if(request()->hasAny(['q', 'user']))
                <a href="{{ route('admin.links.index') }}" class="btn-secondary btn-sm">{{ __('Reset') }}</a>
            @endif
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Short link') }}</th>
                    <th>{{ __('Destination') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Clicks') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($links as $link)
                    <tr>
                        <td data-label="{{ __('Short link') }}">
                            <a href="{{ $link->shortUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-medium hover:text-brand-600 min-w-0">
                                <span class="truncate">{{ $link->shortUrl() }}</span>
                                <x-icon name="external" class="h-3.5 w-3.5 shrink-0 text-slate-400"/>
                            </a>
                        </td>
                        <td data-label="{{ __('Destination') }}">
                            <span class="block max-w-[16rem] truncate text-slate-500" title="{{ $link->destination }}">{{ $link->destination }}</span>
                        </td>
                        <td data-label="{{ __('Owner') }}">
                            @if($link->user)
                                <a href="{{ route('admin.users.edit', $link->user) }}" class="text-brand-600 hover:underline">{{ $link->user->name }}</a>
                            @else
                                <span class="badge-gray">{{ __('Guest') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Clicks') }}">{{ format_number($link->clicks_count) }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($link->disabled)<span class="badge-red">{{ __('Disabled') }}</span>
                            @else<span class="badge-green">{{ __('Active') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5">
                                <form method="POST" action="{{ route('admin.links.toggle', $link) }}">
                                    @csrf
                                    <button class="btn-secondary btn-sm">{{ $link->disabled ? __('Enable') : __('Disable') }}</button>
                                </form>
                                <x-confirm :action="route('admin.links.destroy', $link)" method="DELETE" :title="__('Delete this link?')">
                                    <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                </x-confirm>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No links found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $links->links() }}
</div>
@endsection
