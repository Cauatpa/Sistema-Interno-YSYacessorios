<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';

auth_session_start();
auth_require_role('operador');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'finalizar_pedido')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = int_pos($_POST['id'] ?? 0);
$wantNext = (int)($_POST['next'] ?? 0) === 1;

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

// Busca o pedido (agora pega quantidade_solicitada)
$stmt = $pdo->prepare("
    SELECT id, competencia, status, quantidade_solicitada
    FROM retiradas
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$competencia = (string)$row['competencia'];
if (!competencia_valida($competencia)) {
    http_response_code(500);
    exit('Competência inválida no registro.');
}

if (mes_esta_fechado($pdo, $competencia)) {
    http_response_code(403);
    exit("Não é possível finalizar em mês fechado ({$competencia}).");
}

if ((string)$row['status'] === 'finalizado') {
    redirect_with_query('../index.php', [
        'competencia' => $competencia,
        'toast' => 'ja_finalizado',
        'highlight_id' => $id
    ]);
    exit;
}

$qtdSolicitada = (int)($row['quantidade_solicitada'] ?? 0);

$precisa_balanco = (int)($_POST['precisa_balanco'] ?? 0);
$sem_estoque = (int)($_POST['sem_estoque'] ?? 0);

$rawQtd = $_POST['quantidade_retirada'] ?? null;
if (is_string($rawQtd) && trim($rawQtd) === '') {
    $rawQtd = null;
}

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
    http_response_code(400);
    exit('Quantidade entregue inválida.');
}
if ($responsavel_estoque === '') {
    http_response_code(400);
    exit('Responsável do estoque é obrigatório.');
}

// falta_estoque: entregou menos que solicitado OU sem estoque marcado
$falta_estoque = 0;
if ($qtdSolicitada > 0 && $qtdEntregue < $qtdSolicitada) {
    $falta_estoque = 1;
}
if ($sem_estoque === 1) {
    $falta_estoque = 1;
}

// Finaliza
$update = $pdo->prepare("
    UPDATE retiradas
    SET
        quantidade_retirada = ?,
        responsavel_estoque = ?,
        precisa_balanco = ?,
        sem_estoque = ?,
        falta_estoque = ?,
        status = 'finalizado',
        data_finalizacao = NOW()
    WHERE id = ? AND deleted_at IS NULL
");
$ok = $update->execute([
    $qtdEntregue,
    $responsavel_estoque,
    $precisa_balanco ? 1 : 0,
    $sem_estoque ? 1 : 0,
    $falta_estoque ? 1 : 0,
    $id
]);

if (!$ok) {
    http_response_code(500);
    exit('Erro ao finalizar.');
}

csrf_rotate('finalizar_pedido');

// Se pediu "próximo", busca o próximo pendente do mesmo mês
$openNextId = null;
if ($wantNext) {
    $nextStmt = $pdo->prepare("
        SELECT id
        FROM retiradas
        WHERE competencia = ?
          AND deleted_at IS NULL
          AND status <> 'finalizado'
          AND id <> ?
        ORDER BY data_pedido ASC, id ASC
        LIMIT 1
    ");
    $nextStmt->execute([$competencia, $id]);
    $openNextId = $nextStmt->fetchColumn();
    $openNextId = $openNextId ? (int)$openNextId : null;
}

$params = [
    'competencia' => $competencia,
    'toast' => 'finalizado',
    'highlight_id' => $id,
];

if ($openNextId) {
    $params['open_finalizar_id'] = $openNextId;
}

redirect_with_query('../index.php', $params);
exit;
