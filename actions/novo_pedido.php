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

// ✅ Lê "next" uma vez (0/1)
$wantNext = ((int)($_POST['next'] ?? 0) === 1);

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

// Campos obrigatórios (modo novo ou antigo)
require_fields($_POST, ['produto', 'solicitante']);

$produto = trim((string)($_POST['produto'] ?? ''));
$solicitante = trim((string)($_POST['solicitante'] ?? ''));

// ---- MODO NOVO: tipos[] + quantidade_prata / quantidade_ouro
$tipos = $_POST['tipos'] ?? null;
$selected = [];

$qtdByTipo = [
    'prata' => 0,
    'ouro'  => 0,
];

if (is_array($tipos) && count($tipos) > 0) {
    foreach ($tipos as $t) {
        $t = one_of(trim((string)$t), ['prata', 'ouro'], '');
        if ($t !== '') $selected[$t] = true;
    }

    if (!empty($selected['prata'])) {
        $qtdByTipo['prata'] = int_pos($_POST['quantidade_prata'] ?? 0);
    }
    if (!empty($selected['ouro'])) {
        $qtdByTipo['ouro'] = int_pos($_POST['quantidade_ouro'] ?? 0);
    }

    // validações do modo novo
    if (
        $produto === '' ||
        $solicitante === '' ||
        (empty($selected['prata']) && empty($selected['ouro'])) ||
        (!empty($selected['prata']) && $qtdByTipo['prata'] <= 0) ||
        (!empty($selected['ouro']) && $qtdByTipo['ouro'] <= 0)
    ) {
        audit_log(
            $pdo,
            'create',
            'retirada',
            null,
            [
                'produto' => $produto,
                'tipos' => array_keys($selected),
                'qtd_prata' => $qtdByTipo['prata'],
                'qtd_ouro' => $qtdByTipo['ouro'],
                'solicitante' => $solicitante,
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

    // ---- MODO ANTIGO: tipo + quantidade_solicitada
} else {
    require_fields($_POST, ['tipo', 'quantidade_solicitada']);

    $tipo = one_of(trim((string)($_POST['tipo'] ?? '')), ['prata', 'ouro'], '');
    $quantidade = int_pos($_POST['quantidade_solicitada'] ?? 0);

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

    $selected[$tipo] = true;
    $qtdByTipo[$tipo] = $quantidade;
}

// Não interfere na lógica principal do pedido.
try {
    // normaliza: trim + remove espaços duplicados + case-insensitive
    $produtoNorm = mb_strtolower(preg_replace('/\s+/', ' ', $produto));

    // tenta inserir sem duplicar
    $stmtP = $pdo->prepare("
        INSERT INTO produtos (nome, nome_norm, ativo)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            ativo = 1
    ");
    $stmtP->execute([$produto, $produtoNorm]);
} catch (Throwable $e) {
    // não quebra o pedido se falhar
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

$createdIds = [];

foreach (['prata', 'ouro'] as $t) {
    if (empty($selected[$t])) continue;

    $qtd = (int)$qtdByTipo[$t];
    $ok = $stmt->execute([
        $produto,
        $qtd,
        $t,
        $solicitante,
        $competencia
    ]);

    if (!$ok) {
        http_response_code(500);
        exit('Erro ao salvar pedido.');
    }

    $newId = (int)$pdo->lastInsertId();
    $createdIds[] = $newId;

    audit_log(
        $pdo,
        'create',
        'retirada',
        $newId,
        [
            'competencia' => $competencia,
            'produto' => $produto,
            'tipo' => $t,
            'quantidade_solicitada' => $qtd,
            'solicitante' => $solicitante
        ],
        null,
        [
            'id' => $newId,
            'produto' => $produto,
            'quantidade_solicitada' => $qtd,
            'tipo' => $t,
            'solicitante' => $solicitante,
            'status' => 'pedido',
            'competencia' => $competencia,
        ],
        true,
        null,
        null,
        "Criou retirada #{$newId} | {$t} | {$produto} | solicitado: {$qtd} | Solicitante: {$solicitante}."
    );
}

if (count($createdIds) === 0) {
    http_response_code(400);
    exit('Nenhum pedido criado.');
}

// gira token só depois de sucesso
csrf_rotate('novo_pedido');

$params = [
    'competencia' => $competencia,
    'toast' => 'criado',
    // destaca o último criado (ou você pode mudar a UI depois pra destacar os 2)
    'highlight_id' => end($createdIds),
];

if ($wantNext) {
    $params['open_novo'] = 1;
    $params['keep_solicitante'] = $solicitante;
}

if ($wantNext) {
    $params['open_novo'] = 1;
    $params['keep_solicitante'] = $solicitante;
}

$return = (string)($_POST['return'] ?? '../index.php');

// Normaliza (aceita "index.php?...")
$base = $return;
parse_str(parse_url($return, PHP_URL_QUERY) ?? '', $q);

// injeta os params (toast, highlight, etc) sem perder os filtros
foreach ($params as $k => $v) {
    $q[$k] = $v;
}

$path = parse_url($return, PHP_URL_PATH) ?: '../index.php';
$dest = $path . '?' . http_build_query($q);

header('Location: ' . $dest);
exit;
