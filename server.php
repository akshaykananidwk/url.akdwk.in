<?php

/**
 * Router for PHP's built-in dev server:
 *   php -S localhost:8000 -t public server.php
 * Serves static files directly and routes everything else to public/index.php.
 * Production deployments should point Apache/Nginx at public/ instead.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
