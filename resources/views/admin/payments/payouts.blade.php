@extends('layouts.admin')

@section('title', __('Payouts') . ' — ' . site_name())
@section('page-title', __('Affiliate payouts'))

@section('content')
<div class="space-y-5">
    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Method') }}</th>
                    <th>{{ __('Details') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Requested') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $payout)
                    <tr>
                        <td data-label="{{ __('User') }}">
                            @if($payout->user)
                                <a href="{{ route('admin.users.edit', $payout->user) }}" class="text-brand-600 hover:underline">{{ $payout->user->name }}</a>
                            @else
                                {{ __('Deleted user') }}
                            @endif
                        </td>
                        <td data-label="{{ __('Amount') }}" class="font-semibold">{{ format_money($payout->amount) }}</td>
                        <td data-label="{{ __('Method') }}"><span class="badge-gray">{{ $payout->method }}</span></td>
                        <td data-label="{{ __('Details') }}">
                            <pre class="whitespace-pre-wrap font-sans text-xs text-slate-500 max-w-[16rem]">{{ $payout->details }}</pre>
                        </td>
                        <td data-label="{{ __('Status') }}">
                            @if($payout->status === 'paid')<span class="badge-green">{{ __('Paid') }}</span>
                            @elseif($payout->status === 'pending')<span class="badge-amber">{{ __('Pending') }}</span>
                            @else<span class="badge-red">{{ __('Rejected') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Requested') }}">{{ $payout->created_at->format('M j, Y') }}</td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            @if($payout->status === 'pending')
                                <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'payout-{{ $payout->id }}')">{{ __('Review') }}</button>
                                <x-modal name="payout-{{ $payout->id }}" :title="__('Review payout')">
                                    <form method="POST" action="{{ route('admin.payouts.update', $payout) }}" class="space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <p class="text-sm text-slate-500">
                                            {{ __(':amount to :name via :method', ['amount' => format_money($payout->amount), 'name' => $payout->user?->name ?? __('user'), 'method' => $payout->method]) }}
                                        </p>
                                        <div>
                                            <label class="label" for="payout-note-{{ $payout->id }}">{{ __('Admin note (optional)') }}</label>
                                            <textarea id="payout-note-{{ $payout->id }}" name="admin_note" rows="3" class="input" placeholder="{{ __('Transaction reference, reason…') }}"></textarea>
                                        </div>
                                        <p class="help">{{ __('Rejecting returns the amount to the user\'s affiliate balance.') }}</p>
                                        <div class="flex justify-end gap-2">
                                            <button name="status" value="rejected" class="btn-danger">{{ __('Reject') }}</button>
                                            <button name="status" value="paid" class="btn-primary">{{ __('Mark as paid') }}</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @else
                                <span class="text-xs text-slate-400" title="{{ $payout->admin_note }}">{{ $payout->admin_note ? \Illuminate\Support\Str::limit($payout->admin_note, 40) : '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No payout requests.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $payouts->links() }}
</div>
@endsection
