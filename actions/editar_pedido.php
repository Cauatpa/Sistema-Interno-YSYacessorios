<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/competencia.php';

auth_session_start();
auth_require_role('admin');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'editar_pedido')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('editar_pedido');

$id = int_pos($_POST['id'] ?? 0);
$produto = trim((string)($_POST['produto'] ?? ''));
$tipo = one_of(trim((string)($_POST['tipo'] ?? '')), ['prata', 'ouro'], '');
$qtdSolic = int_pos($_POST['quantidade_solicitada'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}
if ($produto === '') {
    http_response_code(400);
    exit('Produto inválido.');
}
if ($tipo === '') {
    http_response_code(400);
    exit('Tipo inválido.');
}
if ($qtdSolic <= 0) {
    http_response_code(400);
    exit('Quantidade inválida.');
}

// Busca registro
$stmt = $pdo->prepare("
    SELECT id, competencia, status, quantidade_retirada
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
$status = (string)$row['status'];
$qtdRetirada = isset($row['quantidade_retirada']) ? (int)$row['quantidade_retirada'] : null;

if (!competencia_valida($competencia)) {
    http_response_code(500);
    exit('Competência inválida no registro.');
}

// ✅ Admin pode editar mesmo com mês fechado (conforme sua regra)
// Se você quiser obrigar reabrir antes de editar, é só bloquear aqui:
// if (mes_esta_fechado($pdo, $competencia)) { exit('...'); }

// Recalcula flags se já estiver finalizado e existir quantidade_retirada
$precisa_balanco = null;
$sem_estoque = null;

if ($status === 'finalizado' && $qtdRetirada !== null) {
    if ($qtdRetirada < $qtdSolic) {
        $precisa_balanco = 1;
        $sem_estoque = 1;
    } else {
        // se bateu a quantidade, zera flags “automáticas”
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
    http_response_code(500);
    exit('Erro ao salvar edição.');
}

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'editado',
    'highlight_id' => $id
]);
