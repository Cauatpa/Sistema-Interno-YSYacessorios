<?php
// pages/lote_controller.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

$toast = $_GET['toast'] ?? '';
$highlightId = (int)($_GET['highlight_id'] ?? 0);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

$canOperate = auth_has_role('operador');
$canAdmin   = auth_has_role('admin');

$edit = (int)($_GET['edit'] ?? 0) === 1;

// operador pode editar (conferência) quando edit=1
$canEdit = $canOperate && $edit;

// admin tem “poder total” na edição
$canEditFull = $canAdmin && $edit;

// Cabeçalho do lote
$stmt = $pdo->prepare("
    SELECT l.*, u.nome AS criado_por_nome, u.usuario AS criado_por_usuario
    FROM lotes l
    LEFT JOIN users u ON u.id = l.criado_por
    WHERE l.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$lote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lote) {
    header('Location: lotes.php?toast=' . urlencode('Lote não encontrado.'));
    exit;
}

// Itens do lote (linhas)
$stmtItens = $pdo->prepare("
    SELECT li.*
    FROM lote_itens li
    WHERE li.lote_id = ?
    ORDER BY li.id ASC
");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

// Agrupar para UI: 1 linha por produto, com prata/ouro dentro
$itensGrouped = [];
foreach ($itens as $li) {
    $produtoId = (int)($li['produto_id'] ?? 0);
    $produtoNome = (string)($li['produto_nome'] ?? '');
    $key = $produtoId > 0 ? ('pid:' . $produtoId) : ('pn:' . mb_strtolower(trim($produtoNome)));

    if (!isset($itensGrouped[$key])) {
        $itensGrouped[$key] = [
            'produto_id'   => $produtoId,
            'produto_nome' => $produtoNome,
            'prata' => null,
            'ouro'  => null,
            // por padrão, pega de um dos registros (quando existir)
            'situacao' => null,
            'nota'     => null,
        ];
    }

    $variacao = mb_strtolower(trim((string)($li['variacao'] ?? '')));
    if ($variacao === 'prata') {
        $itensGrouped[$key]['prata'] = $li;
    } elseif ($variacao === 'ouro') {
        $itensGrouped[$key]['ouro'] = $li;
    } else {
        // se vier algo fora (ou vazio), coloca em "prata" se estiver vazio, senão em "ouro"
        if ($itensGrouped[$key]['prata'] === null) $itensGrouped[$key]['prata'] = $li;
        else $itensGrouped[$key]['ouro'] = $li;
    }

    // situação/nota iguais: mantém a primeira que achar (ou substitui se estiver vazio)
    if ($itensGrouped[$key]['situacao'] === null || $itensGrouped[$key]['situacao'] === '') {
        $itensGrouped[$key]['situacao'] = (string)($li['situacao'] ?? 'ok');
    }
    if ($itensGrouped[$key]['nota'] === null || $itensGrouped[$key]['nota'] === '') {
        $itensGrouped[$key]['nota'] = (string)($li['nota'] ?? '');
    }
}

// transforma em lista para o foreach do view
$itensGrouped = array_values($itensGrouped);

// Sugestões (produtos ativos)
$stmtProd = $pdo->query("
    SELECT id, nome
    FROM produtos
    WHERE ativo = 1
    ORDER BY nome ASC
    LIMIT 1000
");
$produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
