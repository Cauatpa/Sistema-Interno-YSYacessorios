<?php

declare(strict_types=1);

function redirect_back_with_params(string $fallback, array $params): void
{
    $return = (string)($_POST['return'] ?? $_GET['return'] ?? '');
    $return = trim($return);

    // fallback seguro
    // - bloqueia http(s) externo
    // - bloqueia esquema-relative "//dominio.com"
    // - bloqueia strings com quebras de linha (header injection)
    if (
        $return === '' ||
        preg_match('~^https?://~i', $return) ||
        str_starts_with($return, '//') ||
        preg_match('/[\r\n]/', $return)
    ) {
        $return = $fallback;
    }

    // path do destino
    $path = (string)(parse_url($return, PHP_URL_PATH) ?? '');
    if ($path === '') {
        $path = $fallback;
    }

    // se vier "index.php" ou "pages/....php", transforma em "/InterYSY/index.php"
    if ($path !== '' && $path[0] !== '/') {
        $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); // /InterYSY
        $path = $base . '/' . ltrim($path, '/');
    }

    // query existente do return
    $query = (string)(parse_url($return, PHP_URL_QUERY) ?? '');
    $q = [];
    if ($query !== '') {
        parse_str($query, $q);
        if (!is_array($q)) $q = [];
    }

    // injeta/override params
    foreach ($params as $k => $v) {
        $q[$k] = $v;
    }

    $dest = $path . (count($q) ? ('?' . http_build_query($q)) : '');
    header('Location: ' . $dest);
    exit;
}
