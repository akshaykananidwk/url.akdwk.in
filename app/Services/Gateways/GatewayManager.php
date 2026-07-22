<?php

namespace App\Services\Gateways;

/**
 * Registry of available payment gateways. Addons can register additional
 * gateways through the 'payment_gateways' filter.
 */
class GatewayManager
{
    /** @var array<string, class-string<Gateway>> */
    protected array $gateways = [
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'razorpay' => RazorpayGateway::class,
        'paystack' => PaystackGateway::class,
        'mollie' => MollieGateway::class,
        'paddle' => PaddleGateway::class,
        'xendit' => XenditGateway::class,
        'mercadopago' => MercadoPagoGateway::class,
        'coinbase' => CoinbaseGateway::class,
        'nowpayments' => NowPaymentsGateway::class,
        'upi' => UpiGateway::class,
        'bank' => BankTransferGateway::class,
    ];

    /** @return Gateway[] all gateways (configured or not — admin settings UI needs all). */
    public function all(): array
    {
        $classes = hook_filter('payment_gateways', $this->gateways);

        return array_map(fn ($class) => app($class), $classes);
    }

    /** @return Gateway[] gateways ready for customer checkout. */
    public function enabled(): array
    {
        return array_filter($this->all(), fn (Gateway $g) => $g->isConfigured() && setting($g->key() . '_enabled', false));
    }

    public function get(string $key): ?Gateway
    {
        $all = $this->all();

        return $all[$key] ?? null;
    }
}
