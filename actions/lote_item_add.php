<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

auth_session_start();
auth_require_role('operador');
csrf_session_start();

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_add')) {
    header('Location: ../lotes.php?toast=' . urlencode('Sessão expirada. Tente novamente.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

if ($loteId <= 0 || $recebimentoId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Dados inválidos.'));
    exit;
}

// bloqueia se lote fechado
$stmt = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmt->execute([$loteId]);
if ((string)$stmt->fetchColumn() === 'fechado') {
    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Lote fechado. Edição bloqueada.'));
    exit;
}

$produtoNome = trim((string)($_POST['produto_nome'] ?? ''));
if ($produtoNome === '') {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Informe o nome do produto.'));
    exit;
}

$temPrata = (int)($_POST['tem_prata'] ?? 0) === 1;
$temOuro  = (int)($_POST['tem_ouro'] ?? 0) === 1;

if (!$temPrata && !$temOuro) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Selecione Prata e/ou Ouro.'));
    exit;
}

$qPrevPrata = max(0, (int)($_POST['qtd_prevista_prata'] ?? 0));
$qPrevOuro  = max(0, (int)($_POST['qtd_prevista_ouro'] ?? 0));

$qConfPrata = $_POST['qtd_conferida_prata'] ?? null;
$qConfOuro  = $_POST['qtd_conferida_ouro'] ?? null;

$qConfPrata = ($qConfPrata === '' || $qConfPrata === null) ? null : (int)$qConfPrata;
$qConfOuro  = ($qConfOuro === ''  || $qConfOuro === null)  ? null : (int)$qConfOuro;

$situacao = (string)($_POST['situacao'] ?? 'ok');
$allow = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if (!in_array($situacao, $allow, true)) $situacao = 'ok';

$nota = trim((string)($_POST['nota'] ?? ''));
if ($nota === '') $nota = null;

// tenta linkar no produtos (se existir), mas NÃO OBRIGA
$produtoId = null;
$stmtProd = $pdo->prepare("SELECT id, nome FROM produtos WHERE nome = ? OR nome_norm = ? LIMIT 1");
$nomeNorm = norm_prod($produtoNome);
$stmtProd->execute([$produtoNome, $nomeNorm]);
if ($p = $stmtProd->fetch(PDO::FETCH_ASSOC)) {
    $produtoId = (int)$p['id'];
    $produtoNome = (string)$p['nome']; // padroniza com nome do banco, se existir
}

$stmtIns = $pdo->prepare("
  INSERT INTO lote_itens
    (lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
  VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$pdo->beginTransaction();

try {
    if ($temPrata) {
        $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $nota]);
    }
    if ($temOuro) {
        $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $nota]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Erro ao adicionar: ' . $e->getMessage()));
    exit;
}

// se apertou "próximo"
$next = (int)($_POST['next'] ?? 0) === 1;
$qs = 'id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId;
if ($next) $qs .= '&open_item=1';

header('Location: ../lote.php?' . $qs . '&toast=' . urlencode('Item adicionado!'));
exit;

function norm_prod(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    if (is_string($t) && $t !== '') $s = $t;
    $s = preg_replace('/[^a-z0-9 ]/', '', $s) ?? $s;
    return trim($s);
}
