<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Razorpay Orders API + hosted Standard Checkout page. */
class RazorpayGateway extends Gateway
{
    public function key(): string
    {
        return 'razorpay';
    }

    public function label(): string
    {
        return 'Razorpay';
    }

    public function isConfigured(): bool
    {
        return $this->setting('key_id') && $this->setting('key_secret');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $order = Http::withBasicAuth($this->setting('key_id'), $this->setting('key_secret'))
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => (int) round($payment->total * 100), // paise
                'currency' => $payment->currency,
                'receipt' => 'pay_' . $payment->id,
                'notes' => ['payment_id' => (string) $payment->id],
            ])->throw()->json();

        $payment->update(['gateway_reference' => $order['id']]);

        // Render the checkout.js page; it posts back to billing.return.
        return response()->view('billing.razorpay', [
            'payment' => $payment,
            'orderId' => $order['id'],
            'keyId' => $this->setting('key_id'),
            'callbackUrl' => $this->returnUrl($payment),
        ]);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        $orderId = $payment->gateway_reference;
        $paymentId = $request->input('razorpay_payment_id');
        $signature = $request->input('razorpay_signature');
        if (! $orderId || ! $paymentId || ! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->setting('key_secret'));

        return hash_equals($expected, (string) $signature);
    }
}
