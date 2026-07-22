<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale resolution order: ?lang= switch → session → user preference →
 * browser Accept-Language → site default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_installed()) {
            return $next($request);
        }

        $active = active_languages()->pluck('code')->all() ?: ['en'];

        if ($request->filled('lang') && in_array($request->query('lang'), $active, true)) {
            session(['locale' => $request->query('lang')]);
            if ($request->user()) {
                $request->user()->forceFill(['locale' => $request->query('lang')])->save();
            }
        }

        $locale = session('locale')
            ?? $request->user()?->locale
            ?? $this->fromBrowser($request, $active)
            ?? setting('default_language', 'en');

        if (! in_array($locale, $active, true)) {
            $locale = in_array('en', $active, true) ? 'en' : $active[0];
        }

        app()->setLocale($locale);

        return $next($request);
    }

    protected function fromBrowser(Request $request, array $active): ?string
    {
        if (! setting('auto_detect_language', true)) {
            return null;
        }
        foreach (explode(',', (string) $request->server('HTTP_ACCEPT_LANGUAGE')) as $part) {
            $code = strtolower(substr(trim($part), 0, 2));
            if (in_array($code, $active, true)) {
                return $code;
            }
        }

        return null;
    }
}
