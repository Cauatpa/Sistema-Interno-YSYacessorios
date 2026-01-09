<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'reabrir_mes')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('reabrir_mes');

$competencia = (string)($_POST['competencia'] ?? '');
$confirm = trim((string)($_POST['confirm'] ?? ''));

if (!competencia_valida($competencia)) {
    http_response_code(400);
    exit('Competência inválida.');
}

$expected = "REABRIR {$competencia}";
if (mb_strtoupper($confirm, 'UTF-8') !== $expected) {
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: {$expected}");
}

$stmt = $pdo->prepare("DELETE FROM fechamentos WHERE competencia = ? LIMIT 1");
$stmt->execute([$competencia]);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'mes_reaberto'
]);
