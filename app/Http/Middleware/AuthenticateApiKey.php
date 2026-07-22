<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\PlanLimits;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * API authentication via "Authorization: Bearer sk_..." keys, with a
 * per-key/per-plan rate limit.
 */
class AuthenticateApiKey
{
    public function __construct(protected PlanLimits $limits)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Api-Key');
        if (! $token) {
            return response()->json(['message' => 'API key missing. Pass it as a Bearer token.'], 401);
        }

        $key = ApiKey::findByPlainKey($token);
        if (! $key || ! $key->user) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        $user = $key->user;
        if ($user->isSuspended()) {
            return response()->json(['message' => 'Account suspended.'], 403);
        }
        if (! $this->limits->hasFeature($user, 'api')) {
            return response()->json(['message' => 'API access is not available on your plan.'], 403);
        }

        $rate = $this->limits->apiRate($user, $key->rate_limit);
        $bucket = 'api:' . $key->id;
        if (RateLimiter::tooManyAttempts($bucket, $rate)) {
            return response()->json(['message' => 'Rate limit exceeded.'], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($bucket));
        }
        RateLimiter::hit($bucket, 60);

        $key->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($user);
        $request->attributes->set('api_key', $key);

        return $next($request)
            ->header('X-RateLimit-Limit', (string) $rate)
            ->header('X-RateLimit-Remaining', (string) max(0, $rate - RateLimiter::attempts($bucket)));
    }
}
