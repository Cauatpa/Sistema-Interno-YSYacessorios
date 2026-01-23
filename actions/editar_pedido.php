<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/return_redirect.php';

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

$id          = int_pos($_POST['id'] ?? 0);
$produto     = trim((string)($_POST['produto'] ?? ''));
$tipo        = one_of(trim((string)($_POST['tipo'] ?? '')), ['prata', 'ouro'], '');
$qtdSolic    = int_pos($_POST['quantidade_solicitada'] ?? 0);
$solicitante = trim((string)($_POST['solicitante'] ?? ''));

// flags do modal
$precisa_balanco = ((int)($_POST['precisa_balanco'] ?? 0) === 1) ? 1 : 0;
$sem_estoque     = ((int)($_POST['sem_estoque'] ?? 0) === 1) ? 1 : 0;

// consistência: sem estoque => balanço
if ($sem_estoque === 1) $precisa_balanco = 1;

// ✅ REGRA NOVA: se tirou "precisa_balanco", destrava/reset "balanco_feito"
$resetBalancoFeito = false;
if ($precisa_balanco === 0) {
    $resetBalancoFeito = true;
}

if ($id <= 0 || $produto === '' || $tipo === '' || $qtdSolic <= 0 || $solicitante === '') {
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
            'solicitante' => $solicitante,
            'precisa_balanco' => $precisa_balanco,
            'sem_estoque' => $sem_estoque,
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
 * BEFORE (puxa o necessário)
 */
$stmt = $pdo->prepare("
    SELECT
        id, competencia, status,
        produto, tipo, quantidade_solicitada, solicitante,
        quantidade_retirada, precisa_balanco, sem_estoque, falta_estoque,
        balanco_feito, balanco_feito_em
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

$isFinalizado = ($status === 'finalizado');

/**
 * Quantidade entregue: só pode editar se finalizado (Opção A)
 */
$newQtdRet = null;

if ($isFinalizado && array_key_exists('quantidade_retirada', $_POST)) {
    $raw = $_POST['quantidade_retirada'];

    // vazio = não altera
    if (is_string($raw) && trim($raw) === '') {
        $newQtdRet = null;
    } else {
        $newQtdRet = int_nonneg($raw);
    }
}

// Se finalizado e sem_estoque=1 => quantidade_retirada obrigatoriamente 0
if ($isFinalizado && $sem_estoque === 1) {
    $newQtdRet = 0;
}

/**
 * Regra falta_estoque (só faz sentido se finalizado)
 */
$falta_estoque = (int)($beforeRow['falta_estoque'] ?? 0);

$beforeQtdRet  = array_key_exists('quantidade_retirada', $beforeRow) && $beforeRow['quantidade_retirada'] !== null
    ? (int)$beforeRow['quantidade_retirada']
    : null;

$effectiveQtdRet = $beforeQtdRet;
if ($isFinalizado && $newQtdRet !== null) {
    $effectiveQtdRet = $newQtdRet;
}

if ($isFinalizado && $effectiveQtdRet !== null) {
    $falta_estoque = 0;
    if ($sem_estoque === 1) {
        $falta_estoque = 1;
    } else {
        if ($effectiveQtdRet < $qtdSolic) $falta_estoque = 1;
    }
}

/**
 * UPDATE dinâmico (inclui qtd_retirada/falta_estoque somente se finalizado e enviado)
 */
$fields = [
    'produto = ?',
    'tipo = ?',
    'quantidade_solicitada = ?',
    'solicitante = ?',
    'precisa_balanco = ?',
    'sem_estoque = ?',
];
$params = [$produto, $tipo, $qtdSolic, $solicitante, $precisa_balanco, $sem_estoque];

// ✅ se tirou precisa_balanco, resetar balanco_feito e data
if ($resetBalancoFeito) {
    $fields[] = 'balanco_feito = 0';
    $fields[] = 'balanco_feito_em = NULL';
}

if ($isFinalizado && $newQtdRet !== null) {
    $fields[] = 'quantidade_retirada = ?';
    $params[] = $newQtdRet;

    $fields[] = 'falta_estoque = ?';
    $params[] = $falta_estoque;
}

$params[] = $id;

$sql = "UPDATE retiradas SET " . implode(', ', $fields) . " WHERE id = ? AND deleted_at IS NULL";
$upd = $pdo->prepare($sql);
$ok = $upd->execute($params);

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
 * Diff (inclui campos novos pra auditoria)
 */
$beforeForDiff = [
    'produto' => (string)($beforeRow['produto'] ?? ''),
    'tipo' => (string)($beforeRow['tipo'] ?? ''),
    'quantidade_solicitada' => (int)($beforeRow['quantidade_solicitada'] ?? 0),
    'solicitante' => (string)($beforeRow['solicitante'] ?? ''),
    'precisa_balanco' => (int)($beforeRow['precisa_balanco'] ?? 0),
    'sem_estoque' => (int)($beforeRow['sem_estoque'] ?? 0),
    'quantidade_retirada' => $beforeQtdRet,
    'falta_estoque' => (int)($beforeRow['falta_estoque'] ?? 0),
    'balanco_feito' => (int)($beforeRow['balanco_feito'] ?? 0),
    'balanco_feito_em' => $beforeRow['balanco_feito_em'] ?? null,
];

$afterForDiff = [
    'produto' => $produto,
    'tipo' => $tipo,
    'quantidade_solicitada' => $qtdSolic,
    'solicitante' => $solicitante,
    'precisa_balanco' => (int)$precisa_balanco,
    'sem_estoque' => (int)$sem_estoque,
    'quantidade_retirada' => ($isFinalizado && $newQtdRet !== null) ? (int)$newQtdRet : $beforeForDiff['quantidade_retirada'],
    'falta_estoque' => ($isFinalizado && $newQtdRet !== null) ? (int)$falta_estoque : (int)$beforeForDiff['falta_estoque'],
    'balanco_feito' => $resetBalancoFeito ? 0 : (int)$beforeForDiff['balanco_feito'],
    'balanco_feito_em' => $resetBalancoFeito ? null : $beforeForDiff['balanco_feito_em'],
];

$keys = ['produto', 'tipo', 'quantidade_solicitada', 'solicitante', 'precisa_balanco', 'sem_estoque', 'balanco_feito', 'balanco_feito_em'];
if ($isFinalizado) {
    $keys[] = 'quantidade_retirada';
    $keys[] = 'falta_estoque';
}

[$beforeDiff, $afterDiff] = audit_diff($beforeForDiff, $afterForDiff, $keys);

audit_log(
    $pdo,
    'edit',
    'retirada',
    $id,
    ['competencia' => $competencia, 'reason' => null],
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
