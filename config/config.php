<?php
/**
 * KM-BUD Configuration
 * Credentials are loaded from environment variables.
 * On local: set in .env (git-ignored)
 * On Hostinger: set via hPanel → Advanced → PHP Config, or .env placed on the server
 */

require_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

// ─── Environment ─────────────────────────────────────────────
$appEnv = getenv('APP_ENV') ?: 'production';
$isDev  = ($appEnv === 'development');

// PHP error display — show errors locally, suppress on production
if ($isDev) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

return [
    'app_env' => $appEnv,
    'dev'     => $isDev,

    'db' => [
        'host'    => getenv('DB_HOST')    ?: 'localhost',
        'name'    => getenv('DB_NAME')    ?: 'kmbud',
        'user'    => getenv('DB_USER')    ?: 'root',
        'pass'    => getenv('DB_PASS')    !== false ? getenv('DB_PASS') : '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'admin' => [
        'password_hash' => getenv('ADMIN_PASSWORD_HASH') ?: '',
    ],
    'tinypng' => [
        'api_key' => getenv('TINYPNG_API_KEY') ?: '',
    ],
    'google_places' => [
        'api_key'  => getenv('GOOGLE_PLACES_API_KEY')  ?: '',
        'place_id' => getenv('GOOGLE_PLACES_PLACE_ID') ?: '',
    ],
    'upload' => [
        'max_size'      => 10 * 1024 * 1024,
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'quality'       => 85,
        'max_dimension' => 2048,
    ],
];
