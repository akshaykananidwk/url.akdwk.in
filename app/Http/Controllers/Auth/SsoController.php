<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Plan;
use App\Models\SsoProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Generic OpenID Connect / OAuth2 single sign-on. Providers are configured by
 * an admin (Admin → SSO); each renders a "Sign in with …" button on the login
 * page. Works with Okta, Azure AD / Entra, Auth0, Keycloak, Google Workspace,
 * and any standards-compliant OIDC IdP.
 */
class SsoController extends Controller
{
    public function redirect(Request $request, string $slug)
    {
        $provider = SsoProvider::where('slug', $slug)->where('active', true)->firstOrFail();
        $endpoints = $provider->endpoints();
        abort_unless($endpoints['authorize'], 500, 'SSO provider is misconfigured.');

        $state = Str::random(40);
        $nonce = Str::random(40);
        session(['sso_state' => $state, 'sso_slug' => $slug]);

        $params = [
            'client_id' => $provider->client_id,
            'redirect_uri' => route('sso.callback', $slug),
            'response_type' => 'code',
            'scope' => $provider->scopes ?: 'openid email profile',
            'state' => $state,
            'nonce' => $nonce,
        ];

        return redirect()->away($endpoints['authorize'] . '?' . http_build_query($params));
    }

    public function callback(Request $request, string $slug)
    {
        $provider = SsoProvider::where('slug', $slug)->where('active', true)->firstOrFail();

        if (! $request->filled('code') || $request->input('state') !== session('sso_state')) {
            return redirect()->route('login')->withErrors(['email' => __('Single sign-on failed. Please try again.')]);
        }

        $endpoints = $provider->endpoints();

        try {
            $tokenRes = Http::asForm()->acceptJson()->timeout(12)->post($endpoints['token'], [
                'grant_type' => 'authorization_code',
                'code' => $request->input('code'),
                'redirect_uri' => route('sso.callback', $slug),
                'client_id' => $provider->client_id,
                'client_secret' => $provider->client_secret,
            ])->throw()->json();

            $accessToken = $tokenRes['access_token'] ?? null;
            $claims = [];

            // Prefer the id_token claims; fall back to the userinfo endpoint.
            if (! empty($tokenRes['id_token'])) {
                $claims = $this->decodeJwt($tokenRes['id_token']);
            }
            if (empty($claims['email']) && $accessToken && $endpoints['userinfo']) {
                $claims = array_merge($claims, Http::withToken($accessToken)->acceptJson()->timeout(12)->get($endpoints['userinfo'])->json() ?? []);
            }
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => __('Single sign-on failed. Please try again.')]);
        }

        $email = $claims['email'] ?? null;
        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => __('Your identity provider did not return an email address.')]);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            abort_unless(setting('sso_auto_provision', true), 403, __('No account exists for this email.'));
            $user = User::create([
                'name' => $claims['name'] ?? trim(($claims['given_name'] ?? '') . ' ' . ($claims['family_name'] ?? '')) ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::random(40),
                'plan_id' => Plan::defaultPlan()->id,
                'plan_cycle' => 'lifetime',
                'email_verified_at' => now(),
                'referral_code' => Str::lower(Str::random(8)),
                'locale' => app()->getLocale(),
            ]);
            hook_action('user_registered', $user);
        }

        if ($user->isSuspended()) {
            return redirect()->route('login')->withErrors(['email' => __('Your account has been suspended.')]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        Activity::log('auth.sso_login', __('Signed in via :provider', ['provider' => $provider->name]));

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
    }

    protected function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }

        return json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true) ?: [];
    }

    /** Active providers for the login page. */
    public static function active()
    {
        try {
            return SsoProvider::where('active', true)->get(['name', 'slug']);
        } catch (\Throwable) {
            return collect();
        }
    }
}
