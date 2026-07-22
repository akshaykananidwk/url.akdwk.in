<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Manual bank transfer: shows the admin's bank details; the customer wires
 * the amount and submits the transfer reference for admin approval.
 */
class BankTransferGateway extends Gateway
{
    public function key(): string
    {
        return 'bank';
    }

    public function label(): string
    {
        return 'Bank Transfer';
    }

    public function isManual(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('details');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $details = [];
        foreach (preg_split('/[\r\n]+/', (string) $this->setting('details')) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $details[trim($k)] = trim($v);
            }
        }
        $details[__('Amount')] = format_money($payment->total, $payment->currency);
        $details[__('Payment reference')] = 'ORDER-' . $payment->id;

        return response()->view('billing.manual', [
            'payment' => $payment,
            'gateway' => $this,
            'title' => __('Pay by bank transfer'),
            'instructions' => __('Transfer the exact amount to the account below including the payment reference, then submit your transfer reference number. Your plan activates once our team confirms the transfer.'),
            'details' => $details,
            'qrSvg' => null,
            'upiUri' => null,
        ]);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        $request->validate(['reference' => 'required|string|min:4|max:64']);
        $payment->update([
            'gateway_reference' => $request->input('reference'),
            'meta' => array_merge($payment->meta ?? [], ['submitted_at' => now()->toIso8601String(), 'note' => $request->input('note')]),
        ]);

        return false;
    }
}
