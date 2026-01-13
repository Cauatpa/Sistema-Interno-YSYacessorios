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
    audit_log(
        $pdo,
        'edit',
        'retirada',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao editar pedido (CSRF inválido).'
    );
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('editar_pedido');

$id      = int_pos($_POST['id'] ?? 0);
$produto = trim((string)($_POST['produto'] ?? ''));
$tipo    = one_of(trim((string)($_POST['tipo'] ?? '')), ['prata', 'ouro'], '');
$qtdSolic = int_pos($_POST['quantidade_solicitada'] ?? 0);

if ($id <= 0 || $produto === '' || $tipo === '' || $qtdSolic <= 0) {
    audit_log(
        $pdo,
        'edit',
        'retirada',
        $id > 0 ? $id : null,
        [
            'reason' => 'validation_error',
            'produto' => $produto,
            'tipo' => $tipo,
            'quantidade_solicitada' => $qtdSolic,
        ],
        null,
        null,
        false,
        'validation_error',
        "Falha ao editar pedido #{$id} (validação)."
    );
    http_response_code(400);
    exit('Dados inválidos.');
}

/**
 * BEFORE (puxa só o necessário para diff e regras)
 */
$stmt = $pdo->prepare("
    SELECT
        id, competencia, status,
        produto, tipo, quantidade_solicitada,
        quantidade_retirada, precisa_balanco, sem_estoque
    FROM retiradas
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$beforeRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$beforeRow) {
    audit_log(
        $pdo,
        'edit',
        'retirada',
        $id,
        ['reason' => 'not_found'],
        null,
        null,
        false,
        'not_found',
        "Falha ao editar pedido #{$id} (não encontrado)."
    );
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$competencia = (string)($beforeRow['competencia'] ?? '');
$status      = (string)($beforeRow['status'] ?? '');
$qtdRetirada = isset($beforeRow['quantidade_retirada']) ? (int)$beforeRow['quantidade_retirada'] : null;

if (!competencia_valida($competencia)) {
    audit_log(
        $pdo,
        'edit',
        'retirada',
        $id,
        ['reason' => 'invalid_competencia', 'competencia' => $competencia],
        ['competencia' => $competencia],
        null,
        false,
        'invalid_competencia',
        "Falha ao editar pedido #{$id} (competência inválida)."
    );
    http_response_code(500);
    exit('Competência inválida no registro.');
}

/**
 * Regras: admin edita mesmo mês fechado (mantido).
 * Se estiver finalizado, recalcula flags com base no qtdRetirada vs qtdSolic.
 */
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

/**
 * UPDATE
 */
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
    audit_log(
        $pdo,
        'edit',
        'retirada',
        $id,
        ['reason' => 'db_error', 'competencia' => $competencia],
        ['id' => $id],
        null,
        false,
        'db_error',
        "Falha ao editar pedido #{$id} (erro banco)."
    );
    http_response_code(500);
    exit('Erro ao salvar edição.');
}

/**
 * AFTER (monta só o que importa; sem SELECT * gigante)
 */
$afterRow = [
    'produto' => $produto,
    'tipo' => $tipo,
    'quantidade_solicitada' => $qtdSolic,
];

if ($precisa_balanco !== null) {
    $afterRow['precisa_balanco'] = $precisa_balanco;
    $afterRow['sem_estoque'] = $sem_estoque;
}

/**
 * Diff só do que mudou
 */
$beforeForDiff = [
    'produto' => (string)($beforeRow['produto'] ?? ''),
    'tipo' => (string)($beforeRow['tipo'] ?? ''),
    'quantidade_solicitada' => (int)($beforeRow['quantidade_solicitada'] ?? 0),
    'precisa_balanco' => (int)($beforeRow['precisa_balanco'] ?? 0),
    'sem_estoque' => (int)($beforeRow['sem_estoque'] ?? 0),
];

$afterForDiff = [
    'produto' => $produto,
    'tipo' => $tipo,
    'quantidade_solicitada' => $qtdSolic,
    'precisa_balanco' => $precisa_balanco !== null ? (int)$precisa_balanco : (int)($beforeRow['precisa_balanco'] ?? 0),
    'sem_estoque' => $sem_estoque !== null ? (int)$sem_estoque : (int)($beforeRow['sem_estoque'] ?? 0),
];

[$beforeDiff, $afterDiff] = audit_diff(
    $beforeForDiff,
    $afterForDiff,
    ['produto', 'tipo', 'quantidade_solicitada', 'precisa_balanco', 'sem_estoque']
);

audit_log(
    $pdo,
    'edit',
    'retirada',
    $id,
    [
        'competencia' => $competencia,
        'reason' => null,
    ],
    $beforeDiff,
    $afterDiff,
    true,
    null,
    "Editou pedido #{$id} ({$competencia})."
);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'editado',
    'highlight_id' => $id
]);
