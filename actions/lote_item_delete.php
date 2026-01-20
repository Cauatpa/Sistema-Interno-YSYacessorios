<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_delete')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("DELETE FROM lote_itens WHERE id = ? AND lote_id = ? LIMIT 1");
$stmt->execute([$id, $loteId]);

header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Item excluído!'));
exit;
