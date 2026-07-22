<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Xendit Invoices API (popular in Southeast Asia). */
class XenditGateway extends Gateway
{
    public function key(): string
    {
        return 'xendit';
    }

    public function label(): string
    {
        return 'Xendit';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('secret_key');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $externalId = 'xn_' . $payment->id . '_' . bin2hex(random_bytes(4));
        $res = Http::withBasicAuth($this->setting('secret_key'), '')
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => $externalId,
                'amount' => (float) $payment->total,
                'currency' => $payment->currency,
                'description' => site_name() . ' — ' . $payment->plan->name,
                'payer_email' => $payment->user->email,
                'success_redirect_url' => $this->returnUrl($payment),
                'failure_redirect_url' => $this->cancelUrl(),
            ])->throw()->json();

        $payment->update(['gateway_reference' => $res['id'], 'meta' => ['external_id' => $externalId]]);

        return redirect()->away($res['invoice_url']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        if (! $payment->gateway_reference) {
            return false;
        }

        $res = Http::withBasicAuth($this->setting('secret_key'), '')
            ->get('https://api.xendit.co/v2/invoices/' . $payment->gateway_reference);

        return $res->ok() && in_array($res->json('status'), ['PAID', 'SETTLED'], true);
    }

    public function webhook(Request $request): ?Payment
    {
        $token = $this->setting('callback_token');
        if ($token && ! hash_equals($token, (string) $request->header('x-callback-token'))) {
            abort(400, 'Invalid callback token');
        }

        if (in_array($request->input('status'), ['PAID', 'SETTLED'], true)) {
            return Payment::where('gateway_reference', $request->input('id'))->first();
        }

        return null;
    }
}
