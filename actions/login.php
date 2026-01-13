<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/rate_limit.php';

auth_session_start();

// CSRF do login
if (!csrf_validate($_POST['csrf_token'] ?? null, 'login')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$usuario = trim((string)($_POST['usuario'] ?? ''));
$usuario = mb_strtolower($usuario);
$usuario = mb_substr($usuario, 0, 80);

$senha = (string)($_POST['senha'] ?? '');

$ip = rl_client_ip();

// Rate limit antes de autenticar
if (rl_is_blocked($pdo, $ip, $usuario, 6, 10)) {
    $secs = rl_seconds_until_unblock($pdo, $ip, $usuario, 6, 10);
    $min = (int)ceil($secs / 60);
    header('Location: ../login.php?err=1&wait=' . $min);
    exit;
}

// tenta login
$ok = auth_login($pdo, $usuario, $senha);

// registra tentativa (sucesso ou falha)
rl_log_attempt($pdo, $ip, $usuario, $ok);

csrf_rotate('login');

if ($ok) {
    header('Location: ../index.php');
    exit;
}

// erro sempre genérico
header('Location: ../login.php?err=1');
exit;
