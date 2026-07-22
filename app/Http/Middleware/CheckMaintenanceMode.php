<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings-driven maintenance mode: blocks the public site for everyone
 * except admins. Short link redirects keep working (existing links should
 * never break), and the login route stays reachable so admins can get in.
 */
class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_installed() || ! setting('maintenance_mode', false)) {
            return $next($request);
        }

        if ($request->user()?->isAdmin()
            || $request->is('login') || $request->is('logout') || $request->is('two-factor*')
            || $request->is('admin') || $request->is('admin/*')
            || $request->route()?->getName() === 'redirect') {
            return $next($request);
        }

        return response()->view('errors.maintenance', [], 503);
    }
}
