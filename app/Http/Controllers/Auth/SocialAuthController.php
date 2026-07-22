<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Dependency-free OAuth2 login for Google, Facebook, X (Twitter), GitHub and
 * Apple. Providers appear on the login page only when the admin has filled in
 * their client ID + secret (Admin → Settings → Social Login).
 *
 * For Apple, the "client secret" field takes the pre-generated client secret
 * JWT (created from your .p8 key in the Apple developer portal tooling).
 */
class SocialAuthController extends Controller
{
    protected function providers(): array
    {
        return [
            'google' => [
                'auth' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token' => 'https://oauth2.googleapis.com/token',
                'user' => 'https://openidconnect.googleapis.com/v1/userinfo',
                'scope' => 'openid email profile',
                'map' => fn ($u) => ['email' => $u['email'] ?? null, 'name' => $u['name'] ?? null],
            ],
            'facebook' => [
                'auth' => 'https://www.facebook.com/v19.0/dialog/oauth',
                'token' => 'https://graph.facebook.com/v19.0/oauth/access_token',
                'user' => 'https://graph.facebook.com/me?fields=id,name,email',
                'scope' => 'email public_profile',
                'map' => fn ($u) => ['email' => $u['email'] ?? null, 'name' => $u['name'] ?? null],
            ],
            'twitter' => [
                'auth' => 'https://twitter.com/i/oauth2/authorize',
                'token' => 'https://api.twitter.com/2/oauth2/token',
                'user' => 'https://api.twitter.com/2/users/me',
                'scope' => 'users.read tweet.read',
                'pkce' => true,
                'basic' => true,
                // X does not return emails via this endpoint; synthesize a stable address.
                'map' => fn ($u) => [
                    'email' => isset($u['data']['id']) ? 'x_' . $u['data']['id'] . '@users.noreply.x.local' : null,
                    'name' => $u['data']['name'] ?? null,
                ],
            ],
            'github' => [
                'auth' => 'https://github.com/login/oauth/authorize',
                'token' => 'https://github.com/login/oauth/access_token',
                'user' => 'https://api.github.com/user',
                'scope' => 'user:email',
                'map' => fn ($u) => ['email' => $u['email'] ?? null, 'name' => $u['name'] ?? ($u['login'] ?? null), 'github' => true],
            ],
            'apple' => [
                'auth' => 'https://appleid.apple.com/auth/authorize',
                'token' => 'https://appleid.apple.com/auth/token',
                'user' => null, // email comes from the id_token
                'scope' => 'name email',
                'form_post' => true,
                'map' => null,
            ],
        ];
    }

    /** Providers with credentials configured (for the login page buttons). */
    public static function enabledProviders(): array
    {
        return array_values(array_filter(
            ['google', 'facebook', 'twitter', 'github', 'apple'],
            fn ($p) => setting('oauth_' . $p . '_id') && setting('oauth_' . $p . '_secret')
        ));
    }

    public function redirect(Request $request, string $provider)
    {
        $config = $this->providers()[$provider] ?? abort(404);
        $clientId = setting('oauth_' . $provider . '_id') ?: abort(404);

        $state = Str::random(32);
        session(['oauth_state' => $state]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => route('social.callback', $provider),
            'response_type' => 'code',
            'scope' => $config['scope'],
            'state' => $state,
        ];

        if (! empty($config['pkce'])) {
            $verifier = Str::random(64);
            session(['oauth_verifier' => $verifier]);
            $params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            $params['code_challenge_method'] = 'S256';
        }
        if (! empty($config['form_post'])) {
            $params['response_mode'] = 'form_post';
        }

        return redirect()->away($config['auth'] . '?' . http_build_query($params));
    }

    public function callback(Request $request, string $provider)
    {
        $config = $this->providers()[$provider] ?? abort(404);

        if ($request->input('state') !== session('oauth_state') || ! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['email' => __('Social login failed. Please try again.')]);
        }

        $tokenParams = [
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'redirect_uri' => route('social.callback', $provider),
            'client_id' => setting('oauth_' . $provider . '_id'),
            'client_secret' => setting('oauth_' . $provider . '_secret'),
        ];
        if (! empty($config['pkce'])) {
            $tokenParams['code_verifier'] = session('oauth_verifier');
        }

        $tokenRequest = Http::asForm()->acceptJson()->timeout(10);
        if (! empty($config['basic'])) {
            $tokenRequest = $tokenRequest->withBasicAuth($tokenParams['client_id'], $tokenParams['client_secret']);
            unset($tokenParams['client_secret']);
        }

        try {
            $tokenRes = $tokenRequest->post($config['token'], $tokenParams);
            $accessToken = $tokenRes->json('access_token');

            if ($provider === 'apple') {
                $info = $this->decodeJwtPayload((string) $tokenRes->json('id_token'));
                $email = $info['email'] ?? null;
                $name = trim((string) ($request->input('user') ? (json_decode($request->input('user'), true)['name']['firstName'] ?? '') : ''));
            } else {
                if (! $accessToken) {
                    throw new \RuntimeException('No access token: ' . $tokenRes->body());
                }
                $userRes = Http::withToken($accessToken)->acceptJson()->timeout(10)->get($config['user']);
                $mapped = ($config['map'])($userRes->json() ?? []);
                $email = $mapped['email'] ?? null;
                $name = $mapped['name'] ?? null;

                // GitHub may hide the primary email; fetch it explicitly.
                if (! $email && ! empty($mapped['github'])) {
                    $emails = Http::withToken($accessToken)->acceptJson()->get('https://api.github.com/user/emails')->json();
                    $email = collect($emails)->firstWhere('primary', true)['email'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => __('Social login failed. Please try again.')]);
        }

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => __('Your social account did not provide an email address.')]);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            abort_unless(setting('registration_enabled', true), 403, __('Registration is disabled.'));
            $user = User::create([
                'name' => $name ?: Str::before($email, '@'),
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

        return redirect()->intended(route('dashboard'));
    }

    protected function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }

        return json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true) ?: [];
    }
}
