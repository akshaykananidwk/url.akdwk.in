@extends('layouts.admin')

@section('title', __('Spaces') . ' — ' . site_name())
@section('page-title', __('Spaces'))

@section('content')
<div class="space-y-5">
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Space') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Links') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($spaces as $space)
                    <tr>
                        <td data-label="{{ __('Space') }}">
                            <span class="inline-flex items-center gap-2 font-medium">
                                <span class="h-3 w-3 rounded-full shrink-0" style="background: {{ $space->color ?: '#94a3b8' }}"></span>
                                {{ $space->name }}
                            </span>
                        </td>
                        <td data-label="{{ __('Owner') }}">
                            @if($space->user)
                                <a href="{{ route('admin.users.edit', $space->user) }}" class="text-brand-600 hover:underline">{{ $space->user->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Links') }}">{{ format_number($space->links_count) }}</td>
                        <td data-label="{{ __('Created') }}">{{ $space->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <x-confirm :action="route('admin.spaces.destroy', $space)" method="DELETE"
                                       :title="__('Delete this space?')"
                                       :message="__('Its links will be kept and moved out of the space.')">
                                <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/> {{ __('Delete') }}</button>
                            </x-confirm>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No spaces found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $spaces->links() }}
</div>
@endsection
