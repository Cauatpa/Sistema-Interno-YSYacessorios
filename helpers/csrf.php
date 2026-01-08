<?php

function csrf_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Ajuste conforme o ambiente:
        // Em localhost sem https, secure=false
        // Em produção com https, secure=true
        $secure = false;

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
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
