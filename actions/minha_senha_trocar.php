<?php
require '../config/database.php';

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/user_password.php';

auth_session_start();

$u = auth_require_login();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'minha_senha_trocar')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$senhaAtual  = (string)($_POST['senha_atual'] ?? '');
$senhaNova   = (string)($_POST['senha_nova'] ?? '');
$senhaConfirm = (string)($_POST['senha_confirm'] ?? '');

if ($senhaNova === '' || $senhaAtual === '') {
    http_response_code(400);
    exit('Dados inválidos.');
}

if (strlen($senhaNova) < 8) {
    http_response_code(400);
    exit('Nova senha muito curta.');
}

if ($senhaNova !== $senhaConfirm) {
    http_response_code(400);
    exit('Confirmação não confere.');
}

if ($senhaNova === $senhaAtual) {
    http_response_code(400);
    exit('A nova senha deve ser diferente da atual.');
}

// Busca o usuário do banco (pra verificar a senha atual com o hash real)
$userId = (int)($u['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    exit('Usuário inválido.');
}

$col = users_password_hash_field_select($pdo);

$stmt = $pdo->prepare("SELECT id, {$col} FROM users WHERE id = ? AND ativo = 1 LIMIT 1");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(403);
    exit('Usuário não encontrado/inativo.');
}

// valida senha atual
if (!user_verify_password($row, $pdo, $senhaAtual)) {
    header('Location: ../minha_senha.php?toast=erro');
    exit;
}

// atualiza senha
$ok = user_set_password($pdo, $userId, $senhaNova);

csrf_rotate('minha_senha_trocar');

if ($ok) {
    // força re-login opcional (mais seguro). Se quiser manter logado, pode comentar.
    auth_logout();
    header('Location: ../login.php?toast=senha_alterada');
    exit;
}

header('Location: ../minha_senha.php?toast=erro');
exit;
