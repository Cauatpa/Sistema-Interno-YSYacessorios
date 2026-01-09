<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/validation.php';

csrf_session_start();
post_only();

// Somente admin
if (!auth_has_role('admin')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'admin_trocar_senha')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('admin_trocar_senha');

// Campos
$id = (int)($_POST['id'] ?? 0);
$senha = (string)($_POST['senha'] ?? '');
$senha2 = (string)($_POST['senha2'] ?? '');

if ($id <= 0) {
    http_response_code(400);
    exit('Usuário inválido.');
}
$senha = trim($senha);
$senha2 = trim($senha2);

if ($senha === '' || $senha2 === '') {
    http_response_code(400);
    exit('Preencha a senha e a confirmação.');
}
if ($senha !== $senha2) {
    http_response_code(400);
    exit('As senhas não coincidem.');
}
if (mb_strlen($senha) < 6) {
    http_response_code(400);
    exit('A senha deve ter pelo menos 6 caracteres.');
}

// Verifica se usuário existe
$chk = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$chk->execute([$id]);
if (!$chk->fetchColumn()) {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

// Hash seguro
$hash = password_hash($senha, PASSWORD_DEFAULT);

// Atualiza senha
$up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$ok = $up->execute([$hash, $id]);

if ($ok) {
    redirect_with_query('../usuarios.php', ['toast' => 'senha_alterada']);
}

http_response_code(500);
exit('Erro ao alterar senha.');
