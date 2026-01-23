<?php
// actions/lote_item_update_group.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_update_group')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

$idPrata = (int)($_POST['id_prata'] ?? 0);
$idOuro  = (int)($_POST['id_ouro'] ?? 0);

$qConfPrataRaw = trim((string)($_POST['qtd_conferida_prata'] ?? ''));
$qConfOuroRaw  = trim((string)($_POST['qtd_conferida_ouro'] ?? ''));

$qConfPrata = ($qConfPrataRaw === '') ? null : (int)$qConfPrataRaw;
$qConfOuro  = ($qConfOuroRaw  === '') ? null : (int)$qConfOuroRaw;

$situacao = (string)($_POST['situacao'] ?? 'ok');
$nota = trim((string)($_POST['nota'] ?? ''));

$allowed = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if (!in_array($situacao, $allowed, true)) $situacao = 'ok';

$notaDb = ($nota === '') ? null : $nota;

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

if ($idPrata <= 0 && $idOuro <= 0) {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Nada para salvar.'
    ]));
    exit;
}

// ---- BEFORE (pega o estado atual de cada item que vai mudar)
$stmtSel = $pdo->prepare("
  SELECT id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota, lote_id, recebimento_id
  FROM lote_itens
  WHERE id = ? AND lote_id = ?
  LIMIT 1
");

$beforeRows = [];
if ($idPrata > 0) {
    $stmtSel->execute([$idPrata, $loteId]);
    $beforeRows['prata'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}
if ($idOuro > 0) {
    $stmtSel->execute([$idOuro, $loteId]);
    $beforeRows['ouro'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Atualiza 1 ou 2 registros (garantindo lote_id)
$stmtUp = $pdo->prepare("
    UPDATE lote_itens
    SET qtd_conferida = ?, situacao = ?, nota = ?
    WHERE id = ? AND lote_id = ?
    LIMIT 1
");

if ($idPrata > 0) $stmtUp->execute([$qConfPrata, $situacao, $notaDb, $idPrata, $loteId]);
if ($idOuro  > 0) $stmtUp->execute([$qConfOuro,  $situacao, $notaDb, $idOuro,  $loteId]);

// ---- AFTER
$afterRows = [];
if ($idPrata > 0) {
    $stmtSel->execute([$idPrata, $loteId]);
    $afterRows['prata'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}
if ($idOuro > 0) {
    $stmtSel->execute([$idOuro, $loteId]);
    $afterRows['ouro'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Log (um log único para a “linha agrupada”)
$payload = [
    'lote_id' => $loteId,
    'recebimento_id' => $recebimentoId,
    'id_prata' => $idPrata ?: null,
    'id_ouro' => $idOuro ?: null,
];

audit_log(
    $pdo,
    'update',
    'lote_item_group',
    ($idPrata > 0 ? $idPrata : ($idOuro > 0 ? $idOuro : null)),
    $payload,
    ['before' => $beforeRows],
    ['after'  => $afterRows],
    true,
    'lote_item_update_group',
    "Atualizou item(ns) do lote #{$loteId} (recebimento #{$recebimentoId})."
);

header('Location: ../lote.php?' . http_build_query([
    'id' => $loteId,
    'edit' => 1,
    'recebimento_id' => $recebimentoId,
    'toast' => 'Salvo!',
    'highlight_id' => ($idPrata > 0 ? $idPrata : $idOuro),
]));
exit;
