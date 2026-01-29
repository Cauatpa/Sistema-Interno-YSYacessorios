<?php
// pages/index_controller.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/ui_retiradas.php';
require_once __DIR__ . '/../helpers/filtros_retiradas.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';

$u = auth_require_login();
csrf_session_start();

// ---------------------
// Parâmetros
// ---------------------
$toast = $_GET['toast'] ?? '';
$highlightId = (int)($_GET['highlight_id'] ?? 0);

$competencia = $_GET['competencia'] ?? competencia_atual();
if (!competencia_valida($competencia)) {
    $competencia = competencia_atual();
}

// Normaliza filtros
$f = normaliza_filtros($_GET);
$filtro = $f['filtro'];
$busca = $f['busca'];
$tipo = $f['tipo'];
$statusFiltro = $f['statusFiltro'];
$soBalanco = $f['soBalanco'];
$soSemEstoque = $f['soSemEstoque'];

// ✅ GARANTIA: aceita o novo status "balanco_feito" mesmo que o helper ainda não trate
// (não muda a lógica, só normaliza para evitar "ignorar" o filtro)
$allowedStatus = ['todos', 'pendentes', 'finalizados', 'balanco_feito'];
if (!in_array((string)$statusFiltro, $allowedStatus, true)) {
    $statusFiltro = 'todos';
    $f['statusFiltro'] = 'todos';
} else {
    $f['statusFiltro'] = (string)$statusFiltro;
}

// ---------------------
// Lista de meses
// ---------------------
$stmtMeses = $pdo->query("SELECT DISTINCT competencia FROM retiradas ORDER BY competencia DESC");
$mesesDisponiveis = $stmtMeses->fetchAll(PDO::FETCH_COLUMN);

// ---------------------
// Mês fechado
// ---------------------
$stmtFechado = $pdo->prepare("SELECT 1 FROM fechamentos WHERE competencia = ? LIMIT 1");
$stmtFechado->execute([$competencia]);
$mesFechado = (bool)$stmtFechado->fetchColumn();

// ---------------------
// Dashboard contadores
// ---------------------
$stmtDash = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status <> 'finalizado' THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) AS finalizados,
        SUM(
            CASE
                WHEN precisa_balanco = 1
                    AND sem_estoque = 0
                    AND COALESCE(balanco_feito,0) = 0
                THEN 1 ELSE 0
            END
        ) AS balanco,
        SUM(CASE WHEN sem_estoque = 1 THEN 1 ELSE 0 END) AS sem_estoque
    FROM retiradas
    WHERE competencia = ? AND deleted_at IS NULL
");
$stmtDash->execute([$competencia]);
$dash = $stmtDash->fetch(PDO::FETCH_ASSOC) ?: [
    'total' => 0,
    'pendentes' => 0,
    'finalizados' => 0,
    'balanco' => 0,
    'sem_estoque' => 0
];

// ---------------------
// Listagem (filtros)
// ---------------------
list($where, $params) = montar_where_retiradas($competencia, $f);

// ✅ PATCH: se o helper ainda não colocou o filtro balanco_feito, a gente aplica aqui
// (não altera lógica do sistema, só garante o filtro novo)
if (($f['statusFiltro'] ?? 'todos') === 'balanco_feito') {
    // evita duplicar se helper já aplicou
    if (stripos($where, 'balanco_feito') === false) {
        $where .= " AND COALESCE(balanco_feito,0) = 1 ";
    }
}

// ---------------------
// Paginação + per_page (ÚNICO BLOCO)
// ---------------------
$perPageOptions = [30, 50, 100];
$perPage = (int)($_GET['per_page'] ?? 30);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 30;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

// Total (com os mesmos filtros)
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM retiradas {$where}");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Listagem paginada
$sql = "SELECT * FROM retiradas {$where} ORDER BY data_pedido DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$retiradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// Sugestões de solicitantes (para autocomplete)
// ======================
$stmtSol = $pdo->query("
    SELECT DISTINCT solicitante
    FROM retiradas
    WHERE deleted_at IS NULL
      AND COALESCE(solicitante,'') <> ''
    ORDER BY solicitante ASC
    LIMIT 500
");
$rawSolicitantes = $stmtSol->fetchAll(PDO::FETCH_COLUMN) ?: [];

$solicitantesSugestoes = [];
$seen = [];

foreach ($rawSolicitantes as $s) {
    foreach (preg_split('/,/', (string)$s) as $parte) {
        $nome = trim($parte);
        if ($nome === '') continue;

        // chave case-insensitive pra não duplicar "sarah" e "Sarah"
        $k = mb_strtolower($nome);
        if (isset($seen[$k])) continue;

        $seen[$k] = true;
        $solicitantesSugestoes[] = $nome;
    }
}

// ======================
// Sugestões de produtos (para autocomplete)
// (com tabela nova: vem de produtos)
// ======================
$stmtProd = $pdo->query("
    SELECT nome
    FROM produtos
    WHERE ativo = 1
    ORDER BY nome ASC
    LIMIT 800
");
$rawProdutos = $stmtProd->fetchAll(PDO::FETCH_COLUMN) ?: [];

$produtosSugestoes = [];
$seenProd = [];
foreach ($rawProdutos as $p) {
    $nome = preg_replace('/\s+/', ' ', trim((string)$p));
    if ($nome === '') continue;

    $k = mb_strtolower($nome);
    if (isset($seenProd[$k])) continue;

    $seenProd[$k] = true;
    $produtosSugestoes[] = $nome;
}
