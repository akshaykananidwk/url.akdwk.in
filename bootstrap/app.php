<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the reverse proxy (aaPanel/Nginx/Apache/Cloudflare) so the app
        // correctly detects HTTPS from X-Forwarded-Proto. Without this, a site
        // served over HTTPS through a proxy looks like plain HTTP to Laravel,
        // which breaks secure session cookies and CSRF — the usual cause of
        // "forms don't save" / "419 Page Expired" on shared hosting.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'verified.setting' => \App\Http\Middleware\EnsureEmailVerifiedIfRequired::class,
            'not.suspended' => \App\Http\Middleware\EnsureNotSuspended::class,
            'apikey' => \App\Http\Middleware\AuthenticateApiKey::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*', // payment gateway callbacks sign their own payloads
            'auth/social/*', // Apple form_post callback
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
