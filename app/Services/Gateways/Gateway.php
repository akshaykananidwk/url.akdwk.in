<?php

namespace App\Services\Gateways;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Payment gateway contract. Each gateway is configured entirely from
 * Admin → Settings → Payments (keys stored encrypted in the settings table).
 *
 * Flow: BillingController creates a pending Payment, then calls checkout()
 * which returns either a redirect to the gateway's hosted page or a view
 * (manual gateways render payment instructions). The gateway sends the user
 * back to billing.return where verify() confirms the charge server-side;
 * webhooks (/webhooks/{gateway}) are supported as the asynchronous source of
 * truth where available.
 */
abstract class Gateway
{
    abstract public function key(): string;

    abstract public function label(): string;

    /** True when the admin has entered credentials for this gateway. */
    abstract public function isConfigured(): bool;

    /** Begin a checkout: redirect to the PSP or render instructions. */
    abstract public function checkout(Payment $payment): \Symfony\Component\HttpFoundation\Response;

    /** Server-side verification when the customer returns. */
    abstract public function verify(Request $request, Payment $payment): bool;

    /** Handle an asynchronous webhook; return the affected payment if completed. */
    public function webhook(Request $request): ?Payment
    {
        return null;
    }

    /** Whether this gateway requires manual admin approval (UPI/bank). */
    public function isManual(): bool
    {
        return false;
    }

    protected function setting(string $key, $default = null)
    {
        return setting($this->key() . '_' . $key, $default);
    }

    protected function returnUrl(Payment $payment): string
    {
        return route('billing.return', ['gateway' => $this->key(), 'payment' => $payment->id]);
    }

    protected function cancelUrl(): string
    {
        return route('billing.plans');
    }
}
