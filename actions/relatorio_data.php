<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';

auth_session_start();
auth_require_role('admin'); // relatório é admin

$competencia = (string)($_GET['competencia'] ?? '');

header('Content-Type: application/json; charset=utf-8');

if (!competencia_valida($competencia)) {
    http_response_code(400);
    echo json_encode(['error' => 'competencia_invalida'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$resp = [
    'status' => ['finalizados' => 0, 'pendentes' => 0],
    'alertas' => ['sem_estoque' => 0, 'balanco' => 0],
    'dias' => ['labels' => [], 'values' => []],
    'top_produtos' => ['labels' => [], 'values' => []],
    'por_solicitante' => ['labels' => [], 'pedidos' => [], 'itens' => []],
];

// 1) Status
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(status='finalizado'),0) AS finalizados,
    COALESCE(SUM(status<>'finalizado'),0) AS pendentes
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$st = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['finalizados' => 0, 'pendentes' => 0];

$resp['status'] = [
    'finalizados' => (int)($st['finalizados'] ?? 0),
    'pendentes'   => (int)($st['pendentes'] ?? 0),
];

// 2) Alertas
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(sem_estoque=1),0) AS sem_estoque,
    COALESCE(SUM(precisa_balanco=1 AND sem_estoque=0),0) AS balanco
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

// 3) Por dia
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

// 4) Top 10 produtos (qtd solicitada)
$stmt = $pdo->prepare("
  SELECT produto,
         COALESCE(SUM(quantidade_solicitada),0) AS total_qtd,
         COUNT(*) AS total_pedidos
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
  GROUP BY produto
  ORDER BY total_qtd DESC, total_pedidos DESC
  LIMIT 10
");
$stmt->execute([$competencia]);
$top = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$topLabels = [];
$topValues = [];
foreach ($top as $t) {
    $topLabels[] = (string)($t['produto'] ?? '');
    $topValues[] = (int)($t['total_qtd'] ?? 0);
}
$resp['top_produtos'] = ['labels' => $topLabels, 'values' => $topValues];

// 5) Por solicitante (pedidos + itens)
$stmt = $pdo->prepare("
  SELECT solicitante,
         COUNT(*) AS pedidos,
         COALESCE(SUM(quantidade_solicitada),0) AS itens
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
    AND COALESCE(solicitante,'') <> ''
  GROUP BY solicitante
  ORDER BY pedidos DESC, itens DESC
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
