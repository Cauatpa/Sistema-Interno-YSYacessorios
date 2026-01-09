<?php

declare(strict_types=1);

if (!function_exists('post_only')) {
    function post_only(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido.');
        }
    }
}

function require_fields(array $data, array $fields): void
{
    foreach ($fields as $f) {
        if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
            http_response_code(400);
            exit("Campo obrigatório: {$f}");
        }
    }
}

function int_pos($v): int
{
    $n = filter_var($v, FILTER_VALIDATE_INT);
    return ($n !== false && $n > 0) ? (int)$n : 0;
}

function int_nonneg($v): int
{
    $n = filter_var($v, FILTER_VALIDATE_INT);
    return ($n !== false && $n >= 0) ? (int)$n : -1;
}

function one_of(string $value, array $allowed, string $fallback): string
{
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function redirect_with_query(string $path, array $query = []): void
{
    $url = $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }
    header("Location: {$url}");
    exit;
}
