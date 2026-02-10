<?php
// pages/api/alerta_itens.php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

auth_session_start();
auth_require_role('admin');

header('Content-Type: application/json; charset=utf-8');

$competencia = trim((string)($_GET['competencia'] ?? ''));
$tipoAlerta  = trim((string)($_GET['tipo'] ?? ''));

if ($competencia === '' || $tipoAlerta === '') {
    echo json_encode(['ok' => false, 'error' => 'Parametros invalidos']);
    exit;
}

// Regras dos alertas (igual seu dashboard):
// - "Sem estoque": sem_estoque = 1 (e normalmente balanco_feito=0, se você quiser aplicar)
// - "Precisa balanço": precisa_balanco=1 AND sem_estoque=0 AND balanco_feito=0
$where = '';
$params = [$competencia];

if ($tipoAlerta === 'sem_estoque') {
    $where = "COALESCE(sem_estoque,0) = 1";
    // Se quiser excluir balanço feito daqui, descomente:
    // $where .= " AND COALESCE(balanco_feito,0) = 0";
} elseif ($tipoAlerta === 'precisa_balanco') {
    $where = "COALESCE(precisa_balanco,0) = 1
              AND COALESCE(sem_estoque,0) = 0
              AND COALESCE(balanco_feito,0) = 0";
} else {
    echo json_encode(['ok' => false, 'error' => 'Tipo de alerta invalido']);
    exit;
}

$sql = "
  SELECT
    produto,
    tipo,
    SUM(COALESCE(quantidade_solicitada,0)) AS qtd,
    COUNT(*) AS pedidos
  FROM retiradas
  WHERE competencia = ?
    AND deleted_at IS NULL
    AND ($where)
  GROUP BY produto, tipo
  ORDER BY qtd DESC
  LIMIT 500
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$totalItens = 0;
$totalPedidos = 0;
foreach ($rows as $r) {
    $totalItens += (int)($r['qtd'] ?? 0);
    $totalPedidos += (int)($r['pedidos'] ?? 0);
}

echo json_encode([
    'ok' => true,
    'competencia' => $competencia,
    'tipo' => $tipoAlerta,
    'total_itens' => $totalItens,
    'total_pedidos' => $totalPedidos,
    'itens' => array_map(function ($r) {
        return [
            'produto' => (string)($r['produto'] ?? ''),
            'tipo' => (string)($r['tipo'] ?? ''),
            'qtd' => (int)($r['qtd'] ?? 0),
            'pedidos' => (int)($r['pedidos'] ?? 0),
        ];
    }, $rows),
]);
