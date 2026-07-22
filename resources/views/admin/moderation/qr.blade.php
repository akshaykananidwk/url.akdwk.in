@extends('layouts.admin')

@section('title', __('QR codes') . ' — ' . site_name())
@section('page-title', __('QR codes'))

@section('content')
<div class="space-y-5">
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Link') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Scans') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($codes as $code)
                    <tr>
                        <td data-label="{{ __('Name') }}" class="font-medium">{{ $code->name ?: __('Untitled') }}</td>
                        <td data-label="{{ __('Link') }}">
                            @if($code->link)
                                <a href="{{ $code->link->shortUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-brand-600 min-w-0">
                                    <span class="truncate max-w-[12rem]">{{ $code->link->alias }}</span>
                                    <x-icon name="external" class="h-3.5 w-3.5 shrink-0 text-slate-400"/>
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Owner') }}">
                            @if($code->user)
                                <a href="{{ route('admin.users.edit', $code->user) }}" class="text-brand-600 hover:underline">{{ $code->user->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Scans') }}">{{ format_number($code->scans) }}</td>
                        <td data-label="{{ __('Created') }}">{{ $code->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <x-confirm :action="route('admin.qr.destroy', $code)" method="DELETE" :title="__('Delete this QR code?')">
                                <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/> {{ __('Delete') }}</button>
                            </x-confirm>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No QR codes found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $codes->links() }}
</div>
@endsection
