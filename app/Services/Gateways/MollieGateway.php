<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Mollie Payments API. */
class MollieGateway extends Gateway
{
    public function key(): string
    {
        return 'mollie';
    }

    public function label(): string
    {
        return 'Mollie';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('api_key');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withToken($this->setting('api_key'))
            ->post('https://api.mollie.com/v2/payments', [
                'amount' => ['currency' => $payment->currency, 'value' => number_format((float) $payment->total, 2, '.', '')],
                'description' => site_name() . ' — ' . $payment->plan->name,
                'redirectUrl' => $this->returnUrl($payment),
                'webhookUrl' => route('billing.webhook', 'mollie'),
                'metadata' => ['payment_id' => $payment->id],
            ])->throw()->json();

        $payment->update(['gateway_reference' => $res['id']]);

        return redirect()->away($res['_links']['checkout']['href']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        if (! $payment->gateway_reference) {
            return false;
        }

        $res = Http::withToken($this->setting('api_key'))
            ->get('https://api.mollie.com/v2/payments/' . $payment->gateway_reference);

        return $res->ok() && $res->json('status') === 'paid';
    }

    public function webhook(Request $request): ?Payment
    {
        $id = $request->input('id');
        if (! $id) {
            return null;
        }
        $res = Http::withToken($this->setting('api_key'))
            ->get('https://api.mollie.com/v2/payments/' . $id);
        if ($res->ok() && $res->json('status') === 'paid') {
            return Payment::find($res->json('metadata.payment_id'));
        }

        return null;
    }
}
