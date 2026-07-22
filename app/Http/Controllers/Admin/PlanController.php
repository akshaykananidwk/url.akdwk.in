<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index', [
            'plans' => Plan::withCount('users')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.plans.form', [
            'plan' => null,
            'limitKeys' => Plan::LIMIT_KEYS,
            'featureKeys' => Plan::FEATURE_KEYS,
        ]);
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', [
            'plan' => $plan,
            'limitKeys' => Plan::LIMIT_KEYS,
            'featureKeys' => Plan::FEATURE_KEYS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $plan = Plan::create($data);
        AuditLog::record('plan.created', $plan);

        return redirect()->route('admin.plans.index')->with('status', __('Plan created.'));
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request));
        AuditLog::record('plan.updated', $plan);

        return redirect()->route('admin.plans.index')->with('status', __('Plan updated.'));
    }

    public function destroy(Plan $plan)
    {
        abort_if($plan->is_default, 422, __('The default plan cannot be deleted.'));
        $fallback = Plan::defaultPlan();
        \App\Models\User::where('plan_id', $plan->id)->update(['plan_id' => $fallback->id]);
        $plan->delete();
        AuditLog::record('plan.deleted', null, ['name' => $plan->name]);

        return back()->with('status', __('Plan deleted. Its users were moved to the default plan.'));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'description' => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'price_lifetime' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0|max:365',
            'limits' => 'required|array',
            'features' => 'nullable|array',
            'is_free' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $limits = [];
        foreach (array_keys(Plan::LIMIT_KEYS) as $key) {
            $limits[$key] = (int) ($data['limits'][$key] ?? 0);
        }
        $features = [];
        foreach (array_keys(Plan::FEATURE_KEYS) as $key) {
            $features[$key] = ! empty($data['features'][$key]);
        }

        $data['limits'] = $limits;
        $data['features'] = $features;
        foreach (['is_free', 'is_default', 'is_featured', 'active'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($data['is_default']) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        return $data;
    }

    /* ------------------------------------------------------------ coupons */

    public function coupons()
    {
        return view('admin.plans.coupons', [
            'coupons' => Coupon::orderByDesc('created_at')->paginate(25),
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function storeCoupon(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:60|unique:coupons,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0.01',
            'max_uses' => 'nullable|integer|min:1',
            'plan_ids' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);
        $data['code'] = strtoupper($data['code']);
        $data['active'] = true;
        Coupon::create($data);

        return back()->with('status', __('Coupon created.'));
    }

    public function toggleCoupon(Coupon $coupon)
    {
        $coupon->update(['active' => ! $coupon->active]);

        return back()->with('status', __('Coupon updated.'));
    }

    public function destroyCoupon(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('status', __('Coupon deleted.'));
    }

    /* ---------------------------------------------------------- tax rates */

    public function taxes()
    {
        return view('admin.plans.taxes', ['taxes' => TaxRate::orderBy('name')->get()]);
    }

    public function storeTax(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'country' => 'nullable|string|size:2',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_id_label' => 'nullable|string|max:30',
        ]);
        $data['country'] = $data['country'] ? strtoupper($data['country']) : null;
        $data['active'] = true;
        TaxRate::create($data);

        return back()->with('status', __('Tax rate created.'));
    }

    public function toggleTax(TaxRate $tax)
    {
        $tax->update(['active' => ! $tax->active]);

        return back()->with('status', __('Tax rate updated.'));
    }

    public function destroyTax(TaxRate $tax)
    {
        $tax->delete();

        return back()->with('status', __('Tax rate deleted.'));
    }
}
