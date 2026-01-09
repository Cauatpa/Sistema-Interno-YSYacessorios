<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'usuarios_toggle')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('usuarios_toggle');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

$me = auth_user();
if ($me && (int)$me['id'] === $id) {
    http_response_code(403);
    exit('Você não pode desativar seu próprio usuário.');
}

$stmt = $pdo->prepare("SELECT ativo FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

$novo = ((int)$row['ativo'] === 1) ? 0 : 1;

$up = $pdo->prepare("UPDATE users SET ativo = ? WHERE id = ?");
$ok = $up->execute([$novo, $id]);

if ($ok) {
    header('Location: ../usuarios.php?toast=toggle');
    exit;
}

http_response_code(500);
exit('Erro ao alterar status.');
