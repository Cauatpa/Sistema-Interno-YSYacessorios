<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/return_redirect.php';


auth_session_start();
auth_require_role('admin');
post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'excluir_pedido')) {
    audit_log($pdo, 'delete', 'retirada', null, ['reason' => 'csrf_invalid'], null, null, false, 'csrf_invalid', 'CSRF inválido.', 'Falha ao excluir retirada (CSRF inválido).');
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('excluir_pedido');

$id = int_pos($_POST['id'] ?? 0);
$confirm = trim((string)($_POST['confirm'] ?? ''));

if ($id <= 0) {
    audit_log($pdo, 'delete', 'retirada', null, ['id' => $id, 'reason' => 'invalid_id'], null, null, false, 'invalid_id', 'ID inválido.', 'Falha ao excluir retirada (ID inválido).');
    http_response_code(400);
    exit('ID inválido.');
}

if ($confirm !== "EXCLUIR {$id}") {
    audit_log($pdo, 'delete', 'retirada', $id, ['reason' => 'invalid_confirm', 'confirm' => $confirm], null, null, false, 'invalid_confirm', 'Confirmação inválida.', "Falha ao excluir retirada #{$id} (confirmação inválida).");
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: EXCLUIR {$id}");
}

// Busca registro completo (before)
$stmt = $pdo->prepare("
    SELECT *
    FROM retiradas
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$before = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$before) {
    audit_log($pdo, 'delete', 'retirada', $id, ['reason' => 'not_found'], null, null, false, 'not_found', 'Pedido não encontrado.', "Falha ao excluir retirada #{$id} (não encontrada).");
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$competencia = (string)($before['competencia'] ?? '');

if (!competencia_valida($competencia)) {
    audit_log($pdo, 'delete', 'retirada', $id, ['competencia' => $competencia, 'reason' => 'invalid_competencia'], $before, null, false, 'invalid_competencia', 'Competência inválida.', "Falha ao excluir retirada #{$id} (competência inválida).");
    http_response_code(500);
    exit('Competência inválida no registro.');
}

// Bloqueio mês fechado
if (mes_esta_fechado($pdo, $competencia)) {
    audit_log($pdo, 'delete', 'retirada', $id, ['competencia' => $competencia, 'reason' => 'month_closed'], $before, null, false, 'month_closed', 'Mês fechado.', "Falha ao excluir retirada #{$id} (mês fechado {$competencia}).");
    http_response_code(403);
    exit("Não é possível excluir pedido de mês fechado ({$competencia}).");
}

// Soft delete
$upd = $pdo->prepare("
    UPDATE retiradas
    SET deleted_at = NOW()
    WHERE id = ? AND deleted_at IS NULL
");
$ok = $upd->execute([$id]);

if (!$ok) {
    audit_log($pdo, 'delete', 'retirada', $id, ['competencia' => $competencia, 'reason' => 'db_error'], $before, null, false, 'db_error', 'Falha ao atualizar deleted_at.', "Falha ao excluir retirada #{$id} (erro no banco).");
    http_response_code(500);
    exit('Erro ao excluir pedido.');
}

// after (estado final do registro)
$stmt2 = $pdo->prepare("SELECT * FROM retiradas WHERE id = ? LIMIT 1");
$stmt2->execute([$id]);
$after = $stmt2->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id, 'deleted_at' => date('Y-m-d H:i:s')];

audit_log(
    $pdo,
    'delete',
    'retirada',
    $id,
    ['competencia' => $competencia],
    ['deleted_at' => null],
    ['deleted_at' => date('Y-m-d H:i:s')],
    true,
    null,
    "Excluiu o pedido #{$id} ({$competencia})."
);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'excluido',
    'highlight_id' => $id
]);
