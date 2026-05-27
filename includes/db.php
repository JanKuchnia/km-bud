<?php
/**
 * Database connection (PDO singleton)
 */

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $config = require __DIR__ . '/../config/config.php';
    $c = $config['db'];

    try {
        $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Discard any output buffer if active
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Log the error for server diagnostics
        error_log('[Database Connection Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        // Set response header to 500
        if (!headers_sent()) {
            http_response_code(500);
        }

        // Expose the error to the 500 page diagnostics
        $GLOBALS['DB_CONNECTION_ERROR'] = $e;

        // Load our custom 500.php page natively
        require __DIR__ . '/../500.php';
        exit;
    }

    return $pdo;
}
