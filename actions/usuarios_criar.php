<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'usuarios_criar')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('usuarios_criar');

$nome = trim((string)($_POST['nome'] ?? ''));
$usuario = trim((string)($_POST['usuario'] ?? ''));
$role = trim((string)($_POST['role'] ?? 'operador'));

$rolesPermitidos = ['admin', 'operador', 'leitura'];
if ($nome === '' || $usuario === '') {
    http_response_code(400);
    exit('Dados inválidos.');
}
if (!in_array($role, $rolesPermitidos, true)) {
    http_response_code(400);
    exit('Permissão inválida.');
}

// evita duplicidade
$chk = $pdo->prepare("SELECT 1 FROM users WHERE usuario = ? LIMIT 1");
$chk->execute([$usuario]);
if ($chk->fetchColumn()) {
    http_response_code(409);
    exit('Usuário já existe.');
}

// senha temporária segura (12 chars)
$senhaTemp = bin2hex(random_bytes(6)); // 12 hex
$hash = password_hash($senhaTemp, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (nome, usuario, senha_hash, role, ativo) VALUES (?, ?, ?, ?, 1)");
$ok = $stmt->execute([$nome, $usuario, $hash, $role]);

if ($ok) {
    header('Location: ../usuarios.php?toast=criado&senha=' . urlencode($senhaTemp));
    exit;
}

http_response_code(500);
exit('Erro ao criar usuário.');
