<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('operador');

post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'finalizar_pedido')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('finalizar_pedido');

// Dados do modal
$id = int_pos($_POST['id'] ?? 0);
$quantidade_retirada = int_nonneg($_POST['quantidade_retirada'] ?? -1);

// checkboxes (normaliza)
$precisa_balanco = !empty($_POST['precisa_balanco']) ? 1 : 0;
$sem_estoque     = !empty($_POST['sem_estoque']) ? 1 : 0;

if ($id <= 0) {
    http_response_code(400);
    exit('ID inválido.');
}
if ($quantidade_retirada < 0) {
    http_response_code(400);
    exit('Quantidade retirada inválida.');
}

// ✅ responsável vem do usuário logado
$u = auth_user();
$responsavel_estoque = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($responsavel_estoque === '') {
    http_response_code(401);
    exit('Usuário não autenticado.');
}

// Buscar dados do pedido (inclui competência e quantidade solicitada)
$stmt = $pdo->prepare("
    SELECT quantidade_solicitada, competencia
    FROM retiradas
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([$id]);
$retirada = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$retirada) {
    http_response_code(404);
    exit('Retirada não encontrada.');
}

$competencia = (string)$retirada['competencia'];
$qtd_solicitada = (int)$retirada['quantidade_solicitada'];

if (!competencia_valida($competencia)) {
    http_response_code(500);
    exit('Competência inválida no registro.');
}

// Bloqueio mês fechado
if (mes_esta_fechado($pdo, $competencia)) {
    http_response_code(403);
    exit("Não é possível finalizar em mês fechado ({$competencia}).");
}

// Regras automáticas
// Obs: seu código diz: se retirou menos, marca precisa_balanco e sem_estoque.
// Mantive igual ao que você já definiu.
if ($quantidade_retirada < $qtd_solicitada) {
    $precisa_balanco = 1;
    $sem_estoque = 1;
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
    (int)$precisa_balanco,
    (int)$sem_estoque,
    $id
]);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'finalizado',
    'highlight_id' => $id
]);
