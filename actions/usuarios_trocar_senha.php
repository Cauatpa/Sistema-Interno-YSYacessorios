<?php
require '../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/user_password.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'usuarios_trocar_senha')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = (int)($_POST['id'] ?? 0);
$senha = trim($_POST['senha'] ?? '');

if ($id <= 0 || $senha === '') {
    http_response_code(400);
    exit('Dados inválidos.');
}

user_set_password($pdo, $id, $senha);

csrf_rotate('usuarios_trocar_senha');

header('Location: ../usuarios.php?toast=senha_alterada');
exit;
