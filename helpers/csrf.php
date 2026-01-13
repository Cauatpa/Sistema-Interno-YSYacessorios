<?php

function csrf_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;

    // Detecta HTTPS (em produção, isso fica true)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    // PHP >= 7.3 aceita SameSite aqui
    session_set_cookie_params([
        'lifetime' => 0,              // sessão até fechar o navegador
        'path' => '/',
        'domain' => '',               // deixe vazio se não precisar
        'secure' => $isHttps,         // só true quando tiver HTTPS
        'httponly' => true,           // impede JS de ler
        'samesite' => 'Lax',          // bom default (pode ser 'Strict' se quiser mais travado)
    ]);

    // evita aceitar IDs de sessão “forçados” (session fixation)
    ini_set('session.use_strict_mode', '1');

    // recomendado: não expor id em URL
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_start();
}

function csrf_token(string $formId = 'default'): string
{
    csrf_session_start();

    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    if (empty($_SESSION['csrf_tokens'][$formId])) {
        $_SESSION['csrf_tokens'][$formId] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$formId];
}

function csrf_validate(?string $token, string $formId = 'default'): bool
{
    csrf_session_start();

    if (!isset($_SESSION['csrf_tokens'][$formId])) {
        return false;
    }

    if (!$token || !is_string($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_tokens'][$formId], $token);
}

/**
 * Opcional: depois de validar, pode girar o token (mais “empresa”).
 */
function csrf_rotate(string $formId = 'default'): void
{
    csrf_session_start();
    if (isset($_SESSION['csrf_tokens'][$formId])) {
        unset($_SESSION['csrf_tokens'][$formId]);
    }
}
