<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';

auth_session_start();
auth_require_login();

$competencia = (string)($_GET['competencia'] ?? '');
if (!competencia_valida($competencia)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'competencia_invalida']);
    exit;
}

// Status
$stmt = $pdo->prepare("
  SELECT
    SUM(status='finalizado') AS finalizados,
    SUM(status<>'finalizado') AS pendentes
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$st = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['finalizados' => 0, 'pendentes' => 0];

// Alertas
$stmt = $pdo->prepare("
  SELECT
    SUM(sem_estoque=1) AS sem_estoque,
    SUM(precisa_balanco=1 AND sem_estoque=0) AS balanco
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$al = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['sem_estoque' => 0, 'balanco' => 0];

// Por dia (no mês)
$stmt = $pdo->prepare("
  SELECT DATE(data_pedido) AS dia, COUNT(*) AS total
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
  GROUP BY DATE(data_pedido)
  ORDER BY dia ASC
");
$stmt->execute([$competencia]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$values = [];
foreach ($rows as $r) {
    $labels[] = (string)$r['dia'];
    $values[] = (int)$r['total'];
}

// Top 10 produtos (por quantidade solicitada)
$stmt = $pdo->prepare("
  SELECT produto,
         SUM(quantidade_solicitada) AS total_qtd,
         COUNT(*) AS total_pedidos
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
  GROUP BY produto
  ORDER BY total_qtd DESC, total_pedidos DESC
  LIMIT 10
");
$stmt->execute([$competencia]);
$top = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topLabels = [];
$topValues = [];
foreach ($top as $t) {
    $topLabels[] = (string)$t['produto'];
    $topValues[] = (int)$t['total_qtd'];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => [
        'finalizados' => (int)$st['finalizados'],
        'pendentes'   => (int)$st['pendentes'],
    ],
    'alertas' => [
        'sem_estoque' => (int)$al['sem_estoque'],
        'balanco'     => (int)$al['balanco'],
    ],
    'dias' => [
        'labels' => $labels,
        'values' => $values,
    ],
    'top_produtos' => [
        'labels' => $topLabels,
        'values' => $topValues,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
