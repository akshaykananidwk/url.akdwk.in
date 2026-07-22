<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Stripe Checkout (hosted page) via the REST API — no SDK required. */
class StripeGateway extends Gateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('secret_key');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withToken($this->setting('secret_key'))
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $this->returnUrl($payment) . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->cancelUrl(),
                'customer_email' => $payment->user->email,
                'client_reference_id' => (string) $payment->id,
                'line_items[0][price_data][currency]' => strtolower($payment->currency),
                'line_items[0][price_data][unit_amount]' => (int) round($payment->total * 100),
                'line_items[0][price_data][product_data][name]' => $payment->plan->name . ' — ' . ucfirst($payment->cycle),
                'line_items[0][quantity]' => 1,
                'metadata[payment_id]' => (string) $payment->id,
            ])->throw()->json();

        $payment->update(['gateway_reference' => $res['id']]);

        return redirect()->away($res['url']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        $sessionId = $request->query('session_id') ?: $payment->gateway_reference;
        if (! $sessionId) {
            return false;
        }

        $res = Http::withToken($this->setting('secret_key'))
            ->get('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);

        return $res->ok() && $res->json('payment_status') === 'paid'
            && (string) $res->json('client_reference_id') === (string) $payment->id;
    }

    public function webhook(Request $request): ?Payment
    {
        // Verify the Stripe-Signature header when a webhook secret is set.
        $secret = $this->setting('webhook_secret');
        $payload = $request->getContent();
        if ($secret) {
            $header = (string) $request->header('Stripe-Signature');
            preg_match('/t=(\d+)/', $header, $t);
            preg_match('/v1=([a-f0-9]+)/', $header, $v);
            $expected = hash_hmac('sha256', ($t[1] ?? '') . '.' . $payload, $secret);
            if (! isset($v[1]) || ! hash_equals($expected, $v[1])) {
                abort(400, 'Invalid signature');
            }
        }

        $event = json_decode($payload, true);
        if (($event['type'] ?? '') === 'checkout.session.completed') {
            $paymentId = $event['data']['object']['metadata']['payment_id'] ?? $event['data']['object']['client_reference_id'] ?? null;
            $payment = Payment::find($paymentId);
            if ($payment && ($event['data']['object']['payment_status'] ?? '') === 'paid') {
                return $payment;
            }
        }

        return null;
    }
}
