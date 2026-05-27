<?php
/**
 * Lightweight .env file loader
 * Reads key=value pairs from a .env file and sets them via putenv() / $_ENV.
 * Skips blank lines and comments (#).
 * Does NOT overwrite already-set environment variables (server config takes priority).
 */

function loadEnv(string $path): void {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Only process valid KEY=VALUE pairs
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes if present
        if (preg_match('/^(["\'])(.*)(\1)$/', $value, $m)) {
            $value = $m[2];
        }

        // Don't overwrite values already set by the server environment
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}
