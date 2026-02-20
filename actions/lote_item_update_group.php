<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    exit('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_update_group')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId        = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

$idPrata = (int)($_POST['id_prata'] ?? 0);
$idOuro  = (int)($_POST['id_ouro'] ?? 0);

if ($loteId <= 0 || $recebimentoId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Dados inválidos.'));
    exit;
}

if ($idPrata <= 0 && $idOuro <= 0) {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Nada para salvar.',
    ]));
    exit;
}

// ----------- Inputs por variação -----------
$qConfPrataRaw = trim((string)($_POST['qtd_conferida_prata'] ?? ''));
$qConfOuroRaw  = trim((string)($_POST['qtd_conferida_ouro'] ?? ''));

$qConfPrata = ($qConfPrataRaw === '') ? null : (int)$qConfPrataRaw;
$qConfOuro  = ($qConfOuroRaw  === '') ? null : (int)$qConfOuroRaw;

$conferidoPrata = trim((string)($_POST['conferido_por_prata'] ?? ''));
$conferidoOuro  = trim((string)($_POST['conferido_por_ouro'] ?? ''));

$conferidoPrataDb = ($conferidoPrata === '') ? null : $conferidoPrata;
$conferidoOuroDb  = ($conferidoOuro  === '') ? null : $conferidoOuro;

$situacaoPrata = trim((string)($_POST['situacao_prata'] ?? ''));
$situacaoOuro  = trim((string)($_POST['situacao_ouro'] ?? ''));

$notaPrata = trim((string)($_POST['nota_prata'] ?? ''));
$notaOuro  = trim((string)($_POST['nota_ouro'] ?? ''));

$notaPrataDb = ($notaPrata === '') ? null : $notaPrata;
$notaOuroDb  = ($notaOuro  === '') ? null : $notaOuro;

// ----------- Validação situação -----------
$allowedSit = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if ($situacaoPrata === '' || !in_array($situacaoPrata, $allowedSit, true)) $situacaoPrata = 'ok';
if ($situacaoOuro  === '' || !in_array($situacaoOuro,  $allowedSit, true)) $situacaoOuro  = 'ok';

// ----------- BEFORE/AFTER (auditoria) -----------
$stmtSel = $pdo->prepare("
  SELECT
    id, produto_nome, variacao,
    qtd_prevista, qtd_conferida,
    situacao, nota, conferido_por,
    lote_id, recebimento_id
  FROM lote_itens
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
  LIMIT 1
");

$beforeRows = ['prata' => null, 'ouro' => null];

if ($idPrata > 0) {
    $stmtSel->execute([$idPrata, $loteId, $recebimentoId]);
    $beforeRows['prata'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}
if ($idOuro > 0) {
    $stmtSel->execute([$idOuro, $loteId, $recebimentoId]);
    $beforeRows['ouro'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ----------- UPDATE (por variação) -----------
$stmtUpd = $pdo->prepare("
  UPDATE lote_itens
  SET
    qtd_conferida = ?,
    conferido_por = ?,
    situacao = ?,
    nota = ?,
    atualizado_em = NOW()
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
  LIMIT 1
");

try {
    $pdo->beginTransaction();

    if ($idPrata > 0) {
        $stmtUpd->execute([
            $qConfPrata,
            $conferidoPrataDb,
            $situacaoPrata,
            $notaPrataDb,
            $idPrata,
            $loteId,
            $recebimentoId
        ]);
    }

    if ($idOuro > 0) {
        $stmtUpd->execute([
            $qConfOuro,
            $conferidoOuroDb,
            $situacaoOuro,
            $notaOuroDb,
            $idOuro,
            $loteId,
            $recebimentoId
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Erro ao salvar: ' . $e->getMessage(),
    ]));
    exit;
}

// ----------- AFTER -----------
$afterRows = ['prata' => null, 'ouro' => null];

if ($idPrata > 0) {
    $stmtSel->execute([$idPrata, $loteId, $recebimentoId]);
    $afterRows['prata'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}
if ($idOuro > 0) {
    $stmtSel->execute([$idOuro, $loteId, $recebimentoId]);
    $afterRows['ouro'] = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Auditoria (um log único para a “linha agrupada”)
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
