@extends('layouts.admin')

@section('title', __('Pixels') . ' — ' . site_name())
@section('page-title', __('Pixels'))

@section('content')
<div class="space-y-5">
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Pixel ID') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pixels as $pixel)
                    <tr>
                        <td data-label="{{ __('Name') }}" class="font-medium">{{ $pixel->name }}</td>
                        <td data-label="{{ __('Type') }}"><span class="badge-gray">{{ $pixel->type }}</span></td>
                        <td data-label="{{ __('Pixel ID') }}">
                            <span class="block max-w-[12rem] truncate font-mono text-xs" title="{{ $pixel->value }}">{{ $pixel->value }}</span>
                        </td>
                        <td data-label="{{ __('Owner') }}">
                            @if($pixel->user)
                                <a href="{{ route('admin.users.edit', $pixel->user) }}" class="text-brand-600 hover:underline">{{ $pixel->user->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Created') }}">{{ $pixel->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <x-confirm :action="route('admin.pixels.destroy', $pixel)" method="DELETE" :title="__('Delete this pixel?')">
                                <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/> {{ __('Delete') }}</button>
                            </x-confirm>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No pixels found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $pixels->links() }}
</div>
@endsection
