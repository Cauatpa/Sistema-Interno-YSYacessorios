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
$mesFechado = (bool) $stmtFechado->fetchColumn();

// ---------------------
// Dashboard contadores
// ---------------------
$stmtDash = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status <> 'finalizado' THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) AS finalizados,
        SUM(CASE WHEN precisa_balanco = 1 AND sem_estoque = 0 THEN 1 ELSE 0 END) AS balanco,
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
// Listagem
// ---------------------
list($where, $params) = montar_where_retiradas($competencia, $f);

// Paginação
$perPage = 30; // escolha: 30, 50, 100
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

// Total
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM retiradas {$where}");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Listagem paginada
list($where, $params) = montar_where_retiradas($competencia, $f);

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

// Listagem paginada
$sql = "SELECT * FROM retiradas {$where} ORDER BY data_pedido DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$retiradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
