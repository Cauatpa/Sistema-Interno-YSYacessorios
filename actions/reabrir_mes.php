<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'reabrir_mes')) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, ['reason' => 'csrf_invalid'], null, null, false, 'csrf_invalid', 'CSRF inválido.', 'Falha ao reabrir mês (CSRF inválido).');
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('reabrir_mes');

$competencia = (string)($_POST['competencia'] ?? '');
$confirm = trim((string)($_POST['confirm'] ?? ''));

if (!competencia_valida($competencia)) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, ['competencia' => $competencia, 'reason' => 'invalid_competencia'], null, null, false, 'invalid_competencia', 'Competência inválida.', 'Falha ao reabrir mês (competência inválida).');
    http_response_code(400);
    exit('Competência inválida.');
}

$expected = "REABRIR {$competencia}";
if (mb_strtoupper($confirm, 'UTF-8') !== $expected) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, ['competencia' => $competencia, 'reason' => 'invalid_confirm', 'confirm' => $confirm], null, null, false, 'invalid_confirm', 'Confirmação inválida.', "Falha ao reabrir mês {$competencia} (confirmação inválida).");
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: {$expected}");
}

// before: existe fechamento?
$chk = $pdo->prepare("SELECT competencia, created_at FROM fechamentos WHERE competencia = ? LIMIT 1");
$chk->execute([$competencia]);
$before = $chk->fetch(PDO::FETCH_ASSOC) ?: null;

// reabre (delete do fechamento)
$stmt = $pdo->prepare("DELETE FROM fechamentos WHERE competencia = ? LIMIT 1");
$ok = $stmt->execute([$competencia]);

if (!$ok) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, ['competencia' => $competencia, 'reason' => 'db_error'], $before, null, false, 'db_error', 'Erro ao remover fechamento.', "Falha ao reabrir mês {$competencia} (erro banco).");
    http_response_code(500);
    exit('Erro ao reabrir mês.');
}

$after = ['competencia' => $competencia, 'status' => 'aberto'];

audit_log(
    $pdo,
    'reopen_month',
    'fechamento',
    null,
    ['competencia' => $competencia],
    $before,
    $after,
    true,
    null,
    null,
    "Reabriu o mês {$competencia}."
);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'mes_reaberto'
]);
