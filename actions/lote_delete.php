<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    http_response_code(403);
    exit('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_delete')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

$pdo->beginTransaction();

try {
    // 1) Apaga itens do lote
    $st = $pdo->prepare("DELETE FROM lote_itens WHERE lote_id = ?");
    $st->execute([$loteId]);

    // 2) Apaga recebimentos do lote
    $st = $pdo->prepare("DELETE FROM lote_recebimentos WHERE lote_id = ?");
    $st->execute([$loteId]);

    // 3) Apaga o lote
    $st = $pdo->prepare("DELETE FROM lotes WHERE id = ?");
    $st->execute([$loteId]);

    $pdo->commit();

    header('Location: ../lotes.php?toast=' . urlencode('Lote excluído (itens e recebimentos removidos).'));
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    header('Location: ../lotes.php?toast=' . urlencode('Erro ao excluir lote.'));
    exit;
}
