<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
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
