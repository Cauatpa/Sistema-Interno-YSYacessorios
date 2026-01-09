<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';

auth_require_role('admin');

post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'excluir_pedido')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('excluir_pedido');

$id = int_pos($_POST['id'] ?? 0);
$confirm = trim((string)($_POST['confirm'] ?? ''));

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}

if ($confirm !== "EXCLUIR {$id}") {
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: EXCLUIR {$id}");
}

// Buscar competência
$stmt = $pdo->prepare("
    SELECT competencia
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

// Bloqueio mês fechado
if (mes_esta_fechado($pdo, $competencia)) {
    http_response_code(403);
    exit("Não é possível excluir pedido de mês fechado ({$competencia}).");
}

// Soft delete
$upd = $pdo->prepare("
    UPDATE retiradas
    SET deleted_at = NOW()
    WHERE id = ? AND deleted_at IS NULL
");
$upd->execute([$id]);

require_once __DIR__ . '/../helpers/audit.php';

audit_log($pdo, 'delete', 'retirada', $id, [
    'competencia' => $competencia
]);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'excluido',
    'highlight_id' => $id
]);
