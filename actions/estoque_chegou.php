<?php
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

$upd = $pdo->prepare("
    UPDATE retiradas
    SET
        sem_estoque = 0,
        estoque_preenchido = 1,
        estoque_preenchido_em = NOW(),

        -- não finaliza aqui!
        status = 'pedido',
        data_finalizacao = NULL

        -- não mexe em quantidade_retirada / falta_estoque
        -- não mexe em quantidade_retirada = quantidade_solicitada
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
    ['status' => 'pedido', 'sem_estoque' => 1, 'finalizado_por' => (string)($r['finalizado_por'] ?? 'normal')],
    ['status' => 'finalizado', 'sem_estoque' => 0, 'finalizado_por' => 'estoque'],
    true,
    null,
    "Estoque chegou e pedido foi finalizado (#{$id})."
);

redirect_back_with_params($return, [
    'toast' => 'estoque_chegou',
    'highlight_id' => $id
]);
exit;
