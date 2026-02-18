<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/return_redirect.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('operador');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'finalizar_pedido')) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao finalizar retirada (CSRF inválido).'
    );
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = int_pos($_POST['id'] ?? 0);
$wantNext = ((int)($_POST['next'] ?? 0) === 1);

$nextTargetId = int_pos($_POST['next_target_id'] ?? 0);

if ($id <= 0) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        null,
        ['reason' => 'invalid_id', 'id' => $id],
        null,
        null,
        false,
        'invalid_id',
        'Falha ao finalizar retirada (ID inválido).'
    );
    http_response_code(400);
    exit('ID inválido.');
}

// BEFORE (para log)
$stmt = $pdo->prepare("
    SELECT
        id, competencia, status,
        produto, tipo, solicitante,
        quantidade_solicitada,
        quantidade_retirada,
        precisa_balanco, sem_estoque, falta_estoque,
        responsavel_estoque, data_finalizacao
    FROM retiradas
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$before = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$before) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['reason' => 'not_found'],
        null,
        null,
        false,
        'not_found',
        "Falha ao finalizar retirada #{$id} (não encontrada)."
    );
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$competencia = (string)($before['competencia'] ?? '');
$status = (string)($before['status'] ?? '');

if (!competencia_valida($competencia)) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['reason' => 'invalid_competencia', 'competencia' => $competencia],
        ['competencia' => $competencia],
        null,
        false,
        'invalid_competencia',
        "Falha ao finalizar retirada #{$id} (competência inválida)."
    );
    http_response_code(500);
    exit('Competência inválida no registro.');
}

if (mes_esta_fechado($pdo, $competencia)) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['reason' => 'month_closed', 'competencia' => $competencia],
        $before,
        null,
        false,
        'month_closed',
        "Falha ao finalizar retirada #{$id} (mês fechado {$competencia})."
    );
    http_response_code(403);
    exit("Não é possível finalizar em mês fechado ({$competencia}).");
}

if ($status === 'finalizado') {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['competencia' => $competencia, 'reason' => 'already_finalized'],
        null,
        null,
        true,
        'already_finalized',
        "Retirada #{$id} já estava finalizada."
    );

    redirect_back_with_params('../index.php', [
        'competencia' => $competencia,
        'toast' => 'ja_finalizado',
        'highlight_id' => $id
    ]);
    exit;
}

$qtdSolicitada = (int)($before['quantidade_solicitada'] ?? 0);

$precisa_balanco = (int)($_POST['precisa_balanco'] ?? 0);
$sem_estoque = (int)($_POST['sem_estoque'] ?? 0);

if ($sem_estoque === 1) {
    $precisa_balanco = 0; // trava balanço no form
    $balanco_feito = 0;
} else {
    $balanco_feito = (int)($_POST['balanco_feito'] ?? 0);
}

$rawQtd = $_POST['quantidade_retirada'] ?? null;
if (is_string($rawQtd) && trim($rawQtd) === '') $rawQtd = null;

if ($sem_estoque === 1) {
    $qtdEntregue = 0;
} else {
    $qtdEntregue = int_nonneg($rawQtd ?? -1);
}

$u = auth_user() ?? [];
$responsavel_estoque = trim((string)($_POST['responsavel_estoque'] ?? ''));
if ($responsavel_estoque === '') {
    $responsavel_estoque = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
}

if ($qtdEntregue < 0) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['reason' => 'invalid_qtd', 'qtdEntregue' => $qtdEntregue, 'competencia' => $competencia],
        $before,
        null,
        false,
        'invalid_qtd',
        "Falha ao finalizar retirada #{$id} (quantidade entregue inválida)."
    );
    http_response_code(400);
    exit('Quantidade entregue inválida.');
}

if ($responsavel_estoque === '') {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['reason' => 'missing_responsavel', 'competencia' => $competencia],
        $before,
        null,
        false,
        'missing_responsavel',
        "Falha ao finalizar retirada #{$id} (responsável obrigatório)."
    );
    http_response_code(400);
    exit('Responsável do estoque é obrigatório.');
}

// falta_estoque
$falta_estoque = 0;
if ($qtdSolicitada > 0 && $qtdEntregue < $qtdSolicitada) $falta_estoque = 1;
if ($sem_estoque === 1) $falta_estoque = 1;

// ✅ OPÇÃO A: sem_estoque NÃO FINALIZA
if ($sem_estoque === 1) {
    $statusNovo = 'pedido';
    $dataFinalSql = "NULL";
    $qtdEntregue = 0;
    $precisa_balanco = 1; // consistência: sem estoque => precisa balanço
} else {
    $statusNovo = 'finalizado';
    $dataFinalSql = "NOW()";
}

