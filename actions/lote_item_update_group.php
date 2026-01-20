<?php
// actions/lote_item_update_group.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

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
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Nada para salvar.'));
    exit;
}

// Atualiza 1 ou 2 registros (garantindo lote_id)
$stmtUp = $pdo->prepare("
    UPDATE lote_itens
    SET qtd_conferida = ?, situacao = ?, nota = ?
    WHERE id = ? AND lote_id = ?
    LIMIT 1
");

if ($idPrata > 0) {
    $stmtUp->execute([$qConfPrata, $situacao, $notaDb, $idPrata, $loteId]);
}
if ($idOuro > 0) {
    $stmtUp->execute([$qConfOuro, $situacao, $notaDb, $idOuro, $loteId]);
}

header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Salvo!'));
exit;
