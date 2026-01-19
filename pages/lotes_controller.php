<?php
// pages/lotes_controller.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/auth.php';

$u = auth_require_login();
csrf_session_start();

// ---------------------
// Parâmetros (UI)
// ---------------------
$toast       = $_GET['toast'] ?? '';
$highlightId = (int)($_GET['highlight_id'] ?? 0);

// ---------------------
// Filtros
// ---------------------
$q = trim((string)($_GET['q'] ?? '')); // busca em codigo/fornecedor
$status = (string)($_GET['status'] ?? 'todos');
if (!in_array($status, ['todos', 'aberto', 'conferido', 'fechado'], true)) {
    $status = 'todos';
}

$dataIni = (string)($_GET['data_ini'] ?? '');
$dataFim = (string)($_GET['data_fim'] ?? '');

// Validação leve de datas (YYYY-MM-DD)
$validDate = function (string $d): bool {
    if ($d === '') return true;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
};
if (!$validDate($dataIni)) $dataIni = '';
if (!$validDate($dataFim)) $dataFim = '';

// ---------------------
// Paginação (mesmo padrão do seu index)
// ---------------------
$perPageOptions = [30, 50, 100];
$perPage = (int)($_GET['per_page'] ?? 30);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 30;
}

$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

// ---------------------
// WHERE dinâmico
// ---------------------
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(l.codigo LIKE ? OR l.fornecedor LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($status !== 'todos') {
    $where[] = "l.status = ?";
    $params[] = $status;
}

if ($dataIni !== '') {
    $where[] = "l.data_recebimento >= ?";
    $params[] = $dataIni;
}
if ($dataFim !== '') {
    $where[] = "l.data_recebimento <= ?";
    $params[] = $dataFim;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// ---------------------
// Total
// ---------------------
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM lotes l {$whereSql}");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// ---------------------
// Listagem (com contagens)
// ---------------------
$sql = "
SELECT
    l.*,
    u.nome AS criado_por_nome,
    u.usuario AS criado_por_usuario,
    COALESCE(it.itens_total, 0) AS itens_total,
    COALESCE(it.divergencias, 0) AS divergencias
FROM lotes l
LEFT JOIN users u ON u.id = l.criado_por
LEFT JOIN (
    SELECT
        lote_id,
        COUNT(*) AS itens_total,
        SUM(
            CASE
              WHEN situacao <> 'ok' THEN 1
              WHEN (qtd_conferida IS NOT NULL AND qtd_conferida <> qtd_prevista) THEN 1
              ELSE 0
            END
        ) AS divergencias
    FROM lote_itens
    GROUP BY lote_id
) it ON it.lote_id = l.id
{$whereSql}
ORDER BY
    FIELD(l.status, 'aberto','conferido','fechado'),
    COALESCE(l.data_recebimento, '1000-01-01') DESC,
    l.id DESC
LIMIT {$perPage} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lotes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
