<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

auth_session_start();
auth_require_role('admin');

header('Content-Type: application/json; charset=utf-8');

$loteId  = (int)($_GET['lote_id'] ?? 0);
$status  = trim((string)($_GET['status'] ?? ''));

if ($loteId <= 0 || $status === '') {
  echo json_encode(['ok' => false, 'error' => 'params_invalidos'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    produto_nome AS produto,
    variacao,
    COALESCE(qtd_prevista,0)  AS previsto,
    COALESCE(qtd_conferida,0) AS conferido,
    (COALESCE(qtd_conferida,0) - COALESCE(qtd_prevista,0)) AS diferenca
  FROM lote_itens
  WHERE lote_id = ?
    AND deleted_at IS NULL
    AND COALESCE(NULLIF(TRIM(situacao),''), 'Outro') = ?
  ORDER BY produto_nome ASC, variacao ASC
");
$stmt->execute([$loteId, $status]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$total = 0;
foreach ($rows as $r) {
  $total += (int)($r['conferido'] ?? 0);
}

echo json_encode([
  'ok' => true,
  'total' => $total,
  'itens' => $rows,
], JSON_UNESCAPED_UNICODE);
