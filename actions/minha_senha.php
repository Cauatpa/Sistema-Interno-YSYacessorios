<?php
require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/user_password.php';

auth_session_start();
$u = auth_require_login(); // precisa estar logado

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Método inválido.');
}

if (!csrf_validate($_POST['csrf_token'] ?? null, 'minha_senha')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$senhaAtual = (string)($_POST['senha_atual'] ?? '');
$senhaNova  = (string)($_POST['senha_nova'] ?? '');
$confirm    = (string)($_POST['senha_confirm'] ?? '');

$senhaAtual = trim($senhaAtual);
$senhaNova  = trim($senhaNova);
$confirm    = trim($confirm);

if ($senhaAtual === '' || $senhaNova === '' || $confirm === '') {
    http_response_code(400);
    exit('Dados inválidos.');
}
if (strlen($senhaNova) < 8) {
    http_response_code(400);
    exit('A nova senha deve ter no mínimo 8 caracteres.');
}
if ($senhaNova !== $confirm) {
    http_response_code(400);
    exit('Confirmação não confere.');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([(int)$u['id']]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    http_response_code(401);
    exit('Usuário inválido.');
}

if (!user_verify_password($userRow, $pdo, $senhaAtual)) {
    http_response_code(400);
    exit('Senha atual incorreta.');
}

user_set_password($pdo, (int)$u['id'], $senhaNova);

// gira token pra “empresa”
csrf_rotate('minha_senha');

// reforço de segurança: troca o id da sessão depois de trocar senha
session_regenerate_id(true);

header('Location: ../index.php?toast=senha_alterada');
exit;
