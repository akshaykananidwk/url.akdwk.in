<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PayoutRequest;
use App\Services\PlanService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected PlanService $plans)
    {
    }

    public function index(Request $request)
    {
        $query = Payment::with(['user', 'plan'])->orderByDesc('created_at');
        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->query('gateway')) {
            $query->where('gateway', $request->query('gateway'));
        }
        if ($request->query('manual')) {
            $query->whereIn('gateway', ['upi', 'bank'])->whereNotNull('gateway_reference');
        }

        return view('admin.payments.index', [
            'payments' => $query->paginate(25)->withQueryString(),
        ]);
    }

    /** Approve a manual (UPI / bank transfer) payment. */
    public function approve(Payment $payment)
    {
        abort_unless($payment->status === 'pending', 422);
        $this->plans->fulfil($payment);
        AuditLog::record('payment.approved', $payment);

        return back()->with('status', __('Payment approved and plan activated.'));
    }

    public function decline(Request $request, Payment $payment)
    {
        abort_unless($payment->status === 'pending', 422);
        $payment->update([
            'status' => 'declined',
            'meta' => array_merge($payment->meta ?? [], ['declined_reason' => $request->input('reason')]),
        ]);
        AuditLog::record('payment.declined', $payment);

        return back()->with('status', __('Payment declined.'));
    }

    /** Record a refund (bookkeeping; money moves in the gateway dashboard). */
    public function refund(Request $request, Payment $payment)
    {
        abort_unless($payment->status === 'completed', 422);
        $payment->update([
            'status' => 'refunded',
            'meta' => array_merge($payment->meta ?? [], ['refunded_at' => now()->toIso8601String(), 'refund_note' => $request->input('reason')]),
        ]);
        $this->plans->downgradeToFree($payment->user);
        AuditLog::record('payment.refunded', $payment);

        return back()->with('status', __('Payment marked refunded and plan downgraded.'));
    }

    /* -------------------------------------------------- affiliate payouts */

    public function payouts()
    {
        return view('admin.payments.payouts', [
            'payouts' => PayoutRequest::with('user')->orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function updatePayout(Request $request, PayoutRequest $payout)
    {
        $data = $request->validate(['status' => 'required|in:paid,rejected', 'admin_note' => 'nullable|string|max:500']);

        if ($payout->status === 'pending' && $data['status'] === 'rejected') {
            $payout->user->increment('affiliate_balance', $payout->amount); // return the reserved balance
        }
        $payout->update($data);
        AuditLog::record('payout.' . $data['status'], $payout);

        return back()->with('status', __('Payout updated.'));
    }
}
