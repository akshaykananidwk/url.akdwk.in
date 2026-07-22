<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Paddle Billing API: a transaction is created server-side and the customer
 * pays on Paddle's hosted checkout. Requires a default payment link to be
 * configured in your Paddle dashboard (Checkout settings).
 */
class PaddleGateway extends Gateway
{
    public function key(): string
    {
        return 'paddle';
    }

    public function label(): string
    {
        return 'Paddle';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('api_key');
    }

    protected function base(): string
    {
        return $this->setting('sandbox', false)
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withToken($this->setting('api_key'))
            ->post($this->base() . '/transactions', [
                'items' => [[
                    'quantity' => 1,
                    'price' => [
                        'description' => $payment->plan->name . ' — ' . ucfirst($payment->cycle),
                        'name' => $payment->plan->name,
                        'unit_price' => [
                            'amount' => (string) ((int) round($payment->total * 100)),
                            'currency_code' => $payment->currency,
                        ],
                        'product' => [
                            'name' => site_name() . ' subscription',
                            'tax_category' => 'saas',
                        ],
                    ],
                ]],
                'custom_data' => ['payment_id' => (string) $payment->id],
                'checkout' => ['url' => $this->setting('checkout_url') ?: null],
            ])->throw()->json('data');

        $payment->update(['gateway_reference' => $res['id']]);

        $url = $res['checkout']['url'] ?? null;
        abort_unless($url, 502, 'Paddle did not return a checkout URL. Set a default payment link in your Paddle dashboard.');

        return redirect()->away($url . (str_contains($url, '?') ? '&' : '?') . '_ptxn=' . $res['id']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        if (! $payment->gateway_reference) {
            return false;
        }

        $res = Http::withToken($this->setting('api_key'))
            ->get($this->base() . '/transactions/' . $payment->gateway_reference);

        return $res->ok() && in_array($res->json('data.status'), ['paid', 'completed'], true);
    }

    public function webhook(Request $request): ?Payment
    {
        $secret = $this->setting('webhook_secret');
        if ($secret) {
            $sig = (string) $request->header('Paddle-Signature');
            preg_match('/ts=(\d+)/', $sig, $ts);
            preg_match('/h1=([a-f0-9]+)/', $sig, $h1);
            $expected = hash_hmac('sha256', ($ts[1] ?? '') . ':' . $request->getContent(), $secret);
            if (! isset($h1[1]) || ! hash_equals($expected, $h1[1])) {
                abort(400, 'Invalid signature');
            }
        }

        $event = json_decode($request->getContent(), true);
        if (in_array($event['event_type'] ?? '', ['transaction.completed', 'transaction.paid'], true)) {
            return Payment::find($event['data']['custom_data']['payment_id'] ?? null);
        }

        return null;
    }
}
