<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the whole app behind the installer:
 *  - not installed  → everything redirects to /install
 *  - installed      → /install is permanently blocked (404)
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInstallRoute = $request->is('install') || $request->is('install/*');

        if (! is_installed()) {
            return $isInstallRoute ? $next($request) : redirect('/install');
        }

        if ($isInstallRoute) {
            abort(404);
        }

        return $next($request);
    }
}
