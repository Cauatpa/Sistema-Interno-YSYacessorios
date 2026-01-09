<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/auth.php';

auth_session_start();
auth_require_role('operador');

post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'finalizar_pedido')) {
    http_response_code(403);
    exit('CSRF inválido.');
}

// Dados do modal
$id = int_pos($_POST['id'] ?? 0);

$precisa_balanco = (int)($_POST['precisa_balanco'] ?? 0);
$sem_estoque = (int)($_POST['sem_estoque'] ?? 0);

// ✅ quantidade pode vir vazia quando sem estoque
$rawQtd = $_POST['quantidade_retirada'] ?? null;

// Se veio vazio (""), transforma em null
if (is_string($rawQtd) && trim($rawQtd) === '') {
    $rawQtd = null;
}

// Se sem estoque marcado e não veio quantidade, assume 0
if ($sem_estoque === 1 && $rawQtd === null) {
    $quantidade_retirada = 0;
} else {
    // Caso normal: precisa ser número >= 0
    $quantidade_retirada = int_nonneg($rawQtd ?? -1);
}

$responsavel_estoque = trim((string)($_POST['responsavel_estoque'] ?? ''));
$u = auth_user() ?? [];
if ($responsavel_estoque === '') {
    $responsavel_estoque = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
}

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}
if ($quantidade_retirada < 0) {
    http_response_code(400);
    exit('Quantidade retirada inválida.');
}
if ($responsavel_estoque === '') {
    http_response_code(400);
    exit('Responsável do estoque é obrigatório.');
}

// Atualizar
$update = $pdo->prepare("
    UPDATE retiradas
    SET
        quantidade_retirada = ?,
        responsavel_estoque = ?,
        precisa_balanco = ?,
        sem_estoque = ?,
        status = 'finalizado',
        data_finalizacao = NOW()
    WHERE id = ? AND deleted_at IS NULL
");

$update->execute([
    $quantidade_retirada,
    $responsavel_estoque,
    $precisa_balanco ? 1 : 0,
    $sem_estoque ? 1 : 0,
    $id
]);

// ✅ agora sim pode girar o token (depois de sucesso)
csrf_rotate('finalizar_pedido');

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'finalizado',
    'highlight_id' => $id
]);
