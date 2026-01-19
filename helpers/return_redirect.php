<?php

declare(strict_types=1);

function redirect_back_with_params(string $fallback, array $params): void
{
    $return = (string)($_POST['return'] ?? $_GET['return'] ?? '');
    $return = trim($return);

    // fallback seguro
    if ($return === '' || preg_match('~^https?://~i', $return)) {
        $return = $fallback;
    }

    // se vier só "index.php", transforma em /InterYSY/index.php
    $path = parse_url($return, PHP_URL_PATH) ?: $fallback;

    if ($path !== '' && $path[0] !== '/') {
        $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); // /InterYSY
        $path = $base . '/' . ltrim($path, '/');
    }

    $query = parse_url($return, PHP_URL_QUERY) ?? '';
    parse_str($query, $q);

    foreach ($params as $k => $v) {
        $q[$k] = $v;
    }

    $dest = $path . (count($q) ? ('?' . http_build_query($q)) : '');
    header('Location: ' . $dest);
    exit;
}
