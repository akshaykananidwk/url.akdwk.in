@extends('layouts.admin')

@section('title', __('Bio pages') . ' — ' . site_name())
@section('page-title', __('Bio pages'))

@section('content')
<div class="space-y-5">
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Page') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Blocks') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td data-label="{{ __('Page') }}">
                            <a href="{{ route('bio.show', $page->username) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-medium hover:text-brand-600 min-w-0">
                                <span class="truncate">{{ '@' . $page->username }}</span>
                                <x-icon name="external" class="h-3.5 w-3.5 shrink-0 text-slate-400"/>
                            </a>
                        </td>
                        <td data-label="{{ __('Owner') }}">
                            @if($page->user)
                                <a href="{{ route('admin.users.edit', $page->user) }}" class="text-brand-600 hover:underline">{{ $page->user->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Blocks') }}">{{ format_number($page->blocks_count) }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($page->active)<span class="badge-green">{{ __('Active') }}</span>
                            @else<span class="badge-red">{{ __('Disabled') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Created') }}">{{ $page->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5">
                                <form method="POST" action="{{ route('admin.bio.toggle', $page) }}">
                                    @csrf
                                    <button class="btn-secondary btn-sm">{{ $page->active ? __('Disable') : __('Enable') }}</button>
                                </form>
                                <x-confirm :action="route('admin.bio.destroy', $page)" method="DELETE"
                                           :title="__('Delete this bio page?')"
                                           :message="__('All of its blocks and subscribers will be deleted too.')">
                                    <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                </x-confirm>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No bio pages found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $pages->links() }}
</div>
@endsection
