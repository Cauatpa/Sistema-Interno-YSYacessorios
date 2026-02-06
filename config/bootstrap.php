<?php

declare(strict_types=1);

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;

    foreach ($lines as $line) {
        $line = trim($line);

        // ignora comentários
        if ($line === '' || str_starts_with($line, '#')) continue;

        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        if ($key === '') continue;

        // remove aspas "..." ou '...'
        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }

        // não sobrescreve se já existir no ambiente
        $existing = getenv($key);
        if ($existing !== false && $existing !== null && trim((string)$existing) !== '') {
            continue;
        }

        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

function bootstrap_app(): void
{
    if (defined('APP_BOOTSTRAPPED')) {
        return;
    }
    define('APP_BOOTSTRAPPED', true);

    // ✅ carrega config/.env (antes de usar getenv)
    load_env_file(__DIR__ . '/.env');

    $env = getenv('APP_ENV') ?: 'production';
    $timezone = getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo';

    if (function_exists('date_default_timezone_set')) {
        date_default_timezone_set($timezone);
    }

    if ($env === 'local' || $env === 'development') {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
    }

    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }

    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
