<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'usuarios_criar')) {
    audit_log($pdo, 'create', 'user', null, ['reason' => 'csrf_invalid'], null, null, false, 'csrf_invalid', 'Falha ao criar usuário (CSRF inválido).');
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('usuarios_criar');

$nome = trim((string)($_POST['nome'] ?? ''));
$usuario = trim((string)($_POST['usuario'] ?? ''));
$role = trim((string)($_POST['role'] ?? 'operador'));

$rolesPermitidos = ['admin', 'operador', 'leitura'];
if ($nome === '' || $usuario === '') {
    audit_log($pdo, 'create', 'user', null, ['reason' => 'validation_error', 'nome' => $nome, 'usuario' => $usuario, 'role' => $role], null, null, false, 'validation_error', 'Falha ao criar usuário (validação).');
    http_response_code(400);
    exit('Dados inválidos.');
}
if (!in_array($role, $rolesPermitidos, true)) {
    audit_log($pdo, 'create', 'user', null, ['reason' => 'invalid_role', 'role' => $role], null, null, false, 'invalid_role', 'Falha ao criar usuário (role inválida).');
    http_response_code(400);
    exit('Permissão inválida.');
}

$chk = $pdo->prepare("SELECT 1 FROM users WHERE usuario = ? LIMIT 1");
$chk->execute([$usuario]);
if ($chk->fetchColumn()) {
    audit_log($pdo, 'create', 'user', null, ['reason' => 'already_exists', 'usuario' => $usuario], null, null, false, 'already_exists', 'Falha ao criar usuário (já existe).');
    http_response_code(409);
    exit('Usuário já existe.');
}

$senhaTemp = bin2hex(random_bytes(6));
$hash = password_hash($senhaTemp, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (nome, usuario, senha_hash, role, ativo) VALUES (?, ?, ?, ?, 1)");
$ok = $stmt->execute([$nome, $usuario, $hash, $role]);

if ($ok) {
    $newId = (int)$pdo->lastInsertId();

    // ⚠️ Não logar senha
    audit_log(
        $pdo,
        'create',
        'user',
        $newId,
        ['usuario' => $usuario, 'nome' => $nome, 'role' => $role],
        null,
        ['id' => $newId, 'usuario' => $usuario, 'nome' => $nome, 'role' => $role, 'ativo' => 1],
        true,
        null,
        "Criou usuário #{$newId} (@{$usuario})."
    );

    header('Location: ../usuarios.php?toast=criado&senha=' . urlencode($senhaTemp));
    exit;
}

audit_log($pdo, 'create', 'user', null, ['reason' => 'db_error', 'usuario' => $usuario], null, null, false, 'db_error', 'Falha ao criar usuário (erro banco).');
http_response_code(500);
exit('Erro ao criar usuário.');
