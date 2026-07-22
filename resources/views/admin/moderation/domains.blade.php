@extends('layouts.admin')

@section('title', __('Domains') . ' — ' . site_name())
@section('page-title', __('Domains'))

@section('content')
<div class="space-y-5">

    {{-- Add global domain --}}
    <form method="POST" action="{{ route('admin.domains.store') }}" class="card card-pad">
        @csrf
        <h2 class="font-semibold mb-1">{{ __('Add global domain') }}</h2>
        <p class="help mb-4">{{ __('Global domains are available to every user as a short-link domain. Point its DNS at this server first.') }}</p>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-field name="domain" :label="__('Domain')">
                <input type="text" id="domain" name="domain" value="{{ old('domain') }}" class="input" placeholder="lnk.example.com" required>
            </x-field>
            <x-field name="index_redirect" :label="__('Homepage redirect')" :help="__('Optional — where the bare domain should redirect.')">
                <input type="url" id="index_redirect" name="index_redirect" value="{{ old('index_redirect') }}" class="input" placeholder="https://example.com">
            </x-field>
        </div>
        <div class="mt-3">
            <button class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add domain') }}</button>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Domain') }}</th>
                    <th>{{ __('Owner') }}</th>
                    <th>{{ __('Links') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($domains as $domain)
                    <tr>
                        <td data-label="{{ __('Domain') }}" class="font-medium">{{ $domain->domain }}</td>
                        <td data-label="{{ __('Owner') }}">
                            @if($domain->user_id === null)
                                <span class="badge-brand">{{ __('Global') }}</span>
                            @elseif($domain->user)
                                <a href="{{ route('admin.users.edit', $domain->user) }}" class="text-brand-600 hover:underline">{{ $domain->user->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="{{ __('Links') }}">{{ format_number($domain->links_count) }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($domain->verified_at)<span class="badge-green">{{ __('Verified') }}</span>
                            @else<span class="badge-amber">{{ __('Pending') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Created') }}">{{ $domain->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <x-confirm :action="route('admin.domains.destroy', $domain)" method="DELETE"
                                       :title="__('Delete this domain?')"
                                       :message="__('Domains that still have links cannot be deleted.')">
                                <button type="button" class="btn-danger btn-sm"><x-icon name="trash" class="h-4 w-4"/> {{ __('Delete') }}</button>
                            </x-confirm>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No domains yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $domains->links() }}
</div>
@endsection
