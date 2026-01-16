<?php

declare(strict_types=1);

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
    audit_log(
        $pdo,
        'create',
        'retirada',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'CSRF inválido.',
        'Falha ao criar retirada (CSRF inválido).'
    );
    http_response_code(403);
    exit('CSRF inválido.');
}

$wantNext = ((int)($_POST['next'] ?? 0) === 1);

// Campos obrigatórios
require_fields($_POST, ['produto', 'tipo', 'solicitante', 'quantidade_solicitada']);

$produto     = trim((string)($_POST['produto'] ?? ''));
$tipo        = one_of(trim((string)($_POST['tipo'] ?? '')), ['prata', 'ouro'], '');
$solicitante = trim((string)($_POST['solicitante'] ?? ''));
$quantidade  = int_pos($_POST['quantidade_solicitada'] ?? 0);

if ($produto === '' || $tipo === '' || $solicitante === '' || $quantidade <= 0) {
    audit_log(
        $pdo,
        'create',
        'retirada',
        null,
        [
            'produto' => $produto,
            'tipo' => $tipo,
            'solicitante' => $solicitante,
            'quantidade_solicitada' => $quantidade,
            'reason' => 'validation_error'
        ],
        null,
        null,
        false,
        'validation_error',
        'Campos inválidos.',
        'Falha ao criar retirada (validação).'
    );
    http_response_code(400);
    exit('Dados inválidos.');
}

// Usuário logado (ator)
$u = auth_user() ?? [];
$ator = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($ator === '') {
    http_response_code(401);
    exit('Usuário não autenticado.');
}

$competencia = competencia_from_datetime(date('Y-m-d H:i:s'));
if (!competencia_valida($competencia)) {
    http_response_code(500);
    exit('Competência inválida gerada.');
}

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

$ok = $stmt->execute([
    $produto,
    $quantidade,
    $tipo,
    $solicitante,
    $competencia
]);

if (!$ok) {
    http_response_code(500);
    exit('Erro ao salvar pedido.');
}

$newId = (int)$pdo->lastInsertId();

audit_log(
    $pdo,
    'create',
    'retirada',
    $newId,
    [
        'competencia' => $competencia,
        'produto' => $produto,
        'tipo' => $tipo,
        'quantidade_solicitada' => $quantidade,
        'solicitante' => $solicitante
    ],
    null,
    [
        'id' => $newId,
        'produto' => $produto,
        'quantidade_solicitada' => $quantidade,
        'tipo' => $tipo,
        'solicitante' => $solicitante,
        'status' => 'pedido',
        'competencia' => $competencia,
    ],
    true,
    null,
    null,
    "Criou retirada #{$newId} | {$tipo} | {$produto} | solicitado: {$quantidade} | Solicitante: {$solicitante}."
);

// gira token só depois de sucesso
csrf_rotate('novo_pedido');

$params = [
    'competencia' => $competencia,
    'toast' => 'criado',
    'highlight_id' => $newId,
];

if ($wantNext) {
    $params['open_novo'] = 1;
    $params['keep_solicitante'] = $solicitante;
}

redirect_with_query('../index.php', $params);
exit;
