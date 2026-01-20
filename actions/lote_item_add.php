<?php
// actions/lote_item_add.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_add')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$produtoId = (int)($_POST['produto_id'] ?? 0);

$temPrata = (int)($_POST['tem_prata'] ?? 0) === 1;
$temOuro  = (int)($_POST['tem_ouro'] ?? 0) === 1;

$qPrevPrata = (int)($_POST['qtd_prevista_prata'] ?? 0);
$qPrevOuro  = (int)($_POST['qtd_prevista_ouro'] ?? 0);

$qConfPrataRaw = trim((string)($_POST['qtd_conferida_prata'] ?? ''));
$qConfOuroRaw  = trim((string)($_POST['qtd_conferida_ouro'] ?? ''));

$qConfPrata = ($qConfPrataRaw === '') ? null : (int)$qConfPrataRaw;
$qConfOuro  = ($qConfOuroRaw  === '') ? null : (int)$qConfOuroRaw;

$situacao = (string)($_POST['situacao'] ?? 'ok');
$nota = trim((string)($_POST['nota'] ?? ''));

$allowed = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if (!in_array($situacao, $allowed, true)) $situacao = 'ok';

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

if ($produtoId <= 0) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Produto inválido.'));
    exit;
}

if (!$temPrata && !$temOuro) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Selecione Prata e/ou Ouro.'));
    exit;
}

$stmtP = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? LIMIT 1");
$stmtP->execute([$produtoId]);
$produtoNome = $stmtP->fetchColumn();
if (!$produtoNome) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Produto inválido.'));
    exit;
}

$notaDb = ($nota === '') ? null : $nota;

$stmtIns = $pdo->prepare("
  INSERT INTO lote_itens (lote_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if ($temPrata) {
    $stmtIns->execute([$loteId, $produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $notaDb]);
}

if ($temOuro) {
    $stmtIns->execute([$loteId, $produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $notaDb]);
}

header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Item adicionado!'));
exit;
