<?php
// actions/lote_item_excluir.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate('lote_item_excluir', $_POST['csrf_token'] ?? '')) {
    header('Location: ../lotes.php?toast=' . urlencode('Sessão expirada. Tente novamente.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$id     = (int)($_POST['id'] ?? 0);

if ($loteId <= 0 || $id <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Dados inválidos.'));
    exit;
}

// bloqueia edição se lote fechado
$stmt = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmt->execute([$loteId]);
$st = (string)$stmt->fetchColumn();
if ($st === 'fechado') {
    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Lote fechado. Edição bloqueada.'));
    exit;
}

$stmtD = $pdo->prepare("DELETE FROM lote_itens WHERE id = ? AND lote_id = ? LIMIT 1");
$stmtD->execute([$id, $loteId]);

header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Item removido.'));
exit;
