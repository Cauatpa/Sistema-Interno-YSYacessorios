<?php
// actions/lote_marcar_conferido.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate('lote_marcar_conferido', $_POST['csrf_token'] ?? '')) {
    header('Location: ../lotes.php?toast=' . urlencode('Sessão expirada. Tente novamente.'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

// se já estiver fechado, não mexe
$stmt = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$st = (string)$stmt->fetchColumn();

if ($st === 'fechado') {
    header('Location: ../lote.php?id=' . $id . '&toast=' . urlencode('Lote fechado.'));
    exit;
}

$stmtU = $pdo->prepare("UPDATE lotes SET status='conferido' WHERE id = ? LIMIT 1");
$stmtU->execute([$id]);

header('Location: ../lote.php?id=' . $id . '&toast=' . urlencode('Lote marcado como CONFERIDO!'));
exit;
