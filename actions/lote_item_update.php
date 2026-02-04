<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_update')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

$qtdConfRaw = trim((string)($_POST['qtd_conferida'] ?? ''));
$qtdConf = ($qtdConfRaw === '') ? null : (int)$qtdConfRaw;

$conferidoPor = (int)($_POST['conferido_por'] ?? 0);
$conferidoPorDb = $conferidoPor > 0 ? $conferidoPor : null;

$situacao = (string)($_POST['situacao'] ?? 'ok');
$allowed = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if (!in_array($situacao, $allowed, true)) $situacao = 'ok';

$nota = trim((string)($_POST['nota'] ?? ''));
if ($nota === '') $nota = null;

$stmt = $pdo->prepare("
  UPDATE lote_itens
  SET qtd_conferida = ?, situacao = ?, nota = ?, conferido_por = ?
  WHERE id = ? AND lote_id = ?
  LIMIT 1
");
$stmt->execute([$qtdConf, $situacao, $nota, $conferidoPorDb, $id, $loteId]);

header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Item atualizado!'));
exit;
