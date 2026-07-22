<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Coinbase Commerce hosted charges (crypto payments). */
class CoinbaseGateway extends Gateway
{
    public function key(): string
    {
        return 'coinbase';
    }

    public function label(): string
    {
        return 'Coinbase Commerce';
    }

    public function isConfigured(): bool
    {
        return (bool) $this->setting('api_key');
    }

    public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        $res = Http::withHeaders(['X-CC-Api-Key' => $this->setting('api_key'), 'X-CC-Version' => '2018-03-22'])
            ->post('https://api.commerce.coinbase.com/charges', [
                'name' => site_name() . ' — ' . $payment->plan->name,
                'description' => ucfirst($payment->cycle) . ' subscription',
                'pricing_type' => 'fixed_price',
                'local_price' => ['amount' => number_format((float) $payment->total, 2, '.', ''), 'currency' => $payment->currency],
                'metadata' => ['payment_id' => (string) $payment->id],
                'redirect_url' => $this->returnUrl($payment),
                'cancel_url' => $this->cancelUrl(),
            ])->throw()->json('data');

        $payment->update(['gateway_reference' => $res['code']]);

        return redirect()->away($res['hosted_url']);
    }

    public function verify(Request $request, Payment $payment): bool
    {
        if (! $payment->gateway_reference) {
            return false;
        }

        $res = Http::withHeaders(['X-CC-Api-Key' => $this->setting('api_key'), 'X-CC-Version' => '2018-03-22'])
            ->get('https://api.commerce.coinbase.com/charges/' . $payment->gateway_reference);

        $statuses = collect($res->json('data.timeline') ?? [])->pluck('status');

        return $res->ok() && ($statuses->contains('COMPLETED') || $statuses->contains('CONFIRMED') || $statuses->contains('RESOLVED'));
    }

    public function webhook(Request $request): ?Payment
    {
        $secret = $this->setting('webhook_secret');
        if ($secret) {
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, (string) $request->header('X-CC-Webhook-Signature'))) {
                abort(400, 'Invalid signature');
            }
        }

        $event = json_decode($request->getContent(), true)['event'] ?? [];
        if (in_array($event['type'] ?? '', ['charge:confirmed', 'charge:resolved'], true)) {
            return Payment::find($event['data']['metadata']['payment_id'] ?? null);
        }

        return null;
    }
}
