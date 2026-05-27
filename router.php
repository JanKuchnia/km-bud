<?php
/**
 * PHP Built-in Dev Server Router
 *
 * Usage: /opt/lampp/bin/php -S localhost:8000 router.php
 *
 * This file is ONLY for the PHP dev server.
 * On Apache / Hostinger production, .htaccess handles routing automatically.
 */

// ── Load environment early so 500.php can read APP_ENV ───────────────────────
require_once __DIR__ . '/config/env_loader.php';
loadEnv(__DIR__ . '/.env');

// ── Resolve the requested path ────────────────────────────────────────────────
$uri      = $_SERVER['REQUEST_URI'];
$path     = parse_url($uri, PHP_URL_PATH);
$diskFile = __DIR__ . $path;

// Pass static files (images, css, js, ico, webp…) through directly
if ($path !== '/' && is_file($diskFile)) {
    return false;
}

// ── Register shutdown handler → catches fatal errors → shows 500 ──────────────
register_shutdown_function(function () {
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if ($error && in_array($error['type'], $fatalTypes, true)) {
        // Discard any partial output already buffered
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
        }
        require __DIR__ . '/500.php';
    }
});

// Buffer output so we can cleanly swap to an error page on exception
ob_start();

// ── Route map: URL path → PHP file ───────────────────────────────────────────
$routes = [
    '/'                  => 'index.php',
    '/index.php'         => 'index.php',
    '/galeria.php'       => 'galeria.php',
    '/404.php'           => '404.php',
    '/500.php'           => '500.php',

    // Admin panel
    '/admin'             => 'admin/login.php',
    '/admin/'            => 'admin/login.php',
    '/admin/index.php'   => 'admin/index.php',
    '/admin/login.php'   => 'admin/login.php',
    '/admin/logout.php'  => 'admin/logout.php',
    '/admin/api.php'     => 'admin/api.php',
];

// ── Dispatch ──────────────────────────────────────────────────────────────────
try {
    $cleanPath = rtrim($path, '/') ?: '/';

    if (isset($routes[$cleanPath])) {
        // Known route
        require __DIR__ . '/' . $routes[$cleanPath];

    } elseif (is_file($diskFile . '.php')) {
        // Allow /galeria → galeria.php style clean URLs
        require $diskFile . '.php';

    } else {
        // Nothing matched → 404
        ob_end_clean();
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }

} catch (Throwable $e) {
    // Uncaught exception → 500
    ob_end_clean();
    error_log('[router] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    require __DIR__ . '/500.php';
    exit;
}

ob_end_flush();
