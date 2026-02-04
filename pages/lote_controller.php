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

$edit = ((int)($_GET['edit'] ?? 0) === 1);

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

// =============================
// Recebimentos do lote
// =============================
$stmtRec = $pdo->prepare("
    SELECT *
    FROM lote_recebimentos
    WHERE lote_id = ?
    ORDER BY data_hora DESC, id DESC
");
$stmtRec->execute([$id]);
$recebimentos = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

// Recebimento atual vindo do GET
$recebimentoAtualId = (int)($_GET['recebimento_id'] ?? 0);

// Se não veio nada no GET, usa o mais recente (se existir)
if ($recebimentoAtualId <= 0 && !empty($recebimentos)) {
    $recebimentoAtualId = (int)$recebimentos[0]['id'];
}

// Valida: recebimento precisa pertencer ao lote
if ($recebimentoAtualId > 0) {
    $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM lote_recebimentos WHERE id = ? AND lote_id = ?");
    $stmtChk->execute([$recebimentoAtualId, $id]);
    if ((int)$stmtChk->fetchColumn() <= 0) {
        // se alguém tentar forçar um recebimento de outro lote
        $recebimentoAtualId = (!empty($recebimentos)) ? (int)$recebimentos[0]['id'] : 0;
    }
}

// =============================
// Filtros de itens
// =============================
$qProduto  = trim((string)($_GET['q_produto'] ?? ''));
$qVariacao = trim((string)($_GET['q_variacao'] ?? ''));
$qSituacao = trim((string)($_GET['q_situacao'] ?? ''));

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(10, min(200, (int)($_GET['per_page'] ?? 50)));
$offset = ($page - 1) * $perPage;

// =============================
// Itens do lote (FILTRADOS pelo recebimento atual)
// =============================
$itens = [];
$totalItens = 0;

if ($recebimentoAtualId > 0) {
    $where = " li.lote_id = ? AND li.recebimento_id = ? ";
    $args = [$id, $recebimentoAtualId];

    if ($qProduto !== '') {
        $where .= " AND li.produto_nome LIKE ? ";
        $args[] = '%' . $qProduto . '%';
    }

    if ($qVariacao === 'prata' || $qVariacao === 'ouro') {
        $where .= " AND LOWER(li.variacao) = ? ";
        $args[] = $qVariacao;
    }

    $allowedSit = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
    if (in_array($qSituacao, $allowedSit, true)) {
        $where .= " AND li.situacao = ? ";
        $args[] = $qSituacao;
    }

    // total
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM lote_itens li WHERE $where");
    $stmtCount->execute($args);
    $totalItens = (int)$stmtCount->fetchColumn();

    // página
    $stmtItens = $pdo->prepare("
        SELECT
            li.*,
            uconf.nome    AS conferido_por_nome,
            uconf.usuario AS conferido_por_usuario
        FROM lote_itens li
        LEFT JOIN users uconf ON uconf.id = li.conferido_por
        WHERE $where
        ORDER BY li.id ASC
        LIMIT $perPage OFFSET $offset
    ");
    $stmtItens->execute($args);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
}

// Paginação
$totalPages = (int)ceil($totalItens / $perPage);
if ($totalPages < 1) $totalPages = 1;

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
        if ($itensGrouped[$key]['prata'] === null) $itensGrouped[$key]['prata'] = $li;
        else $itensGrouped[$key]['ouro'] = $li;
    }

    if ($itensGrouped[$key]['situacao'] === null || $itensGrouped[$key]['situacao'] === '') {
        $itensGrouped[$key]['situacao'] = (string)($li['situacao'] ?? 'ok');
    }
    if ($itensGrouped[$key]['nota'] === null || $itensGrouped[$key]['nota'] === '') {
        $itensGrouped[$key]['nota'] = (string)($li['nota'] ?? '');
    }
}
$itensGrouped = array_values($itensGrouped);

// =============================
// Sugestões para filtros   
// =============================

// Sugestões (solicitantes)
$stmtSolic = $pdo->query("
    SELECT DISTINCT TRIM(solicitante) AS nome
    FROM retiradas
    WHERE solicitante IS NOT NULL
      AND TRIM(solicitante) <> ''
    ORDER BY nome ASC
");
$solicitantes = $stmtSolic->fetchAll(PDO::FETCH_ASSOC);

// Sugestões (usuários)
$stmtUsers = $pdo->query("
    SELECT id, nome, usuario
    FROM users
    ORDER BY nome ASC, usuario ASC
");
$usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Sugestões (produtos ativos)
$stmtProd = $pdo->query("
    SELECT id, nome
    FROM produtos
    WHERE ativo = 1
    ORDER BY nome ASC
    LIMIT 1000
");
$produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
