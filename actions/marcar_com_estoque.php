<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('admin');

// POST only (sem helper)
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'marcar_com_estoque')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('marcar_com_estoque');

// ID (sem int_pos)
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

// Return url (fallback seguro)
$return = (string)($_POST['return'] ?? '');
if ($return === '') {
    $return = '../pages/retiradas_controller.php';
}

// Redirect helper (sem return_redirect)
function go(string $url, string $qs): void
{
    $sep = (str_contains($url, '?')) ? '&' : '?';
    header('Location: ' . $url . $sep . $qs);
    exit;
}

if ($id <= 0) {
    go($return, 'toast=invalid');
}

// Busca atual
$stmt = $pdo->prepare("
    SELECT id, status, sem_estoque, precisa_balanco
    FROM retiradas
    WHERE id = ?
      AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    go($return, 'toast=notfound');
}

if (empty($row['sem_estoque']) || (($row['status'] ?? '') === 'finalizado')) {
    go($return, 'toast=nao_permitido');
}

$pdo->beginTransaction();

try {
    $upd = $pdo->prepare("
        UPDATE retiradas
        SET
          sem_estoque = 0,
          status = 'finalizado',
          precisa_balanco = 0
        WHERE id = ?
          AND deleted_at IS NULL
          AND sem_estoque = 1
          AND status <> 'finalizado'
    ");
    $upd->execute([$id]);

    audit_log(
        $pdo,
        'update',
        'retirada',
        $id,
        ['action' => 'marcar_com_estoque'],
        $row,
        ['sem_estoque' => 0, 'status' => 'finalizado', 'precisa_balanco' => 0],
        true,
        null,
        'Pedido marcado como COM ESTOQUE e finalizado.'
    );

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    go($return, 'toast=erro');
}

go($return, 'toast=com_estoque_ok&highlight_id=' . $id);
