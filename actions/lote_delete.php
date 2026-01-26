<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    // log tentativa sem permissão
    audit_log(
        $pdo,
        'delete',
        'lote',
        null,
        ['reason' => 'no_permission'],
        null,
        null,
        false,
        'no_permission',
        'Tentativa de excluir lote sem permissão.'
    );

    http_response_code(403);
    exit('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_delete')) {
    audit_log(
        $pdo,
        'delete',
        'lote',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao excluir lote (CSRF inválido).'
    );

    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
if ($loteId <= 0) {
    audit_log(
        $pdo,
        'delete',
        'lote',
        null,
        ['reason' => 'invalid_id', 'lote_id' => $loteId],
        null,
        null,
        false,
        'invalid_id',
        'Falha ao excluir lote (ID inválido).'
    );

    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

/**
 * BEFORE: pega dados do lote e contagens
 */
$stmt = $pdo->prepare("
    SELECT *
    FROM lotes
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$loteId]);
$beforeLote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$beforeLote) {
    audit_log(
        $pdo,
        'delete',
        'lote',
        $loteId,
        ['reason' => 'not_found', 'lote_id' => $loteId],
        null,
        null,
        false,
        'not_found',
        "Falha ao excluir lote #{$loteId} (não encontrado)."
    );

    header('Location: ../lotes.php?toast=' . urlencode('Lote não encontrado.'));
    exit;
}

// contagens antes
$stCntItens = $pdo->prepare("
    SELECT COUNT(*) FROM lote_itens
    WHERE lote_id = ? AND deleted_at IS NULL
");
$stCntItens->execute([$loteId]);
$cntItens = (int)$stCntItens->fetchColumn();

$stCntRec = $pdo->prepare("
    SELECT COUNT(*) FROM lote_recebimentos
    WHERE lote_id = ? AND deleted_at IS NULL
");
$stCntRec->execute([$loteId]);
$cntRec = (int)$stCntRec->fetchColumn();

$beforeForLog = [
    'lote' => $beforeLote,
    'counts' => [
        'itens_ativos' => $cntItens,
        'recebimentos_ativos' => $cntRec,
    ],
];

$pdo->beginTransaction();

try {
    // 1) Soft delete itens do lote
    $st = $pdo->prepare("
        UPDATE lote_itens
           SET deleted_at = NOW()
         WHERE lote_id = ?
           AND deleted_at IS NULL
    ");
    $st->execute([$loteId]);

    // 2) Soft delete recebimentos do lote
    $st = $pdo->prepare("
        UPDATE lote_recebimentos
           SET deleted_at = NOW()
         WHERE lote_id = ?
           AND deleted_at IS NULL
    ");
    $st->execute([$loteId]);

    // 3) Soft delete do lote
    $st = $pdo->prepare("
        UPDATE lotes
           SET deleted_at = NOW()
         WHERE id = ?
           AND deleted_at IS NULL
         LIMIT 1
    ");
    $st->execute([$loteId]);

    // AFTER: puxa o lote depois do soft delete
    $stmt = $pdo->prepare("SELECT * FROM lotes WHERE id = ? LIMIT 1");
    $stmt->execute([$loteId]);
    $afterLote = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // contagens depois (ativos devem virar 0)
    $stCntItens->execute([$loteId]);
    $cntItensAfter = (int)$stCntItens->fetchColumn();

    $stCntRec->execute([$loteId]);
    $cntRecAfter = (int)$stCntRec->fetchColumn();

    $afterForLog = [
        'lote' => $afterLote,
        'counts' => [
            'itens_ativos' => $cntItensAfter,
            'recebimentos_ativos' => $cntRecAfter,
        ],
    ];

    $pdo->commit();

    // auditoria sucesso
    audit_log(
        $pdo,
        'delete',
        'lote',
        $loteId,
        [
            'reason' => null,
            'lote_id' => $loteId,
            'tipo' => 'soft_delete',
        ],
        $beforeForLog,
        $afterForLog,
        true,
        null,
        "Excluiu lote #{$loteId} (soft delete)."
    );

    header('Location: ../lotes.php?toast=' . urlencode('Lote excluído.'));
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();

    audit_log(
        $pdo,
        'delete',
        'lote',
        $loteId,
        [
            'reason' => 'db_error',
            'lote_id' => $loteId,
            'tipo' => 'soft_delete',
            'error' => $e->getMessage(),
        ],
        $beforeForLog ?? null,
        null,
        false,
        'db_error',
        "Erro ao excluir lote #{$loteId} (banco)."
    );

    header('Location: ../lotes.php?toast=' . urlencode('Erro ao excluir lote.'));
    exit;
}
