<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';

auth_session_start();
auth_require_role('operador'); // operador ou admin

post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'novo_pedido')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('novo_pedido');

// Campos obrigatórios (sem solicitante agora)
require_fields($_POST, ['produto', 'quantidade_solicitada', 'tipo']);

$produto = trim((string)$_POST['produto']);
$quantidade = int_pos($_POST['quantidade_solicitada'] ?? 0);
$tipo = one_of(trim((string)$_POST['tipo']), ['prata', 'ouro'], '');

if ($produto === '') {
    http_response_code(400);
    exit('Produto inválido.');
}
if ($quantidade <= 0) {
    http_response_code(400);
    exit('Quantidade inválida.');
}
if ($tipo === '') {
    http_response_code(400);
    exit('Tipo inválido.');
}

// ✅ solicitante vem do usuário logado
$u = $_SESSION['user'] ?? null;
$solicitante = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($solicitante === '') {
    http_response_code(401);
    exit('Usuário não autenticado.');
}

// Competência
$data_pedido = date('Y-m-d H:i:s');
$competencia = competencia_from_datetime($data_pedido);

if (!competencia_valida($competencia)) {
    http_response_code(500);
    exit('Competência inválida gerada.');
}

// Bloqueio mês fechado
if (mes_esta_fechado($pdo, $competencia)) {
    http_response_code(403);
    exit("Não é possível criar retirada em mês fechado ({$competencia}).");
}

$sql = "
    INSERT INTO retiradas
        (produto, quantidade_solicitada, tipo, solicitante, status, data_pedido, competencia)
    VALUES
        (?, ?, ?, ?, 'pedido', NOW(), ?)
";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$produto, $quantidade, $tipo, $solicitante, $competencia]);

if ($ok) {
    redirect_with_query('../index.php', ['competencia' => $competencia]);
}

http_response_code(500);
exit('Erro ao salvar pedido.');
