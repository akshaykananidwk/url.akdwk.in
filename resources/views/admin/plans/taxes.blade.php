@extends('layouts.admin')

@section('title', __('Tax rates') . ' — ' . site_name())
@section('page-title', __('Tax rates'))

@section('content')
<div class="space-y-5">

    {{-- Create tax rate --}}
    <form method="POST" action="{{ route('admin.taxes.store') }}" class="card card-pad space-y-4">
        @csrf
        <h2 class="font-semibold">{{ __('Add tax rate') }}</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-field name="name" :label="__('Name')">
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="input" placeholder="GST, VAT…" required>
            </x-field>
            <x-field name="country" :label="__('Country code')" :help="__('2-letter code — empty = all countries')">
                <input type="text" id="country" name="country" value="{{ old('country') }}" maxlength="2" class="input uppercase" placeholder="IN">
            </x-field>
            <x-field name="rate" :label="__('Rate (%)')">
                <input type="number" step="0.01" min="0" max="100" id="rate" name="rate" value="{{ old('rate') }}" class="input" required>
            </x-field>
            <x-field name="tax_id_label" :label="__('Tax ID label')" :help="__('e.g. GSTIN — asked at checkout')">
                <input type="text" id="tax_id_label" name="tax_id_label" value="{{ old('tax_id_label') }}" class="input" placeholder="GSTIN">
            </x-field>
        </div>
        <button class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add tax rate') }}</button>
    </form>

    {{-- Tax rates table --}}
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Country') }}</th>
                    <th>{{ __('Rate') }}</th>
                    <th>{{ __('Tax ID label') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($taxes as $tax)
                    <tr>
                        <td data-label="{{ __('Name') }}" class="font-medium">{{ $tax->name }}</td>
                        <td data-label="{{ __('Country') }}">{{ $tax->country ?? __('All countries') }}</td>
                        <td data-label="{{ __('Rate') }}">{{ rtrim(rtrim(number_format($tax->rate, 2), '0'), '.') }}%</td>
                        <td data-label="{{ __('Tax ID label') }}">{{ $tax->tax_id_label ?? '—' }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($tax->active)<span class="badge-green">{{ __('Active') }}</span>
                            @else<span class="badge-gray">{{ __('Inactive') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5">
                                <form method="POST" action="{{ route('admin.taxes.toggle', $tax) }}">
                                    @csrf
                                    <button class="btn-secondary btn-sm">{{ $tax->active ? __('Deactivate') : __('Activate') }}</button>
                                </form>
                                <x-confirm :action="route('admin.taxes.destroy', $tax)" method="DELETE" :title="__('Delete this tax rate?')">
                                    <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                </x-confirm>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No tax rates yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
