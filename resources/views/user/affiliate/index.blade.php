@extends('layouts.app')

@section('title', __('Affiliate') . ' — ' . site_name())
@section('page-title', __('Affiliate program'))

@section('content')
<div class="space-y-5">

    {{-- Referral link --}}
    <div class="card card-pad">
        <h2 class="font-semibold mb-1">{{ __('Your referral link') }}</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            {{ __('Earn :percent% commission on every payment made by users you refer.', ['percent' => $commissionPercent + 0]) }}
        </p>
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" readonly value="{{ $referralUrl }}" class="input flex-1 font-mono text-xs" onclick="this.select()" aria-label="{{ __('Referral link') }}">
            <button type="button" class="btn-primary" onclick="copyText(@js($referralUrl))">
                <x-icon name="copy" class="h-4 w-4"/> {{ __('Copy link') }}
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-stat-card :label="__('Referred users')" :value="format_number($referredCount)" icon="users"/>
        <x-stat-card :label="__('Available balance')" :value="format_money($balance)" icon="gift"/>
        <x-stat-card :label="__('Minimum payout')" :value="format_money($minPayout)" icon="card"/>
    </div>

    <div class="grid lg:grid-cols-2 gap-5 items-start">
        {{-- Payout request --}}
        <div class="card card-pad">
            <h2 class="font-semibold mb-4">{{ __('Request a payout') }}</h2>
            @if($balance >= $minPayout)
                <form method="POST" action="{{ route('affiliate.payout') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <x-field name="amount" :label="__('Amount')">
                            <input type="number" name="amount" step="0.01" min="{{ $minPayout }}" max="{{ $balance }}"
                                   value="{{ old('amount', $balance) }}" required class="input">
                        </x-field>
                        <x-field name="method" :label="__('Method')">
                            <select name="method" class="input">
                                <option value="paypal" @selected(old('method') === 'paypal')>PayPal</option>
                                <option value="bank" @selected(old('method') === 'bank')>{{ __('Bank transfer') }}</option>
                                <option value="upi" @selected(old('method') === 'upi')>UPI</option>
                            </select>
                        </x-field>
                    </div>
                    <x-field name="details" :label="__('Payment details')" :help="__('PayPal email, bank account details or UPI ID.')">
                        <textarea name="details" rows="3" required class="input">{{ old('details') }}</textarea>
                    </x-field>
                    <button type="submit" class="btn-primary w-full">{{ __('Request payout') }}</button>
                </form>
            @else
                <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-4 text-sm text-slate-600 dark:text-slate-300">
                    {{ __('You need at least :min to request a payout. Current balance: :balance.', ['min' => format_money($minPayout), 'balance' => format_money($balance)]) }}
                </div>
            @endif

            {{-- Payout history --}}
            <h3 class="font-semibold text-sm mt-6 mb-3">{{ __('Payout history') }}</h3>
            @if($payouts->isEmpty())
                <p class="text-sm text-slate-500 py-2">{{ __('No payouts yet.') }}</p>
            @else
                <div class="space-y-2">
                    @foreach($payouts as $payout)
                        <div class="flex items-center gap-3 rounded-xl ring-1 ring-slate-200 dark:ring-slate-800 p-3 text-sm">
                            <span class="min-w-0 flex-1">
                                <span class="font-medium">{{ format_money($payout->amount) }}</span>
                                <span class="text-slate-500"> · {{ strtoupper($payout->method) }}</span>
                                <span class="block text-xs text-slate-500">{{ $payout->created_at->format('M j, Y') }}</span>
                            </span>
                            <span class="{{ ['approved' => 'badge-green', 'paid' => 'badge-green', 'declined' => 'badge-red', 'rejected' => 'badge-red'][$payout->status] ?? 'badge-amber' }}">
                                {{ __(ucfirst($payout->status)) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Commissions --}}
        <div class="card overflow-hidden">
            <div class="card-pad !pb-0"><h2 class="font-semibold">{{ __('Commissions') }}</h2></div>
            @if($commissions->isEmpty())
                <p class="text-sm text-slate-500 py-8 text-center">{{ __('No commissions yet — share your referral link to start earning.') }}</p>
            @else
                <div class="p-4 sm:p-0 sm:pt-2">
                    <table class="table-cards">
                        <thead>
                            <tr>
                                <th>{{ __('Referred user') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commissions as $c)
                                <tr>
                                    <td data-label="{{ __('Referred user') }}">{{ $c->referredUser?->name ?? __('Deleted user') }}</td>
                                    <td data-label="{{ __('Amount') }}"><span class="font-medium">{{ format_money($c->amount) }}</span></td>
                                    <td data-label="{{ __('Status') }}">
                                        <span class="{{ ['approved' => 'badge-green', 'paid' => 'badge-green', 'declined' => 'badge-red', 'reversed' => 'badge-red'][$c->status] ?? 'badge-amber' }}">
                                            {{ __(ucfirst($c->status)) }}
                                        </span>
                                    </td>
                                    <td data-label="{{ __('Date') }}">{{ $c->created_at->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-pad !pt-2">{{ $commissions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
