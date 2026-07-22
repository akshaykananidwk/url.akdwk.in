<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->ensureReferralCode();

        return view('user.affiliate.index', [
            'referralUrl' => route('register') . '?ref=' . $user->referral_code,
            'referredCount' => \App\Models\User::where('referred_by', $user->id)->count(),
            'commissions' => $user->commissions()->with('payment')->orderByDesc('created_at')->paginate(15),
            'balance' => $user->affiliate_balance,
            'payouts' => \App\Models\PayoutRequest::where('user_id', $user->id)->orderByDesc('created_at')->get(),
            'commissionPercent' => (float) setting('affiliate_commission_percent', 20),
            'minPayout' => (float) setting('affiliate_min_payout', 25),
        ]);
    }

    public function requestPayout(Request $request)
    {
        $user = $request->user();
        $min = (float) setting('affiliate_min_payout', 25);

        $data = $request->validate([
            'amount' => 'required|numeric|min:' . $min,
            'method' => 'required|in:paypal,bank,upi',
            'details' => 'required|string|max:500',
        ]);

        abort_if((float) $data['amount'] > (float) $user->affiliate_balance, 422, __('Amount exceeds your available balance.'));

        \App\Models\PayoutRequest::create($data + ['user_id' => $user->id]);
        $user->decrement('affiliate_balance', $data['amount']);

        return back()->with('status', __('Payout requested. It will be reviewed by our team.'));
    }
}
