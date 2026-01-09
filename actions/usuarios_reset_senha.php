<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'usuarios_reset')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('usuarios_reset');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

$senhaTemp = bin2hex(random_bytes(6));
$hash = password_hash($senhaTemp, PASSWORD_DEFAULT);

$up = $pdo->prepare("UPDATE users SET senha_hash = ? WHERE id = ?");
$ok = $up->execute([$hash, $id]);

if ($ok) {
    header('Location: ../usuarios.php?toast=reset&senha=' . urlencode($senhaTemp));
    exit;
}

http_response_code(500);
exit('Erro ao resetar senha.');
