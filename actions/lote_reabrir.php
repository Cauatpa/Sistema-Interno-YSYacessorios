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

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_reabrir')) {
    die('CSRF inválido.');
}

$loteId = (int)($_POST['lote_id'] ?? 0);
if ($loteId <= 0) {
    die('Lote inválido.');
}

$stmt = $pdo->prepare("
    UPDATE lotes
    SET status = 'aberto'
    WHERE id = ?
");
$stmt->execute([$loteId]);

header('Location: ../lotes.php?toast=' . urlencode('Lote reaberto.'));
exit;
