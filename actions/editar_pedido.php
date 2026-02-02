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

// flags do modal (1x só, sem duplicar)
$precisa_balanco = ((int)($_POST['precisa_balanco'] ?? 0) === 1) ? 1 : 0;
$sem_estoque     = ((int)($_POST['sem_estoque'] ?? 0) === 1) ? 1 : 0;
$estoque_chegou  = !empty($_POST['estoque_chegou']) ? 1 : 0;

$observacao = trim((string)($_POST['observacao'] ?? ''));

// consistência: sem estoque => balanço
if ($sem_estoque === 1) $precisa_balanco = 1;

// ✅ REGRA NOVA: se tirou "precisa_balanco", destrava/reset "balanco_feito"
$resetBalancoFeito = ($precisa_balanco === 0);

// validação básica
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
            'estoque_chegou' => $estoque_chegou,
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
        balanco_feito, balanco_feito_em,
        data_finalizacao
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

$competencia     = (string)($beforeRow['competencia'] ?? '');
$statusBanco     = (string)($beforeRow['status'] ?? '');
$semEstoqueBanco = (int)($beforeRow['sem_estoque'] ?? 0);

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

$isFinalizadoBanco = ($statusBanco === 'finalizado');

/**
 * ✅ Opção A - consistência
 * - sem_estoque=1 => status sempre "pedido" (nunca finalizado)
 * - estoque_chegou só pode se no BANCO estava sem estoque
 */
if ($estoque_chegou === 1 && $semEstoqueBanco !== 1) {
    // tentativa inválida: não estava marcado como sem estoque no banco
    $estoque_chegou = 0;
}

/**
 * Quantidade entregue: só pode editar se finalizado (mantém sua regra)
 */
$newQtdRet = null;

if ($isFinalizadoBanco && array_key_exists('quantidade_retirada', $_POST)) {
    $raw = $_POST['quantidade_retirada'];

    // vazio = não altera
    if (is_string($raw) && trim($raw) === '') {
        $newQtdRet = null;
    } else {
        $newQtdRet = int_nonneg($raw);
    }
}

/**
 * Regra falta_estoque (só faz sentido se finalizado)
 */
$falta_estoque = (int)($beforeRow['falta_estoque'] ?? 0);

$beforeQtdRet  = array_key_exists('quantidade_retirada', $beforeRow) && $beforeRow['quantidade_retirada'] !== null
    ? (int)$beforeRow['quantidade_retirada']
    : null;

$effectiveQtdRet = $beforeQtdRet;
if ($isFinalizadoBanco && $newQtdRet !== null) {
    $effectiveQtdRet = $newQtdRet;
}

if ($isFinalizadoBanco && $effectiveQtdRet !== null) {
    $falta_estoque = 0;
    // se finalizado e retirou menos que solicitado => falta_estoque
    if ($effectiveQtdRet < $qtdSolic) $falta_estoque = 1;
}

/**
 * UPDATE dinâmico
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

/**
 * ✅ Opção A - persistência no banco
 * Se sem_estoque=1 => status='pedido', data_finalizacao=NULL,
 * e zera quantidade_retirada/falta_estoque (pra não existir finalizado+sem_estoque)
 */
if ($sem_estoque === 1) {
    $fields[] = "status = 'pedido'";
    $fields[] = "data_finalizacao = NULL";
    $fields[] = "quantidade_retirada = 0";
    $fields[] = "falta_estoque = 1"; // se quer que "sem estoque" reflita como falta, deixa 1
}

/**
 * Se clicou "estoque chegou" e estava sem estoque no banco, finaliza de verdade
 */
if ($estoque_chegou === 1) {
    $fields[] = "status = 'finalizado'";
    $fields[] = "sem_estoque = 0";
    $fields[] = "data_finalizacao = NOW()";
    $fields[] = "quantidade_retirada = ?";
    $params[] = $qtdSolic;

    $fields[] = "falta_estoque = 0";

    // opcional: ao finalizar por estoque chegou, você pode limpar balanço:
    $fields[] = "precisa_balanco = 0";
    $fields[] = "balanco_feito = 0";
    $fields[] = "balanco_feito_em = NULL";
}

/**
 * Se é finalizado no banco e usuário mandou quantidade_retirada, mantém sua regra
 * (mas só aplica se NÃO estiver sem_estoque=1 e NÃO estiver estoque_chegou=1)
 */
if ($isFinalizadoBanco && $newQtdRet !== null && $sem_estoque === 0 && $estoque_chegou === 0) {
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
 * Diff (auditoria)
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
    'status' => $statusBanco,
    'data_finalizacao' => $beforeRow['data_finalizacao'] ?? null,
];

$afterForDiff = [
    'produto' => $produto,
    'tipo' => $tipo,
    'quantidade_solicitada' => $qtdSolic,
    'solicitante' => $solicitante,
    'precisa_balanco' => (int)$precisa_balanco,
    'sem_estoque' => (int)$sem_estoque,
    'quantidade_retirada' => $beforeForDiff['quantidade_retirada'],
    'falta_estoque' => $beforeForDiff['falta_estoque'],
    'balanco_feito' => $resetBalancoFeito ? 0 : (int)$beforeForDiff['balanco_feito'],
    'balanco_feito_em' => $resetBalancoFeito ? null : $beforeForDiff['balanco_feito_em'],
    'status' => $beforeForDiff['status'],
    'data_finalizacao' => $beforeForDiff['data_finalizacao'],
];

// tenta inferir status/data_finalizacao do pós-update sem requery
if ($estoque_chegou === 1) {
    $afterForDiff['status'] = 'finalizado';
    $afterForDiff['sem_estoque'] = 0;
    $afterForDiff['quantidade_retirada'] = $qtdSolic;
    $afterForDiff['falta_estoque'] = 0;
    $afterForDiff['data_finalizacao'] = 'NOW()';
} elseif ($sem_estoque === 1) {
    $afterForDiff['status'] = 'pedido';
    $afterForDiff['quantidade_retirada'] = 0;
    $afterForDiff['falta_estoque'] = 1;
    $afterForDiff['data_finalizacao'] = null;
} else {
    // status não mudou via regras, e qtd ret pode ter mudado se finalizado + editou
    if ($isFinalizadoBanco && $newQtdRet !== null && $estoque_chegou === 0) {
        $afterForDiff['quantidade_retirada'] = (int)$newQtdRet;
        $afterForDiff['falta_estoque'] = (int)$falta_estoque;
    }
}

$keys = [
    'produto',
    'tipo',
    'quantidade_solicitada',
    'solicitante',
    'precisa_balanco',
    'sem_estoque',
    'balanco_feito',
    'balanco_feito_em',
    'status',
    'data_finalizacao'
];

// se for finalizado no banco, inclui também os campos de qtd/falta
if ($isFinalizadoBanco || $estoque_chegou === 1 || $sem_estoque === 1) {
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

redirect_back_with_params('../index.php', [
    'toast' => 'editado',
    'highlight_id' => $id,
]);
