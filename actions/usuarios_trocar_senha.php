<?php

require '../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/user_password.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'usuarios_trocar_senha')) {
    audit_log($pdo, 'change_password', 'user', null, ['reason' => 'csrf_invalid'], null, null, false, 'csrf_invalid', 'Falha ao trocar senha (CSRF inválido).');
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = (int)($_POST['id'] ?? 0);
$senha = trim($_POST['senha'] ?? '');

if ($id <= 0 || $senha === '') {
    audit_log($pdo, 'change_password', 'user', $id > 0 ? $id : null, ['reason' => 'validation_error', 'id' => $id], null, null, false, 'validation_error', 'Falha ao trocar senha (validação).');
    http_response_code(400);
    exit('Dados inválidos.');
}

// before “safe” (não pega hash/senha)
$stmt = $pdo->prepare("SELECT id, nome, usuario, role, ativo FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$before = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$before) {
    audit_log($pdo, 'change_password', 'user', $id, ['reason' => 'not_found'], null, null, false, 'not_found', "Falha ao trocar senha do usuário #{$id} (não encontrado).");
    http_response_code(404);
    exit('Usuário não encontrado.');
}

user_set_password($pdo, $id, $senha);

csrf_rotate('usuarios_trocar_senha');

audit_log(
    $pdo,
    'change_password',
    'user',
    $id,
    ['usuario' => (string)($before['usuario'] ?? ''), 'nome' => (string)($before['nome'] ?? '')],
    null,
    null,
    true,
    null,
    "Alterou a senha do usuário #{$id} (@{$before['usuario']})."
);

header('Location: ../usuarios.php?toast=senha_alterada');
exit;
