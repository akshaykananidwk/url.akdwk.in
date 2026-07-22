<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Gateways\GatewayManager;
use App\Services\PlanService;
use App\Services\Support\GeoService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected GatewayManager $gateways,
        protected PlanService $plans,
    ) {
    }

    public function plans(Request $request)
    {
        return view('user.billing.plans', [
            'plans' => Plan::where('active', true)->orderBy('sort_order')->get(),
            'current' => $request->user()->currentPlan(),
            'user' => $request->user(),
        ]);
    }

    public function checkout(Request $request, Plan $plan)
    {
        abort_unless($plan->active && ! $plan->is_free, 404);
        $cycle = $request->query('cycle', 'monthly');
        abort_unless(in_array($cycle, ['monthly', 'yearly', 'lifetime'], true), 404);
        abort_unless($plan->price($cycle) > 0, 404, __('This billing cycle is not available for this plan.'));

        $coupon = null;
        if ($request->filled('coupon')) {
            $coupon = Coupon::where('code', $request->query('coupon'))->first();
            if (! $coupon?->isValidFor($plan->id)) {
                return redirect()->route('billing.checkout', ['plan' => $plan, 'cycle' => $cycle])
                    ->withErrors(['coupon' => __('This coupon is invalid or expired.')]);
            }
        }

        $country = GeoService::fromHeaders($request)['country'];

        return view('user.billing.checkout', [
            'plan' => $plan,
            'cycle' => $cycle,
            'coupon' => $coupon,
            'quote' => $this->plans->quote($plan, $cycle, $coupon, $country),
            'gateways' => $this->gateways->enabled(),
        ]);
    }

    public function pay(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'cycle' => 'required|in:monthly,yearly,lifetime',
            'gateway' => 'required|string',
            'coupon' => 'nullable|string|max:60',
            'tax_id' => 'nullable|string|max:30',
        ]);

        $gateway = $this->gateways->get($data['gateway']);
        abort_unless($gateway && $gateway->isConfigured() && setting($gateway->key() . '_enabled', false), 404);

        $coupon = $data['coupon'] ? Coupon::where('code', $data['coupon'])->first() : null;
        if ($coupon && ! $coupon->isValidFor($plan->id)) {
            $coupon = null;
        }

        $payment = $this->plans->createPendingPayment(
            $request->user(), $plan, $data['cycle'], $gateway->key(), $coupon,
            GeoService::fromHeaders($request)['country']
        );

        try {
            return $gateway->checkout($payment);
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['status' => 'failed', 'meta' => ['error' => $e->getMessage()]]);

            return redirect()->route('billing.plans')->withErrors(['gateway' => __('The payment could not be started. Please try again or choose another method.')]);
        }
    }

    /** Return URL from the gateway (GET) or manual reference submission (POST). */
    public function handleReturn(Request $request, string $gateway, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        abort_unless($payment->gateway === $gateway, 404);
        $driver = $this->gateways->get($gateway) ?: abort(404);

        if ($payment->status === 'completed') {
            return redirect()->route('billing.invoices')->with('status', __('Payment received — your plan is active.'));
        }

        if ($driver->verify($request, $payment)) {
            $this->plans->fulfil($payment);

            return redirect()->route('billing.invoices')->with('status', __('Payment received — your plan is active.'));
        }

        if ($driver->isManual()) {
            return redirect()->route('billing.invoices')->with('status', __('Reference submitted. Your plan activates once the payment is approved.'));
        }

        return redirect()->route('billing.plans')->withErrors(['gateway' => __('We could not confirm the payment yet. If you completed it, it will be reflected shortly.')]);
    }

    /** Asynchronous gateway webhooks (CSRF-exempt). */
    public function webhook(Request $request, string $gateway)
    {
        $driver = $this->gateways->get($gateway) ?: abort(404);

        $payment = $driver->webhook($request);
        if ($payment && $payment->status !== 'completed') {
            $this->plans->fulfil($payment);
        }

        return response()->json(['ok' => true]);
    }

    public function invoices(Request $request)
    {
        return view('user.billing.invoices', [
            'payments' => $request->user()->payments()->with('plan')->orderByDesc('created_at')->paginate(15),
            'subscription' => $request->user()->subscriptions()->where('status', 'active')->latest()->first(),
        ]);
    }

    public function invoicePdf(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($payment->status === 'completed', 404);

        $html = view('billing.invoice-pdf', ['payment' => $payment])->render();
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename=' . ($payment->invoice_number ?: 'invoice-' . $payment->id) . '.pdf',
        ]);
    }

    public function cancelSubscription(Request $request)
    {
        $sub = $request->user()->subscriptions()->where('status', 'active')->latest()->first();
        abort_unless($sub, 404);

        $sub->update(['auto_renew' => false, 'cancelled_at' => now(), 'status' => 'cancelled']);

        return back()->with('status', __('Auto-renewal cancelled. Your plan stays active until :date.', [
            'date' => $sub->ends_at?->format('M j, Y') ?? __('forever'),
        ]));
    }
}
