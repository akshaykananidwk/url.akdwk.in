<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * UPI manual payment (India): shows the admin's UPI ID + a UPI QR code, the
 * customer pays from any UPI app and submits the UTR/transaction reference.
 * The payment stays pending until an admin approves it (Admin → Payments).
 */
class UpiGateway extends Gateway
{
    public function key(): string
    {
        return 'upi';
    }

    public function label(): string
    {
        return 'UPI (India)';
    }

    public function isManual(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('vpa');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $vpa = $this->setting('vpa');
        $upiUri = 'upi://pay?' . http_build_query([
            'pa' => $vpa,
            'pn' => $this->setting('payee_name', site_name()),
            'am' => number_format((float) $payment->total, 2, '.', ''),
            'cu' => 'INR',
            'tn' => 'Order ' . $payment->id,
        ]);

        $qrSvg = app(\App\Services\QrService::class)->svg($upiUri, ['size' => 260]);

        return response()->view('billing.manual', [
            'payment' => $payment,
            'gateway' => $this,
            'title' => __('Pay with UPI'),
            'instructions' => __('Scan the QR code with any UPI app (GPay, PhonePe, Paytm, BHIM) or pay to the UPI ID below, then enter the 12-digit UTR / transaction reference to submit for verification.'),
            'details' => [__('UPI ID') => $vpa, __('Amount') => format_money($payment->total, 'INR')],
            'qrSvg' => $qrSvg,
            'upiUri' => $upiUri,
        ]);
    }

    /** "Return" here is the reference submission; keeps the payment pending for admin review. */
    public function verify(Request $request, Payment $payment): bool
    {
        $request->validate(['reference' => 'required|string|min:6|max:64']);
        $payment->update([
            'gateway_reference' => $request->input('reference'),
            'meta' => array_merge($payment->meta ?? [], ['submitted_at' => now()->toIso8601String(), 'note' => $request->input('note')]),
        ]);

        return false; // never auto-completes — admin approval fulfils it
    }
}
