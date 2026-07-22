<?php

use Illuminate\Http\Request;

// Hide deprecation notices (e.g. PHP 8.5 renaming PDO::MYSQL_ATTR_SSL_CA).
// These are harmless but pollute the page when a host has display_errors on;
// real warnings, errors and Laravel's own debug output are unaffected.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// First-run bootstrap: on a fresh upload there is no .env yet. Create it from
// .env.example and generate an APP_KEY so the web installer at /install can
// run without any manual configuration.
$envPath = __DIR__.'/../.env';
if (! file_exists($envPath) && ! file_exists(__DIR__.'/../storage/installed.lock')) {
    $example = __DIR__.'/../.env.example';
    $content = file_exists($example) ? file_get_contents($example) : "APP_NAME=Shortl\nAPP_KEY=\n";
    $key = 'base64:'.base64_encode(random_bytes(32));
    $content = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $content, 1, $replaced);
    if (! $replaced) {
        $content .= "\nAPP_KEY={$key}\n";
    }
    // Pre-install everything runs on file drivers; the installer rewrites these.
    $content = preg_replace('/^SESSION_DRIVER=.*$/m', 'SESSION_DRIVER=file', $content);
    $content = preg_replace('/^CACHE_STORE=.*$/m', 'CACHE_STORE=file', $content);
    @file_put_contents($envPath, $content);
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
