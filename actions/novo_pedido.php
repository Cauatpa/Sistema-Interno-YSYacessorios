<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('operador');

post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'novo_pedido')) {
    // loga falha (opcional, mas útil)
    audit_log($pdo, 'create', 'retirada', null, [
        'reason' => 'csrf_invalid'
    ], null, null, false, 'csrf_invalid', 'CSRF inválido.', 'Falha ao criar retirada (CSRF inválido).');

    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('novo_pedido');

require_fields($_POST, ['produto', 'quantidade_solicitada', 'tipo']);

$produto = trim((string)$_POST['produto']);
$quantidade = int_pos($_POST['quantidade_solicitada'] ?? 0);
$tipo = one_of(trim((string)$_POST['tipo']), ['prata', 'ouro'], '');

if ($produto === '' || $quantidade <= 0 || $tipo === '') {
    audit_log($pdo, 'create', 'retirada', null, [
        'produto' => $produto,
        'quantidade_solicitada' => $quantidade,
        'tipo' => $tipo,
        'reason' => 'validation_error'
    ], null, null, false, 'validation_error', 'Campos inválidos.', 'Falha ao criar retirada (validação).');

    http_response_code(400);
    exit('Dados inválidos.');
}

// solicitante pelo usuário logado
$u = auth_user();
$solicitante = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($solicitante === '') {
    audit_log($pdo, 'create', 'retirada', null, [
        'reason' => 'unauthenticated'
    ], null, null, false, 'unauthenticated', 'Usuário não autenticado.', 'Falha ao criar retirada (sem login).');

    http_response_code(401);
    exit('Usuário não autenticado.');
}

$competencia = competencia_from_datetime(date('Y-m-d H:i:s'));
if (!competencia_valida($competencia)) {
    audit_log($pdo, 'create', 'retirada', null, [
        'competencia' => $competencia,
        'reason' => 'invalid_competencia'
    ], null, null, false, 'invalid_competencia', 'Competência inválida.', 'Falha ao criar retirada (competência inválida).');

    http_response_code(500);
    exit('Competência inválida gerada.');
}

if (mes_esta_fechado($pdo, $competencia)) {
    audit_log($pdo, 'create', 'retirada', null, [
        'competencia' => $competencia,
        'reason' => 'month_closed'
    ], null, null, false, 'month_closed', 'Mês fechado.', "Falha ao criar retirada (mês fechado {$competencia}).");

    http_response_code(403);
    exit("Não é possível criar retirada em mês fechado ({$competencia}).");
}

// INSERT
$sql = "
    INSERT INTO retiradas
        (produto, quantidade_solicitada, tipo, solicitante, status, data_pedido, competencia)
    VALUES
        (?, ?, ?, ?, 'pedido', NOW(), ?)
";
$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$produto, $quantidade, $tipo, $solicitante, $competencia]);

if (!$ok) {
    audit_log($pdo, 'create', 'retirada', null, [
        'produto' => $produto,
        'quantidade_solicitada' => $quantidade,
        'tipo' => $tipo,
        'solicitante' => $solicitante,
        'competencia' => $competencia,
        'reason' => 'db_error'
    ], null, null, false, 'db_error', 'Falha ao inserir no banco.', 'Falha ao criar retirada (erro banco).');

    http_response_code(500);
    exit('Erro ao salvar pedido.');
}

$newId = (int)$pdo->lastInsertId();

// after (estado final)
$after = [
    'id' => $newId,
    'produto' => $produto,
    'quantidade_solicitada' => $quantidade,
    'tipo' => $tipo,
    'solicitante' => $solicitante,
    'status' => 'pedido',
    'competencia' => $competencia,
];

// log sucesso
audit_log(
    $pdo,
    'create',
    'retirada',
    $newId,
    [
        'competencia' => $competencia
    ],
    null,
    $after,
    true,
    null,
    null,
    "Criou retirada #{$newId} ({$tipo}) {$produto} x{$quantidade}."
);

redirect_with_query('../index.php', ['competencia' => $competencia]);
