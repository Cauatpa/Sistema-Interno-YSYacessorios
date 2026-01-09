<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

auth_session_start();

// só POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'login')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('login');

// Campos
$usuario = trim((string)($_POST['usuario'] ?? ''));
$senha   = (string)($_POST['senha'] ?? '');

if ($usuario === '' || $senha === '') {
    http_response_code(400);
    exit('Usuário e senha são obrigatórios.');
}

// TENTA LOGIN USANDO O AUTH.PHP
if (!auth_login($pdo, $usuario, $senha)) {
    http_response_code(401);
    exit('Usuário ou senha inválidos.');
}

// Sucesso
header('Location: ../index.php');
exit;
