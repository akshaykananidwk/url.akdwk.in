<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Paystack transaction initialize + verify. */
class PaystackGateway extends Gateway
{
    public function key(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Paystack';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('secret_key');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $reference = 'ps_' . $payment->id . '_' . bin2hex(random_bytes(4));
        $res = Http::withToken($this->setting('secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $payment->user->email,
                'amount' => (int) round($payment->total * 100),
                'currency' => $payment->currency,
                'reference' => $reference,
                'callback_url' => $this->returnUrl($payment),
                'metadata' => ['payment_id' => $payment->id],
            ])->throw()->json();

        $payment->update(['gateway_reference' => $reference]);

        return redirect()->away($res['data']['authorization_url']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        $reference = $request->query('reference') ?: $payment->gateway_reference;
        if (! $reference) {
            return false;
        }

        $res = Http::withToken($this->setting('secret_key'))
            ->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));

        return $res->ok() && $res->json('data.status') === 'success';
    }
}
