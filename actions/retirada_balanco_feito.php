<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

auth_session_start();
auth_require_login();

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

if (!csrf_validate($_POST['csrf_token'] ?? '', 'balanco_feito')) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'csrf_invalid']);
        exit;
    }
    header('Location: ../retiradas.php?toast=CSRF inválido');
    exit;
}

$sem_estoque = (int)($_POST['sem_estoque'] ?? 0);
$precisa_balanco = (int)($_POST['precisa_balanco'] ?? 0);
if ($sem_estoque === 1) {
    // não permite
    exit('Não é possível marcar balanço em pedido sem estoque.');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id_invalido']);
        exit;
    }
    header('Location: ../retiradas.php?toast=ID inválido');
    exit;
}

$stmt = $pdo->prepare("
    UPDATE retiradas
    SET balanco_feito = 1,
        balanco_feito_em = NOW()
    WHERE id = ?
      AND precisa_balanco = 1
      AND balanco_feito = 0
      AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);

$updated = $stmt->rowCount(); // 1 se marcou, 0 se não tinha o que marcar

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'updated' => $updated
    ]);
    exit;
}

header('Location: ../retiradas.php?toast=Balanço marcado como feito');
exit;
