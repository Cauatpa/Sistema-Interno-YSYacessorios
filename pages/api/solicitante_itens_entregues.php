<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/competencia.php';
require_once __DIR__ . '/../../helpers/validation.php';

auth_session_start();
auth_require_login();

header('Content-Type: application/json; charset=utf-8');

$competencia  = (string)($_GET['competencia'] ?? '');
$solicitante  = trim((string)($_GET['solicitante'] ?? ''));

if (!competencia_valida($competencia)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'competencia_invalida']);
    exit;
}
if ($solicitante === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'solicitante_vazio']);
    exit;
}

/**
 * IMPORTANTE:
 * aqui eu uso match EXATO pelo campo "solicitante" como o gráfico está mostrando.
 * (ex: "Thalia, Isa" conta como um solicitante só, porque é assim que está salvo no banco)
 */
$sql = "
    SELECT
        produto,
        COALESCE(tipo,'') AS tipo,
        SUM(COALESCE(quantidade_retirada,0)) AS itens_entregues,
        COUNT(*) AS pedidos
    FROM retiradas
    WHERE competencia = ?
      AND deleted_at IS NULL
      AND TRIM(COALESCE(solicitante,'')) = ?
      AND COALESCE(quantidade_retirada,0) > 0
    GROUP BY produto, tipo
    ORDER BY itens_entregues DESC, produto ASC
    LIMIT 500
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$competencia, $solicitante]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$total = 0;
foreach ($rows as $r) {
    $total += (int)($r['itens_entregues'] ?? 0);
}

echo json_encode([
    'ok' => true,
    'competencia' => $competencia,
    'solicitante' => $solicitante,
    'total_itens_entregues' => $total,
    'itens' => $rows,
], JSON_UNESCAPED_UNICODE);
