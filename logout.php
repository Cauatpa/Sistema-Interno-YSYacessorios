<?php
require_once __DIR__ . '/config/bootstrap.php';
bootstrap_app();
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/validation.php';

csrf_session_start();
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'logout')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

csrf_rotate('logout');

// encerra sessão
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// redireciona (ajuste se seu login tiver outro nome)
header('Location: login.php');
exit;
