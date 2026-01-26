<?php
// actions/relatorio_data.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';

auth_session_start();
auth_require_login();

header('Content-Type: application/json; charset=utf-8');

$competencia = (string)($_GET['competencia'] ?? '');

if (!competencia_valida($competencia)) {
  http_response_code(400);
  echo json_encode(['error' => 'competencia_invalida'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/**
 * LIMIT do Top Produtos:
 * - default: 10
 * - ?limit=all ou ?limit=0 => "todos" (com teto de segurança)
 */
$limitParam = $_GET['limit'] ?? '10';
$limit = 10;

if ($limitParam === 'all' || $limitParam === '0' || $limitParam === 0) {
  $limit = 500; // teto de segurança
} else {
  $limit = (int)$limitParam;
  if ($limit <= 0) $limit = 10;
  if ($limit > 500) $limit = 500;
}

$resp = [
  // ✅ Agora o status é Finalizados + Balanço feito (como seu relatorio.js está esperando)
  'status' => ['finalizados' => 0, 'balanco_feito' => 0],

  // ✅ Mantém Alertas igual
  'alertas' => ['sem_estoque' => 0, 'balanco' => 0],

  // ✅ Mantém gráficos
  'dias' => ['labels' => [], 'values' => []],
  'top_produtos' => ['labels' => [], 'values' => []],

  // ✅ Mantém solicitantes (pedidos + itens entregues)
  'por_solicitante' => ['labels' => [], 'pedidos' => [], 'itens' => []],

  // ✅ Se você quiser usar depois em cards/indicadores, já vai pronto
  'balanco' => ['pendente' => 0, 'feito' => 0],
];

/* ======================================================
 * 1) STATUS (Finalizados + Balanço feito)
 * ====================================================== */
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(status = 'finalizado'), 0) AS finalizados,
    COALESCE(SUM(COALESCE(balanco_feito,0) = 1), 0) AS balanco_feito
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$st = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['finalizados' => 0, 'balanco_feito' => 0];

$resp['status'] = [
  'finalizados'   => (int)($st['finalizados'] ?? 0),
  'balanco_feito' => (int)($st['balanco_feito'] ?? 0),
];

/* ======================================================
 * EXTRA) Balanço (pendente x feito) — útil pra cards
 * - Pendente: precisa_balanco=1 e ainda não fez
 * - Feito: balanco_feito=1
 * ====================================================== */
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(precisa_balanco = 1 AND COALESCE(balanco_feito,0) = 0), 0) AS pendente,
    COALESCE(SUM(COALESCE(balanco_feito,0) = 1), 0) AS feito
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$bal = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['pendente' => 0, 'feito' => 0];

$resp['balanco'] = [
  'pendente' => (int)($bal['pendente'] ?? 0),
  'feito'    => (int)($bal['feito'] ?? 0),
];

/* ======================================================
 * 2) ALERTAS
 * - Sem estoque
 * - Precisa balanço (sem sem_estoque e ainda não feito)
 * ====================================================== */
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(sem_estoque = 1), 0) AS sem_estoque,
    COALESCE(SUM(precisa_balanco = 1 AND sem_estoque = 0 AND COALESCE(balanco_feito,0) = 0), 0) AS balanco
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$al = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['sem_estoque' => 0, 'balanco' => 0];

$resp['alertas'] = [
  'sem_estoque' => (int)($al['sem_estoque'] ?? 0),
  'balanco'     => (int)($al['balanco'] ?? 0),
];

/* ======================================================
 * 3) POR DIA
 * ====================================================== */
$stmt = $pdo->prepare("
  SELECT DATE(data_pedido) AS dia, COUNT(*) AS total
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
  GROUP BY DATE(data_pedido)
  ORDER BY dia ASC
");
$stmt->execute([$competencia]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$labels = [];
$values = [];
foreach ($rows as $r) {
  $labels[] = (string)($r['dia'] ?? '');
  $values[] = (int)($r['total'] ?? 0);
}
$resp['dias'] = ['labels' => $labels, 'values' => $values];

/* ======================================================
 * 4) TOP PRODUTOS (qtd retirada TOTAL) - com limite dinâmico
 * ====================================================== */
$sqlTop = "
  SELECT
    produto,
    COALESCE(SUM(COALESCE(quantidade_retirada, 0)), 0) AS total_qtd,
    COUNT(*) AS total_pedidos
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
  GROUP BY produto
  ORDER BY total_qtd DESC, total_pedidos DESC
  LIMIT {$limit}
";

$stmt = $pdo->prepare($sqlTop);
$stmt->execute([$competencia]);
$top = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$topLabels = [];
$topValues = [];
foreach ($top as $t) {
  $topLabels[] = (string)($t['produto'] ?? '');
  $topValues[] = (int)($t['total_qtd'] ?? 0);
}
$resp['top_produtos'] = ['labels' => $topLabels, 'values' => $topValues];

/* ======================================================
 * 5) POR SOLICITANTE (pedidos finalizados + itens ENTREGUES)
 * - pedidos: qtd de registros finalizados
 * - itens: soma de quantidade_retirada
 * ====================================================== */
$stmt = $pdo->prepare("
  SELECT
    solicitante,
    COUNT(*) AS pedidos,
    COALESCE(SUM(COALESCE(quantidade_retirada, 0)), 0) AS itens
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
    AND status = 'finalizado'
    AND COALESCE(solicitante,'') <> ''
  GROUP BY solicitante
  ORDER BY itens DESC, pedidos DESC
");
$stmt->execute([$competencia]);
$solRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$resp['por_solicitante'] = [
  'labels'  => array_map(fn($r) => (string)($r['solicitante'] ?? ''), $solRows),
  'pedidos' => array_map(fn($r) => (int)($r['pedidos'] ?? 0), $solRows),
  'itens'   => array_map(fn($r) => (int)($r['itens'] ?? 0), $solRows),
];

echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
