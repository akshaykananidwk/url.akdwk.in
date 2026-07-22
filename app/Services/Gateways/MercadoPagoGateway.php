<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** MercadoPago Checkout Pro (preferences API). */
class MercadoPagoGateway extends Gateway
{
    public function key(): string
    {
        return 'mercadopago';
    }

    public function label(): string
    {
        return 'MercadoPago';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('access_token');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withToken($this->setting('access_token'))
            ->post('https://api.mercadopago.com/checkout/preferences', [
                'items' => [[
                    'title' => site_name() . ' — ' . $payment->plan->name,
                    'quantity' => 1,
                    'unit_price' => (float) $payment->total,
                    'currency_id' => $payment->currency,
                ]],
                'external_reference' => (string) $payment->id,
                'back_urls' => [
                    'success' => $this->returnUrl($payment),
                    'failure' => $this->cancelUrl(),
                    'pending' => $this->returnUrl($payment),
                ],
                'auto_return' => 'approved',
            ])->throw()->json();

        $payment->update(['gateway_reference' => $res['id']]);

        return redirect()->away($res['init_point']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        // The return URL carries payment_id + status; confirm server-side.
        $mpPaymentId = $request->query('payment_id') ?: $request->query('collection_id');
        if (! $mpPaymentId) {
            return false;
        }

        $res = Http::withToken($this->setting('access_token'))
            ->get('https://api.mercadopago.com/v1/payments/' . $mpPaymentId);

        return $res->ok()
            && $res->json('status') === 'approved'
            && (string) $res->json('external_reference') === (string) $payment->id;
    }
}
