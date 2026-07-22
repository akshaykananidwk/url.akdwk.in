@extends('layouts.admin')

@section('title', __('Plans') . ' — ' . site_name())
@section('page-title', __('Plans'))

@section('content')
<div class="space-y-5">

    <div class="flex justify-end">
        <a href="{{ route('admin.plans.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('New plan') }}</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Plan') }}</th>
                    <th>{{ __('Monthly') }}</th>
                    <th>{{ __('Yearly') }}</th>
                    <th>{{ __('Lifetime') }}</th>
                    <th>{{ __('Users') }}</th>
                    <th>{{ __('Flags') }}</th>
                    <th>{{ __('Sort') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td data-label="{{ __('Plan') }}">
                            <span class="block font-medium">{{ $plan->name }}</span>
                            @if($plan->description)<span class="block max-w-[16rem] truncate text-xs text-slate-500">{{ $plan->description }}</span>@endif
                        </td>
                        <td data-label="{{ __('Monthly') }}">{{ $plan->is_free ? __('Free') : format_money($plan->price_monthly) }}</td>
                        <td data-label="{{ __('Yearly') }}">{{ $plan->is_free ? '—' : format_money($plan->price_yearly) }}</td>
                        <td data-label="{{ __('Lifetime') }}">{{ $plan->is_free ? '—' : format_money($plan->price_lifetime) }}</td>
                        <td data-label="{{ __('Users') }}">{{ format_number($plan->users_count) }}</td>
                        <td data-label="{{ __('Flags') }}">
                            <span class="inline-flex flex-wrap gap-1">
                                @if($plan->is_default)<span class="badge-brand">{{ __('Default') }}</span>@endif
                                @if($plan->is_featured)<span class="badge-amber">{{ __('Featured') }}</span>@endif
                                @if($plan->active)<span class="badge-green">{{ __('Active') }}</span>
                                @else<span class="badge-red">{{ __('Inactive') }}</span>@endif
                            </span>
                        </td>
                        <td data-label="{{ __('Sort') }}">{{ $plan->sort_order }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5">
                                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}</a>
                                @unless($plan->is_default)
                                    <x-confirm :action="route('admin.plans.destroy', $plan)" method="DELETE"
                                               :title="__('Delete this plan?')"
                                               :message="__('Its users will be moved to the default plan.')">
                                        <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                    </x-confirm>
                                @endunless
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No plans yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
