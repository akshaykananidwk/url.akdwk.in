<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** PayPal Orders v2 REST API. */
class PayPalGateway extends Gateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        return $this->setting('client_id') && $this->setting('secret');
    }

    protected function base(): string
    {
        return $this->setting('sandbox', false)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    protected function token(): string
    {
        return Cache::remember('paypal_token', 3000, function () {
            return Http::withBasicAuth($this->setting('client_id'), $this->setting('secret'))
                ->asForm()
                ->post($this->base() . '/v1/oauth2/token', ['grant_type' => 'client_credentials'])
                ->throw()->json('access_token');
        });
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withToken($this->token())
            ->post($this->base() . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $payment->id,
                    'description' => $payment->plan->name . ' — ' . ucfirst($payment->cycle),
                    'amount' => ['currency_code' => $payment->currency, 'value' => number_format((float) $payment->total, 2, '.', '')],
                ]],
                'application_context' => [
                    'return_url' => $this->returnUrl($payment),
                    'cancel_url' => $this->cancelUrl(),
                    'brand_name' => site_name(),
                    'user_action' => 'PAY_NOW',
                ],
            ])->throw()->json();

        $payment->update(['gateway_reference' => $res['id']]);
        $approve = collect($res['links'])->firstWhere('rel', 'approve')['href'] ?? null;
        abort_unless($approve, 502, 'PayPal did not return an approval link.');

        return redirect()->away($approve);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        $orderId = $request->query('token') ?: $payment->gateway_reference;
        if (! $orderId) {
            return false;
        }

        // Capture the approved order; treat "already captured" as success.
        $res = Http::withToken($this->token())
            ->withBody('{}', 'application/json')
            ->post($this->base() . '/v2/checkout/orders/' . $orderId . '/capture');

        if ($res->status() === 422 && str_contains($res->body(), 'ORDER_ALREADY_CAPTURED')) {
            return true;
        }

        return $res->successful() && in_array($res->json('status'), ['COMPLETED', 'APPROVED'], true);
    }
}
