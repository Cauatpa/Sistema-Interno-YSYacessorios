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

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_delete_group')) {
    $loteId = (int)($_POST['lote_id'] ?? 0);
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId  = (int)($_POST['lote_id'] ?? 0);
$idPrata = (int)($_POST['id_prata'] ?? 0);
$idOuro  = (int)($_POST['id_ouro'] ?? 0);

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

$ids = [];
if ($idPrata > 0) $ids[] = $idPrata;
if ($idOuro  > 0) $ids[] = $idOuro;

if (!$ids) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Nada para excluir.'));
    exit;
}

$place = implode(',', array_fill(0, count($ids), '?'));

// Segurança: só apaga se pertencer ao lote
$sql = "DELETE FROM lote_itens WHERE lote_id = ? AND id IN ($place)";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$loteId], $ids));

header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Item excluído!'));
exit;
