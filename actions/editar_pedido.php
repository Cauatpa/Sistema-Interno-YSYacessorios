<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'editar_pedido')) {
    audit_log($pdo, 'update', 'retirada', null, ['reason' => 'csrf_invalid'], null, null, false, 'csrf_invalid', 'CSRF inválido.', 'Falha ao editar retirada (CSRF inválido).');
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('editar_pedido');

$id = int_pos($_POST['id'] ?? 0);
$produto = trim((string)($_POST['produto'] ?? ''));
$tipo = one_of(trim((string)($_POST['tipo'] ?? '')), ['prata', 'ouro'], '');
$qtdSolic = int_pos($_POST['quantidade_solicitada'] ?? 0);

if ($id <= 0 || $produto === '' || $tipo === '' || $qtdSolic <= 0) {
    audit_log($pdo, 'update', 'retirada', $id ?: null, [
        'produto' => $produto,
        'tipo' => $tipo,
        'quantidade_solicitada' => $qtdSolic,
        'reason' => 'validation_error'
    ], null, null, false, 'validation_error', 'Campos inválidos.', "Falha ao editar retirada #{$id} (validação).");

    http_response_code(400);
    exit('Dados inválidos.');
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
    audit_log($pdo, 'update', 'retirada', $id, ['reason' => 'not_found'], null, null, false, 'not_found', 'Pedido não encontrado.', "Falha ao editar retirada #{$id} (não encontrada).");
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$competencia = (string)($before['competencia'] ?? '');
$status = (string)($before['status'] ?? '');
$qtdRetirada = isset($before['quantidade_retirada']) ? (int)$before['quantidade_retirada'] : null;

if (!competencia_valida($competencia)) {
    audit_log($pdo, 'update', 'retirada', $id, ['competencia' => $competencia, 'reason' => 'invalid_competencia'], $before, null, false, 'invalid_competencia', 'Competência inválida.', "Falha ao editar retirada #{$id} (competência inválida).");
    http_response_code(500);
    exit('Competência inválida no registro.');
}

// Regra: admin pode editar mesmo com mês fechado (mantido)
$precisa_balanco = null;
$sem_estoque = null;

if ($status === 'finalizado' && $qtdRetirada !== null) {
    if ($qtdRetirada < $qtdSolic) {
        $precisa_balanco = 1;
        $sem_estoque = 1;
    } else {
        $precisa_balanco = 0;
        $sem_estoque = 0;
    }
}

// Update
if ($precisa_balanco === null) {
    $upd = $pdo->prepare("
        UPDATE retiradas
        SET produto = ?, tipo = ?, quantidade_solicitada = ?
        WHERE id = ? AND deleted_at IS NULL
    ");
    $ok = $upd->execute([$produto, $tipo, $qtdSolic, $id]);
} else {
    $upd = $pdo->prepare("
        UPDATE retiradas
        SET produto = ?, tipo = ?, quantidade_solicitada = ?, precisa_balanco = ?, sem_estoque = ?
        WHERE id = ? AND deleted_at IS NULL
    ");
    $ok = $upd->execute([$produto, $tipo, $qtdSolic, $precisa_balanco, $sem_estoque, $id]);
}

if (!$ok) {
    audit_log($pdo, 'update', 'retirada', $id, ['competencia' => $competencia, 'reason' => 'db_error'], $before, null, false, 'db_error', 'Erro ao salvar edição.', "Falha ao editar retirada #{$id} (erro banco).");
    http_response_code(500);
    exit('Erro ao salvar edição.');
}

// after
$stmt2 = $pdo->prepare("SELECT * FROM retiradas WHERE id = ? LIMIT 1");
$stmt2->execute([$id]);
$after = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;

audit_log(
    $pdo,
    'update',
    'retirada',
    $id,
    [
        'competencia' => $competencia,
        'fields' => ['produto', 'tipo', 'quantidade_solicitada', 'precisa_balanco', 'sem_estoque']
    ],
    $before,
    $after,
    true,
    null,
    null,
    "Editou retirada #{$id} (mês {$competencia})."
);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'editado',
    'highlight_id' => $id
]);