$update = $pdo->prepare("
    UPDATE retiradas
    SET
        quantidade_retirada = ?,
        responsavel_estoque = ?,
        precisa_balanco = ?,
        sem_estoque = ?,
        falta_estoque = ?,
        status = ?,
        finalizado_por = ?,
        data_finalizacao = {$dataFinalSql}
    WHERE id = ? AND deleted_at IS NULL
");

$ok = $update->execute([
    $qtdEntregue,
    $responsavel_estoque,
    $precisa_balanco ? 1 : 0,
    $sem_estoque ? 1 : 0,
    $falta_estoque ? 1 : 0,
    $statusNovo,
    'normal',
    $id
]);

if (!$ok) {
    audit_log(
        $pdo,
        'finalize',
        'retirada',
        $id,
        ['reason' => 'db_error', 'competencia' => $competencia],
        $before,
        null,
        false,
        'db_error',
        "Falha ao finalizar retirada #{$id} (erro banco)."
    );
    http_response_code(500);
    exit('Erro ao finalizar.');
}

csrf_rotate('finalizar_pedido');

// AFTER para log
$stmt2 = $pdo->prepare("
    SELECT
        id, competencia, status,
        quantidade_retirada, responsavel_estoque,
        precisa_balanco, sem_estoque, falta_estoque,
        data_finalizacao
    FROM retiradas
    WHERE id = ? LIMIT 1
");
$stmt2->execute([$id]);
$after = $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;

audit_log(
    $pdo,
    'finalize',
    'retirada',
    $id,
    [
        'competencia' => $competencia,
        'qtd_solicitada' => $qtdSolicitada,
        'qtd_entregue' => $qtdEntregue,
        'sem_estoque' => $sem_estoque ? 1 : 0,
        'precisa_balanco' => $precisa_balanco ? 1 : 0,
        'responsavel_estoque' => $responsavel_estoque,
    ],
    [
        'status' => (string)($before['status'] ?? ''),
        'quantidade_retirada' => $before['quantidade_retirada'] ?? null,
        'responsavel_estoque' => $before['responsavel_estoque'] ?? null,
        'precisa_balanco' => (int)($before['precisa_balanco'] ?? 0),
        'sem_estoque' => (int)($before['sem_estoque'] ?? 0),
        'falta_estoque' => (int)($before['falta_estoque'] ?? 0),
    ],
    $after ? [
        'status' => (string)($after['status'] ?? ''),
        'quantidade_retirada' => $after['quantidade_retirada'] ?? null,
        'responsavel_estoque' => $after['responsavel_estoque'] ?? null,
        'precisa_balanco' => (int)($after['precisa_balanco'] ?? 0),
        'sem_estoque' => (int)($after['sem_estoque'] ?? 0),
        'falta_estoque' => (int)($after['falta_estoque'] ?? 0),
        'data_finalizacao' => (string)($after['data_finalizacao'] ?? ''),
    ] : null,
    true,
    null,
    "Finalizou retirada #{$id} ({$competencia})."
);

// ✅ calcula o próximo
$openNextId = null;

if ($wantNext) {

    // 1) se o JS mandou um próximo alvo, valida ele (garante sem_estoque=0)
    if ($nextTargetId > 0) {
        $chk = $pdo->prepare("
            SELECT id
            FROM retiradas
            WHERE id = ?
              AND competencia = ?
              AND deleted_at IS NULL
              AND status <> 'finalizado'
              AND COALESCE(sem_estoque,0) = 0
            LIMIT 1
        ");
        $chk->execute([$nextTargetId, $competencia]);
        $okId = $chk->fetchColumn();
        if ($okId) $openNextId = (int)$okId;
    }

    // 2) se não tiver alvo válido, pega o próximo pendente GLOBAL (independente da página)
    if (!$openNextId) {
        $nextStmt = $pdo->prepare("
            SELECT id
            FROM retiradas
            WHERE competencia = ?
              AND deleted_at IS NULL
              AND status <> 'finalizado'
              AND COALESCE(sem_estoque,0) = 0
            ORDER BY data_pedido ASC, id ASC
            LIMIT 1
        ");
        $nextStmt->execute([$competencia]);
        $openNextId = $nextStmt->fetchColumn();
        $openNextId = $openNextId ? (int)$openNextId : null;
    }
}

$params = [
    'competencia' => $competencia,
    'highlight_id' => $id,
];

// ✅ Se clicou em "Próximo"
if ($wantNext) {

    // Achou próximo → manda abrir
    if ($openNextId) {
        $params['toast'] = 'finalizado';
        $params['open_finalizar_id'] = $openNextId;
    } else {
        $params['toast'] = 'acabou_pendentes';
        $params['no_more_pendentes'] = 1;
    }
} else {
    // Finalizar normal (sem próximo)
    $params['toast'] = 'finalizado';
}

redirect_back_with_params('../index.php', $params);
exit;
