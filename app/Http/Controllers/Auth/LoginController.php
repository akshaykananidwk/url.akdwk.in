<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $this->verifyCaptcha($request);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            if ($user) {
                LoginHistory::create(['user_id' => $user->id, 'ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 500), 'success' => false, 'created_at' => now()]);
            }

            return back()->withErrors(['email' => __('These credentials do not match our records.')])->onlyInput('email');
        }

        if ($user->isSuspended()) {
            return back()->withErrors(['email' => __('Your account has been suspended. Please contact support.')])->onlyInput('email');
        }

        // Two-factor: stash the user id and show the challenge before logging in.
        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('2fa:user', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    public function twoFactorChallenge()
    {
        if (! session('2fa:user')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function twoFactorVerify(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = User::find(session('2fa:user'));
        if (! $user) {
            return redirect()->route('login');
        }

        $code = trim((string) $request->input('code'));
        $ok = Totp::verify(decrypt($user->two_factor_secret), $code);

        // Recovery code fallback.
        if (! $ok) {
            $codes = json_decode((string) decrypt($user->two_factor_recovery_codes ?? ''), true) ?: [];
            if (($i = array_search($code, $codes, true)) !== false) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => encrypt(json_encode(array_values($codes)))])->save();
                $ok = true;
            }
        }

        if (! $ok) {
            return back()->withErrors(['code' => __('The provided code is invalid.')]);
        }

        $remember = (bool) session('2fa:remember');
        session()->forget(['2fa:user', '2fa:remember']);

        return $this->completeLogin($request, $user, $remember);
    }

    protected function completeLogin(Request $request, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        LoginHistory::create([
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'country' => \App\Services\Support\GeoService::fromHeaders($request)['country'],
            'success' => true,
            'created_at' => now(),
        ]);

        hook_action('user_logged_in', $user);

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    public function logout(Request $request)
    {
        // Leaving impersonation returns to the admin account instead of logging out.
        if ($request->session()->has('impersonator')) {
            $adminId = $request->session()->pull('impersonator');
            Auth::loginUsingId($adminId);
            $request->session()->regenerate();

            return redirect()->route('admin.users.index')->with('status', __('Returned to your admin account.'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /** Shared captcha verification (reCAPTCHA v2/v3 or hCaptcha) + honeypot. */
    public static function verifyCaptcha(Request $request): void
    {
        // Honeypot: hidden "website" field must stay empty.
        if (setting('honeypot_enabled', true) && $request->filled('website')) {
            abort(422, 'Spam detected.');
        }

        $provider = setting('captcha_provider'); // recaptcha2 | recaptcha3 | hcaptcha
        $secret = setting('captcha_secret');
        if (! $provider || ! $secret) {
            return;
        }

        $token = $request->input($provider === 'hcaptcha' ? 'h-captcha-response' : 'g-recaptcha-response');
        $endpoint = $provider === 'hcaptcha'
            ? 'https://hcaptcha.com/siteverify'
            : 'https://www.google.com/recaptcha/api/siteverify';

        try {
            $res = \Illuminate\Support\Facades\Http::asForm()->timeout(5)->post($endpoint, [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            $ok = $res->json('success') && ($provider !== 'recaptcha3' || ($res->json('score') ?? 0) >= 0.5);
        } catch (\Throwable) {
            $ok = true; // captcha service down — fail open, honeypot still active
        }

        if (! $ok) {
            throw \Illuminate\Validation\ValidationException::withMessages(['captcha' => __('Captcha verification failed. Please try again.')]);
        }
    }
}
