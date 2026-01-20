<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_edit_full')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

$produtoId = (int)($_POST['produto_id'] ?? 0);
$variacao = trim((string)($_POST['variacao'] ?? ''));
$qtdPrev = (int)($_POST['qtd_prevista'] ?? 0);

$qtdConfRaw = trim((string)($_POST['qtd_conferida'] ?? ''));
$qtdConf = ($qtdConfRaw === '') ? null : (int)$qtdConfRaw;

$situacao = (string)($_POST['situacao'] ?? 'ok');
$allowed = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if (!in_array($situacao, $allowed, true)) $situacao = 'ok';

$nota = trim((string)($_POST['nota'] ?? ''));
if ($nota === '') $nota = null;

$stmtP = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? LIMIT 1");
$stmtP->execute([$produtoId]);
$produtoNome = $stmtP->fetchColumn();
if (!$produtoNome) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Produto inválido.'));
    exit;
}

if ($variacao === '') $variacao = null;

$stmt = $pdo->prepare("
  UPDATE lote_itens
  SET produto_id = ?, produto_nome = ?, variacao = ?, qtd_prevista = ?, qtd_conferida = ?, situacao = ?, nota = ?
  WHERE id = ? AND lote_id = ?
  LIMIT 1
");
$stmt->execute([$produtoId, $produtoNome, $variacao, $qtdPrev, $qtdConf, $situacao, $nota, $id, $loteId]);

header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Item (geral) atualizado!'));
exit;
