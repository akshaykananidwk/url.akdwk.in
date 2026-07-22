@extends('layouts.admin')

@section('title', __('Coupons') . ' — ' . site_name())
@section('page-title', __('Coupons'))

@section('content')
<div class="space-y-5">

    {{-- Create coupon --}}
    <form method="POST" action="{{ route('admin.coupons.store') }}" class="card card-pad space-y-4">
        @csrf
        <h2 class="font-semibold">{{ __('Create coupon') }}</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <x-field name="code" :label="__('Code')">
                <input type="text" id="code" name="code" value="{{ old('code') }}" class="input uppercase" placeholder="SUMMER20" required>
            </x-field>
            <x-field name="type" :label="__('Type')">
                <select id="type" name="type" class="input">
                    <option value="percent" @selected(old('type') === 'percent')>{{ __('Percent off') }}</option>
                    <option value="fixed" @selected(old('type') === 'fixed')>{{ __('Fixed amount') }}</option>
                </select>
            </x-field>
            <x-field name="value" :label="__('Value')">
                <input type="number" step="0.01" min="0.01" id="value" name="value" value="{{ old('value') }}" class="input" required>
            </x-field>
            <x-field name="max_uses" :label="__('Max uses')" :help="__('Empty = unlimited')">
                <input type="number" min="1" id="max_uses" name="max_uses" value="{{ old('max_uses') }}" class="input">
            </x-field>
            <x-field name="expires_at" :label="__('Expires')">
                <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at') }}" class="input">
            </x-field>
        </div>
        <div>
            <p class="label mb-2">{{ __('Restrict to plans') }} <span class="font-normal text-slate-400">({{ __('none = all plans') }})</span></p>
            <div class="flex flex-wrap gap-x-5 gap-y-1">
                @foreach($plans as $plan)
                    <label class="inline-flex items-center gap-2 min-h-touch cursor-pointer text-sm">
                        <input type="checkbox" name="plan_ids[]" value="{{ $plan->id }}" class="checkbox" @checked(in_array($plan->id, old('plan_ids', [])))>
                        {{ $plan->name }}
                    </label>
                @endforeach
            </div>
        </div>
        <button class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('Create coupon') }}</button>
    </form>

    {{-- Coupons table --}}
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Discount') }}</th>
                    <th>{{ __('Used') }}</th>
                    <th>{{ __('Expires') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td data-label="{{ __('Code') }}"><span class="font-mono font-semibold">{{ $coupon->code }}</span></td>
                        <td data-label="{{ __('Discount') }}">
                            {{ $coupon->type === 'percent' ? $coupon->value . '%' : format_money($coupon->value) }}
                        </td>
                        <td data-label="{{ __('Used') }}">{{ $coupon->used_count }} / {{ $coupon->max_uses ?? '∞' }}</td>
                        <td data-label="{{ __('Expires') }}">{{ $coupon->expires_at?->format('M j, Y') ?? __('Never') }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($coupon->active)<span class="badge-green">{{ __('Active') }}</span>
                            @else<span class="badge-gray">{{ __('Inactive') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5">
                                <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}">
                                    @csrf
                                    <button class="btn-secondary btn-sm">{{ $coupon->active ? __('Deactivate') : __('Activate') }}</button>
                                </form>
                                <x-confirm :action="route('admin.coupons.destroy', $coupon)" method="DELETE" :title="__('Delete this coupon?')">
                                    <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                </x-confirm>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No coupons yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $coupons->links() }}
</div>
@endsection
