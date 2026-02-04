<?php
// actions/lote_item_salvar.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate('lote_item_salvar', $_POST['csrf_token'] ?? '')) {
    header('Location: ../lotes.php?toast=' . urlencode('Sessão expirada. Tente novamente.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$id     = (int)($_POST['id'] ?? 0);

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

$conferidoPor = (int)($_POST['conferido_por'] ?? 0);
$conferidoPorDb = $conferidoPor > 0 ? $conferidoPor : null;

// bloqueia edição se lote fechado
$stmt = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmt->execute([$loteId]);
$st = (string)$stmt->fetchColumn();
if ($st === 'fechado') {
    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Lote fechado. Edição bloqueada.'));
    exit;
}

$nota = trim((string)($_POST['nota'] ?? ''));
if ($nota === '') $nota = null;

if ($id > 0) {
    // UPDATE (conferência)
    $qtd_conferida = $_POST['qtd_conferida'] ?? null;
    if ($qtd_conferida === '' || $qtd_conferida === null) $qtd_conferida = null;
    else $qtd_conferida = (int)$qtd_conferida;

    $situacao = (string)($_POST['situacao'] ?? 'ok');
    $allow = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
    if (!in_array($situacao, $allow, true)) $situacao = 'ok';

    $stmtU = $pdo->prepare("
        UPDATE lote_itens
        SET qtd_conferida = ?, situacao = ?, nota = ?, conferido_por = ?
        WHERE id = ? AND lote_id = ?
        LIMIT 1
    ");
    $stmtU->execute([$qtd_conferida, $situacao, $nota, $conferidoPorDb, $id, $loteId]);


    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Item atualizado!') . '&highlight_id=' . $id);
    exit;
}

// INSERT (previsto)
$produtoId = (int)($_POST['produto_id'] ?? 0);
$variacao  = trim((string)($_POST['variacao'] ?? ''));
$qtdPrev   = (int)($_POST['qtd_prevista'] ?? 0);
if ($qtdPrev < 0) $qtdPrev = 0;
if ($variacao === '') $variacao = null;

// pega nome do produto
$stmtP = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? LIMIT 1");
$stmtP->execute([$produtoId]);
$produtoNome = (string)$stmtP->fetchColumn();

if ($produtoId <= 0 || $produtoNome === '') {
    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Produto inválido.'));
    exit;
}

$stmtI = $pdo->prepare("
  INSERT INTO lote_itens (lote_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
  VALUES (?, ?, ?, ?, ?, NULL, 'ok', ?)
");
$stmtI->execute([$loteId, $produtoId, $produtoNome, $variacao, $qtdPrev, $nota]);

$newId = (int)$pdo->lastInsertId();
header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Item adicionado!') . '&highlight_id=' . $newId);
exit;
