<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** NOWPayments hosted invoices (crypto payments). */
class NowPaymentsGateway extends Gateway
{
    public function key(): string
    {
        return 'nowpayments';
    }

    public function label(): string
    {
        return 'NOWPayments';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('api_key');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withHeaders(['x-api-key' => $this->setting('api_key')])
            ->post('https://api.nowpayments.io/v1/invoice', [
                'price_amount' => (float) $payment->total,
                'price_currency' => strtolower($payment->currency),
                'order_id' => (string) $payment->id,
                'order_description' => site_name() . ' — ' . $payment->plan->name,
                'ipn_callback_url' => route('billing.webhook', 'nowpayments'),
                'success_url' => $this->returnUrl($payment),
                'cancel_url' => $this->cancelUrl(),
            ])->throw()->json();

        $payment->update(['gateway_reference' => (string) $res['id']]);

        return redirect()->away($res['invoice_url']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        // Crypto settles asynchronously; check payments recorded for this invoice.
        $res = Http::withHeaders(['x-api-key' => $this->setting('api_key')])
            ->get('https://api.nowpayments.io/v1/payment/', ['invoiceId' => $payment->gateway_reference]);

        return $res->ok() && collect($res->json('data') ?? [])
            ->contains(fn ($p) => in_array($p['payment_status'] ?? '', ['finished', 'confirmed'], true));
    }

    public function webhook(Request $request): ?Payment
    {
        $secret = $this->setting('ipn_secret');
        if ($secret) {
            $data = $request->all();
            ksort($data);
            $expected = hash_hmac('sha512', json_encode($data, JSON_UNESCAPED_SLASHES), $secret);
            if (! hash_equals($expected, (string) $request->header('x-nowpayments-sig'))) {
                abort(400, 'Invalid signature');
            }
        }

        if (in_array($request->input('payment_status'), ['finished', 'confirmed'], true)) {
            return Payment::find($request->input('order_id'));
        }

        return null;
    }
}
