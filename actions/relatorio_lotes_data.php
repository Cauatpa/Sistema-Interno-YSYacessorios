<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

auth_session_start();
auth_require_role('admin');

header('Content-Type: application/json; charset=utf-8');

$loteId = (int)($_GET['lote_id'] ?? 0);
if ($loteId <= 0) {
  echo json_encode(['ok' => false, 'error' => 'lote_id_invalido'], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Schema real (pelo seu print):
 * - lotes: id, competencia, codigo, data_recebimento, fornecedor, observacoes, status ...
 * - lote_itens: lote_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao ...
 */

// Dados do lote
$stmt = $pdo->prepare("
  SELECT
    id,
    competencia,
    codigo,
    data_recebimento,
    fornecedor,
    status
  FROM lotes
  WHERE id = ?
    AND deleted_at IS NULL
  LIMIT 1
");
$stmt->execute([$loteId]);
$lote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lote) {
  echo json_encode(['ok' => false, 'error' => 'lote_nao_encontrado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$codigo = trim((string)($lote['codigo'] ?? ''));
$label  = $codigo !== '' ? $codigo : ('Lote #' . (int)$lote['id']);

// KPIs
$stmt = $pdo->prepare("
  SELECT
    COUNT(*) AS skus,
    COALESCE(SUM(COALESCE(qtd_prevista,0)), 0) AS total_previsto,
    COALESCE(SUM(COALESCE(qtd_conferida,0)), 0) AS total_conferido,

    COALESCE(SUM(CASE WHEN TRIM(LOWER(COALESCE(situacao,''))) = 'ok'
                      THEN COALESCE(qtd_conferida,0) ELSE 0 END), 0) AS total_ok,

    COALESCE(SUM(CASE WHEN TRIM(LOWER(COALESCE(situacao,''))) <> 'ok'
                      THEN 1 ELSE 0 END), 0) AS skus_diverg
  FROM lote_itens
  WHERE lote_id = ?
    AND deleted_at IS NULL
");
$stmt->execute([$loteId]);
$k = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$totalPrev = (int)($k['total_previsto'] ?? 0);
$totalConf = (int)($k['total_conferido'] ?? 0);
$totalOk   = (int)($k['total_ok'] ?? 0);
$skus      = (int)($k['skus'] ?? 0);
$skusDiv   = (int)($k['skus_diverg'] ?? 0);

$okPct = $totalConf > 0 ? round(($totalOk / $totalConf) * 100, 1) : 0.0;
$diff  = $totalConf - $totalPrev;

// Distribuição por situação (somando qtd_conferida)
$stmt = $pdo->prepare("
  SELECT
    COALESCE(NULLIF(TRIM(situacao),''), 'Outro') AS situacao,
    COALESCE(SUM(COALESCE(qtd_conferida,0)), 0) AS total
  FROM lote_itens
  WHERE lote_id = ?
    AND deleted_at IS NULL
  GROUP BY situacao
  ORDER BY total DESC
");
$stmt->execute([$loteId]);
$statusRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$statusLabels = [];
$statusValues = [];
foreach ($statusRows as $r) {
  $statusLabels[] = (string)$r['situacao'];
  $statusValues[] = (int)$r['total'];
}

// Top divergências (por SKU) — abs(qtd_conferida - qtd_prevista)
$stmt = $pdo->prepare("
  SELECT
    produto_nome AS produto,
    variacao,
    COALESCE(qtd_prevista,0)  AS previsto,
    COALESCE(qtd_conferida,0) AS conferido,
    ABS(COALESCE(qtd_conferida,0) - COALESCE(qtd_prevista,0)) AS diff_abs,
    COALESCE(NULLIF(TRIM(situacao),''), 'Outro') AS situacao
  FROM lote_itens
  WHERE lote_id = ?
    AND deleted_at IS NULL
  ORDER BY diff_abs DESC, produto_nome ASC
  LIMIT 10
");
$stmt->execute([$loteId]);
$top = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Itens (tabela)
$stmt = $pdo->prepare("
  SELECT
    produto_nome AS produto,
    variacao,
    COALESCE(qtd_prevista,0)  AS previsto,
    COALESCE(qtd_conferida,0) AS conferido,
    (COALESCE(qtd_conferida,0) - COALESCE(qtd_prevista,0)) AS diferenca,
    COALESCE(NULLIF(TRIM(situacao),''), 'Outro') AS situacao
  FROM lote_itens
  WHERE lote_id = ?
    AND deleted_at IS NULL
  ORDER BY produto_nome ASC, variacao ASC
");
$stmt->execute([$loteId]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
  'ok' => true,
  'lote' => [
    'id' => (int)$lote['id'],
    'label' => $label,
    'competencia' => (string)($lote['competencia'] ?? ''),
    'fornecedor' => (string)($lote['fornecedor'] ?? ''),
    'data_recebimento' => (string)($lote['data_recebimento'] ?? ''),
    'status' => (string)($lote['status'] ?? ''),
  ],
  'kpis' => [
    'skus' => $skus,
    'total_previsto' => $totalPrev,
    'total_conferido' => $totalConf,
    'total_ok' => $totalOk,
    'ok_pct' => $okPct,
    'skus_diverg' => $skusDiv,
    'diff' => $diff,
  ],
  'status' => [
    'labels' => $statusLabels,
    'values' => $statusValues,
  ],
  'top_diverg' => $top,
  'itens' => $itens,
], JSON_UNESCAPED_UNICODE);
