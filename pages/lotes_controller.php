<?php
// pages/lotes_controller.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';

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

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? 'todos');
$dataIni = (string)($_GET['data_ini'] ?? '');
$dataFim = (string)($_GET['data_fim'] ?? '');

// ---------------------
// Meses disponíveis
// ---------------------
$stmtMeses = $pdo->query("
    SELECT DISTINCT competencia
    FROM lotes
    ORDER BY competencia DESC
");
$mesesDisponiveis = $stmtMeses->fetchAll(PDO::FETCH_COLUMN);

// ---------------------
// WHERE dinâmico
// ---------------------
$where = ['l.competencia = ?'];
$params = [$competencia];

if ($q !== '') {
    $where[] = '(l.codigo LIKE ? OR l.fornecedor LIKE ?)';
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}

if ($status !== 'todos') {
    $where[] = 'l.status = ?';
    $params[] = $status;
}

if ($dataIni !== '') {
    $where[] = 'l.data_recebimento >= ?';
    $params[] = $dataIni;
}

if ($dataFim !== '') {
    $where[] = 'l.data_recebimento <= ?';
    $params[] = $dataFim;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ---------------------
// Paginação
// ---------------------
$perPageOptions = [30, 50, 100];
$perPage = (int)($_GET['per_page'] ?? 30);
if (!in_array($perPage, $perPageOptions, true)) $perPage = 30;

$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

// Total
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM lotes l
    {$whereSql}
");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ---------------------
// Listagem principal
// ---------------------
$sql = "
    SELECT
        l.*,

        u.nome   AS criado_por_nome,
        u.usuario AS criado_por_usuario,

        COUNT(li.id) AS itens_total,

        SUM(
            CASE
                WHEN li.situacao IN ('faltando','a_mais','banho_trocado','quebra','outro')
                THEN 1 ELSE 0
            END
        ) AS divergencias

    FROM lotes l
    LEFT JOIN users u ON u.id = l.criado_por
    LEFT JOIN lote_itens li ON li.lote_id = l.id

    {$whereSql}

    GROUP BY l.id
    ORDER BY l.criado_em DESC
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
