<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/return_redirect.php';

auth_session_start();
auth_require_role('admin'); // ajuste se quiser permitir operador
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'estoque_chegou')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('estoque_chegou');

$id = int_pos($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

// ✅ usar return pra voltar pra /InterYSY certinho
$return = (string)($_POST['return'] ?? '/InterYSY/index.php');

$stmt = $pdo->prepare("
    SELECT
        id,
        competencia,
        status,
        sem_estoque,
        quantidade_solicitada,
        quantidade_retirada,
        finalizado_por
    FROM retiradas
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

if (($r['status'] ?? '') === 'finalizado') {
    redirect_back_with_params($return, [
        'toast' => 'ja_finalizado',
        'highlight_id' => $id
    ]);
    exit;
}

if ((int)($r['sem_estoque'] ?? 0) !== 1) {
    redirect_back_with_params($return, [
        'toast' => 'nao_sem_estoque',
        'highlight_id' => $id
    ]);
    exit;
}

/**
 * ✅ Regra: estoque chegou / estoque preenchido
 * - vira FINALIZADO (pra não entrar em pendentes)
 * - sem_estoque vira 0
 * - entrega NÃO muda (mantém 0 / mantém o que já estiver)
 * - grava finalizado_por='estoque' para consistência
 */
$upd = $pdo->prepare("
    UPDATE retiradas
    SET
        sem_estoque = 0,

        estoque_preenchido = 1,
        estoque_preenchido_em = NOW(),

        status = 'finalizado',
        data_finalizacao = NOW(),

        -- ✅ não altera entrega; se estiver NULL, garante 0
        quantidade_retirada = COALESCE(quantidade_retirada, 0),

        falta_estoque = 0,
        precisa_balanco = 0,
        balanco_feito = 0,
        balanco_feito_em = NULL,

        finalizado_por = 'estoque'
    WHERE id = ? AND deleted_at IS NULL
");
$ok = $upd->execute([$id]);

if (!$ok) {
    http_response_code(500);
    exit('Erro ao finalizar pedido.');
}

audit_log(
    $pdo,
    'estoque_chegou',
    'retirada',
    $id,
    ['competencia' => (string)($r['competencia'] ?? '')],
    [
        'status' => (string)($r['status'] ?? 'pedido'),
        'sem_estoque' => (int)($r['sem_estoque'] ?? 0),
        'finalizado_por' => (string)($r['finalizado_por'] ?? 'normal'),
        'quantidade_retirada' => $r['quantidade_retirada'] ?? null,
    ],
    [
        'status' => 'finalizado',
        'sem_estoque' => 0,
        'finalizado_por' => 'estoque',
        'quantidade_retirada' => (int)($r['quantidade_retirada'] ?? 0),
    ],
    true,
    null,
    "Estoque chegou e pedido foi finalizado como estoque preenchido (#{$id})."
);

redirect_back_with_params($return, [
    'toast' => 'estoque_chegou',
    'highlight_id' => $id,
    'filtro' => 'finalizados',   // card de cima
    'status' => 'todos',         // dropdown (não força)
    'sem_estoque' => 0,          // checkbox
    'balanco' => 0,              // checkbox
]);
exit;
