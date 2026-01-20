<?php
// actions/lote_item_edit_full_group.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_edit_full_group')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

// Bloqueio se lote estiver fechado
$stmtL = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmtL->execute([$loteId]);
$statusLote = (string)($stmtL->fetchColumn() ?: '');
if ($statusLote === 'fechado') {
    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Lote fechado. Reabra para editar.'));
    exit;
}

$idPrata = (int)($_POST['id_prata'] ?? 0);
$idOuro  = (int)($_POST['id_ouro'] ?? 0);

$produtoId = (int)($_POST['produto_id'] ?? 0);
if ($produtoId <= 0) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Produto inválido.'));
    exit;
}

$temPrata = (int)($_POST['tem_prata'] ?? 0) === 1;
$temOuro  = (int)($_POST['tem_ouro'] ?? 0) === 1;

if (!$temPrata && !$temOuro) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Selecione Prata e/ou Ouro.'));
    exit;
}

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

$notaDb = ($nota === '') ? null : $nota;

// Pega nome do produto (congela no histórico)
$stmtP = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? LIMIT 1");
$stmtP->execute([$produtoId]);
$produtoNome = (string)$stmtP->fetchColumn();
if ($produtoNome === '') {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Produto inválido.'));
    exit;
}

try {
    $pdo->beginTransaction();

    // helpers: garante que um id pertence ao lote
    $checkOwn = $pdo->prepare("SELECT id FROM lote_itens WHERE id = ? AND lote_id = ? LIMIT 1");

    // UPDATE existente
    $stmtUp = $pdo->prepare("
        UPDATE lote_itens
        SET produto_id = ?, produto_nome = ?, variacao = ?, qtd_prevista = ?, qtd_conferida = ?, situacao = ?, nota = ?
        WHERE id = ? AND lote_id = ?
        LIMIT 1
    ");

    // INSERT novo
    $stmtIns = $pdo->prepare("
        INSERT INTO lote_itens (lote_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // DELETE (quando desmarca)
    $stmtDel = $pdo->prepare("DELETE FROM lote_itens WHERE id = ? AND lote_id = ? LIMIT 1");

    // -------- PRATA --------
    if ($idPrata > 0) {
        $checkOwn->execute([$idPrata, $loteId]);
        if (!$checkOwn->fetchColumn()) {
            throw new RuntimeException('Item Prata não pertence a este lote.');
        }
    }

    if ($temPrata) {
        if ($idPrata > 0) {
            $stmtUp->execute([$produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $notaDb, $idPrata, $loteId]);
        } else {
            $stmtIns->execute([$loteId, $produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $notaDb]);
            $idPrata = (int)$pdo->lastInsertId();
        }
    } else {
        if ($idPrata > 0) {
            $stmtDel->execute([$idPrata, $loteId]);
            $idPrata = 0;
        }
    }

    // -------- OURO --------
    if ($idOuro > 0) {
        $checkOwn->execute([$idOuro, $loteId]);
        if (!$checkOwn->fetchColumn()) {
            throw new RuntimeException('Item Ouro não pertence a este lote.');
        }
    }

    if ($temOuro) {
        if ($idOuro > 0) {
            $stmtUp->execute([$produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $notaDb, $idOuro, $loteId]);
        } else {
            $stmtIns->execute([$loteId, $produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $notaDb]);
            $idOuro = (int)$pdo->lastInsertId();
        }
    } else {
        if ($idOuro > 0) {
            $stmtDel->execute([$idOuro, $loteId]);
            $idOuro = 0;
        }
    }

    $pdo->commit();

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Editado completo!'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    $log = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/../storage_lote_edit_full_group_error.log', $log, FILE_APPEND);

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Erro ao editar completo. Veja storage_lote_edit_full_group_error.log'));
    exit;
}
