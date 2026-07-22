<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function show(Request $request)
    {
        abort_unless(setting('registration_enabled', true), 404);

        // Persist ?ref= referral codes through the signup funnel.
        if ($request->filled('ref')) {
            session(['ref' => $request->query('ref')]);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        abort_unless(setting('registration_enabled', true), 404);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ]);

        LoginController::verifyCaptcha($request);

        $referrer = null;
        if (session('ref')) {
            $referrer = User::where('referral_code', session('ref'))->first();
        }

        $defaultPlan = Plan::defaultPlan();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'plan_id' => $defaultPlan->id,
            'plan_cycle' => 'lifetime',
            'referred_by' => $referrer?->id,
            'referral_code' => Str::lower(Str::random(8)),
            'locale' => app()->getLocale(),
            'trial_ends_at' => $defaultPlan->trial_days > 0 ? now()->addDays($defaultPlan->trial_days) : null,
        ]);

        hook_action('user_registered', $user);

        if (setting('require_email_verification', false)) {
            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
