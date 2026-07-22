@extends('layouts.admin')

@section('title', __('Payments') . ' — ' . site_name())
@section('page-title', __('Payments'))

@section('content')
<div class="space-y-5">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.payments.index') }}" class="card card-pad">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label for="status" class="label">{{ __('Status') }}</label>
                <select id="status" name="status" class="input">
                    <option value="">{{ __('Any') }}</option>
                    @foreach(['pending' => __('Pending'), 'completed' => __('Completed'), 'declined' => __('Declined'), 'refunded' => __('Refunded')] as $s => $label)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="gateway" class="label">{{ __('Gateway') }}</label>
                <input type="text" id="gateway" name="gateway" value="{{ request('gateway') }}" class="input" placeholder="stripe, paypal, upi…">
            </div>
            <label class="inline-flex items-center gap-2.5 min-h-touch cursor-pointer text-sm font-medium">
                <input type="checkbox" name="manual" value="1" class="checkbox" @checked(request('manual'))>
                {{ __('Manual payments awaiting review') }}
            </label>
            <div class="flex gap-2">
                <button class="btn-primary btn-sm">{{ __('Filter') }}</button>
                @if(request()->hasAny(['status', 'gateway', 'manual']))
                    <a href="{{ route('admin.payments.index') }}" class="btn-secondary btn-sm">{{ __('Reset') }}</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Invoice') }}</th>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Plan') }}</th>
                    <th>{{ __('Total') }}</th>
                    <th>{{ __('Gateway') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td data-label="{{ __('Invoice') }}" class="font-mono text-xs">{{ $p->invoice_number ?? '#' . $p->id }}</td>
                        <td data-label="{{ __('User') }}">
                            @if($p->user)
                                <a href="{{ route('admin.users.edit', $p->user) }}" class="text-brand-600 hover:underline">{{ $p->user->name }}</a>
                            @else
                                {{ __('Deleted user') }}
                            @endif
                        </td>
                        <td data-label="{{ __('Plan') }}">{{ $p->plan?->name ?? '—' }} <span class="text-xs text-slate-400">{{ $p->cycle }}</span></td>
                        <td data-label="{{ __('Total') }}" class="font-semibold">{{ format_money($p->total, $p->currency) }}</td>
                        <td data-label="{{ __('Gateway') }}">
                            <span class="block">{{ $p->gateway }}</span>
                            @if($p->gateway_reference)
                                <span class="block max-w-[10rem] truncate font-mono text-xs text-slate-400" title="{{ $p->gateway_reference }}">{{ $p->gateway_reference }}</span>
                            @endif
                            @if(in_array($p->gateway, ['upi', 'bank']) && ($p->meta['note'] ?? null))
                                <span class="block max-w-[12rem] truncate text-xs text-slate-500" title="{{ $p->meta['note'] }}">{{ __('Note:') }} {{ $p->meta['note'] }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Status') }}">
                            @if($p->status === 'completed')<span class="badge-green">{{ __('Completed') }}</span>
                            @elseif($p->status === 'pending')<span class="badge-amber">{{ __('Pending') }}</span>
                            @elseif($p->status === 'refunded')<span class="badge-gray">{{ __('Refunded') }}</span>
                            @else<span class="badge-red">{{ __(ucfirst($p->status)) }}</span>@endif
                        </td>
                        <td data-label="{{ __('Date') }}">{{ $p->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            @if($p->status === 'pending')
                                <span class="inline-flex items-center gap-1.5">
                                    <x-confirm :action="route('admin.payments.approve', $p)" method="POST"
                                               :title="__('Approve this payment?')"
                                               :message="__('The plan will be activated for :name immediately.', ['name' => $p->user?->name ?? __('the user')])"
                                               :button="__('Approve')">
                                        <button type="button" class="btn-primary btn-sm">{{ __('Approve') }}</button>
                                    </x-confirm>
                                    <button type="button" class="btn-danger btn-sm" @click="$dispatch('open-modal', 'decline-{{ $p->id }}')">{{ __('Decline') }}</button>
                                </span>
                                <x-modal name="decline-{{ $p->id }}" :title="__('Decline payment')">
                                    <form method="POST" action="{{ route('admin.payments.decline', $p) }}" class="space-y-3">
                                        @csrf
                                        <div>
                                            <label class="label" for="decline-reason-{{ $p->id }}">{{ __('Reason (optional)') }}</label>
                                            <textarea id="decline-reason-{{ $p->id }}" name="reason" rows="3" class="input" placeholder="{{ __('Shown in the audit trail…') }}"></textarea>
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                                            <button class="btn-danger">{{ __('Decline payment') }}</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @elseif($p->status === 'completed')
                                <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'refund-{{ $p->id }}')">{{ __('Refund') }}</button>
                                <x-modal name="refund-{{ $p->id }}" :title="__('Mark as refunded')">
                                    <form method="POST" action="{{ route('admin.payments.refund', $p) }}" class="space-y-3">
                                        @csrf
                                        <p class="text-sm text-slate-500">{{ __('This records the refund and downgrades the user to the free plan. Move the money in your gateway dashboard.') }}</p>
                                        <div>
                                            <label class="label" for="refund-reason-{{ $p->id }}">{{ __('Note (optional)') }}</label>
                                            <textarea id="refund-reason-{{ $p->id }}" name="reason" rows="3" class="input"></textarea>
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                                            <button class="btn-danger">{{ __('Mark refunded') }}</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No payments found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $payments->links() }}
</div>
@endsection
