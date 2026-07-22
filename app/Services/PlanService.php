<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Plan purchase lifecycle: price calculation (coupon + tax), pending payment
 * creation, and fulfilment after a gateway confirms the charge (idempotent).
 */
class PlanService
{
    /** Compute the checkout totals for a plan/cycle with optional coupon + country tax. */
    public function quote(Plan $plan, string $cycle, ?Coupon $coupon, ?string $country): array
    {
        $base = $plan->price($cycle);
        $discount = 0.0;
        if ($coupon && $coupon->isValidFor($plan->id)) {
            $discount = $coupon->discountOn($base);
        }

        $taxable = max(0, $base - $discount);
        $taxRate = TaxRate::forCountry($country);
        $tax = $taxRate ? round($taxable * ((float) $taxRate->rate / 100), 2) : 0.0;

        return [
            'amount' => round($base, 2),
            'discount' => $discount,
            'tax' => $tax,
            'tax_rate' => $taxRate,
            'total' => round($taxable + $tax, 2),
            'currency' => setting('currency', 'USD'),
        ];
    }

    public function createPendingPayment(User $user, Plan $plan, string $cycle, string $gateway, ?Coupon $coupon, ?string $country): Payment
    {
        $quote = $this->quote($plan, $cycle, $coupon, $country);

        return Payment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'cycle' => $cycle,
            'gateway' => $gateway,
            'amount' => $quote['amount'],
            'discount_amount' => $quote['discount'],
            'tax_amount' => $quote['tax'],
            'total' => $quote['total'],
            'currency' => $quote['currency'],
            'status' => 'pending',
            'coupon_id' => $coupon?->id,
            'tax_details' => $quote['tax_rate'] ? [
                'name' => $quote['tax_rate']->name,
                'rate' => (float) $quote['tax_rate']->rate,
                'country' => $country,
                'tax_id' => request('tax_id'),
            ] : null,
        ]);
    }

    /** Fulfil a confirmed payment: activate the plan, commissions, invoice. Idempotent. */
    public function fulfil(Payment $payment, ?string $gatewayReference = null): void
    {
        DB::transaction(function () use ($payment, $gatewayReference) {
            $payment = Payment::lockForUpdate()->find($payment->id);
            if ($payment->status === 'completed') {
                return;
            }

            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'gateway_reference' => $gatewayReference ?: $payment->gateway_reference,
            ]);
            $payment->assignInvoiceNumber();

            $user = $payment->user;
            $plan = $payment->plan;
            $cycle = $payment->cycle ?: 'monthly';

            // Extend from the current expiry when renewing the same plan.
            $start = ($user->plan_id === $plan->id && $user->plan_expires_at?->isFuture())
                ? $user->plan_expires_at
                : now();
            $expires = match ($cycle) {
                'yearly' => $start->copy()->addYear(),
                'lifetime' => null,
                default => $start->copy()->addMonth(),
            };

            $user->update([
                'plan_id' => $plan->id,
                'plan_cycle' => $cycle,
                'plan_expires_at' => $expires,
                'trial_ends_at' => null,
            ]);

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active'],
                [
                    'cycle' => $cycle,
                    'gateway' => $payment->gateway,
                    'starts_at' => now(),
                    'ends_at' => $expires,
                    'auto_renew' => $cycle !== 'lifetime',
                    'dunning_attempts' => 0,
                ]
            );
            $payment->update(['subscription_id' => $subscription->id]);

            if ($payment->coupon) {
                $payment->coupon->increment('used_count');
            }

            // Affiliate commission for the referrer.
            $percent = (float) setting('affiliate_commission_percent', 20);
            if ($user->referred_by && $percent > 0 && (float) $payment->total > 0) {
                $amount = round((float) $payment->total * $percent / 100, 2);
                Commission::create([
                    'user_id' => $user->referred_by,
                    'referred_user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'status' => 'approved',
                ]);
                User::where('id', $user->referred_by)->increment('affiliate_balance', $amount);
            }

            hook_action('payment_completed', $payment);
        });

        // Email the invoice outside the transaction.
        try {
            $payment->refresh();
            \Illuminate\Support\Facades\Mail::to($payment->user->email)->send(new \App\Mail\InvoicePaid($payment));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Downgrade to the free/default plan (used on cancel + expiry). */
    public function downgradeToFree(User $user): void
    {
        $free = Plan::defaultPlan();
        $user->update([
            'plan_id' => $free->id,
            'plan_cycle' => 'lifetime',
            'plan_expires_at' => null,
        ]);
        $user->subscriptions()->where('status', 'active')->update(['status' => 'expired']);
    }
}
