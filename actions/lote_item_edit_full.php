<?php
// actions/lote_item_edit_full_group.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';

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
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

// Bloqueio se lote estiver fechado
$stmtL = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmtL->execute([$loteId]);
$statusLote = (string)($stmtL->fetchColumn() ?: '');
if ($statusLote === 'fechado') {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Lote fechado. Reabra para editar.'
    ]));
    exit;
}

$idPrata = (int)($_POST['id_prata'] ?? 0);
$idOuro  = (int)($_POST['id_ouro'] ?? 0);

$produtoId = (int)($_POST['produto_id'] ?? 0);
if ($produtoId <= 0) {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Produto inválido.'
    ]));
    exit;
}

$temPrata = ((int)($_POST['tem_prata'] ?? 0) === 1);
$temOuro  = ((int)($_POST['tem_ouro'] ?? 0) === 1);

if (!$temPrata && !$temOuro) {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Selecione Prata e/ou Ouro.'
    ]));
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

// Pega nome do produto
$stmtP = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? LIMIT 1");
$stmtP->execute([$produtoId]);
$produtoNome = (string)$stmtP->fetchColumn();
if ($produtoNome === '') {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Produto inválido.'
    ]));
    exit;
}

try {
    // BEFORE
    $idsBefore = array_values(array_filter([$idPrata, $idOuro], fn($x) => $x > 0));
    $before = [];
    if ($idsBefore) {
        $place = implode(',', array_fill(0, count($idsBefore), '?'));
        $stmtB = $pdo->prepare("
            SELECT id, lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota
            FROM lote_itens
            WHERE lote_id = ? AND id IN ($place)
        ");
        $stmtB->execute(array_merge([$loteId], $idsBefore));
        $before = $stmtB->fetchAll(PDO::FETCH_ASSOC);
    }

    $pdo->beginTransaction();

    $checkOwn = $pdo->prepare("SELECT id FROM lote_itens WHERE id = ? AND lote_id = ? LIMIT 1");

    $stmtUp = $pdo->prepare("
        UPDATE lote_itens
        SET produto_id = ?, produto_nome = ?, variacao = ?, qtd_prevista = ?, qtd_conferida = ?, situacao = ?, nota = ?
        WHERE id = ? AND lote_id = ?
        LIMIT 1
    ");

    $stmtIns = $pdo->prepare("
        INSERT INTO lote_itens (lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtDel = $pdo->prepare("DELETE FROM lote_itens WHERE id = ? AND lote_id = ? LIMIT 1");

    // PRATA
    if ($idPrata > 0) {
        $checkOwn->execute([$idPrata, $loteId]);
        if (!$checkOwn->fetchColumn()) throw new RuntimeException('Item Prata não pertence a este lote.');
    }

    if ($temPrata) {
        if ($idPrata > 0) {
            $stmtUp->execute([$produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $notaDb, $idPrata, $loteId]);
        } else {
            $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $notaDb]);
            $idPrata = (int)$pdo->lastInsertId();
        }
    } else {
        if ($idPrata > 0) {
            $stmtDel->execute([$idPrata, $loteId]);
            $idPrata = 0;
        }
    }

    // OURO
    if ($idOuro > 0) {
        $checkOwn->execute([$idOuro, $loteId]);
        if (!$checkOwn->fetchColumn()) throw new RuntimeException('Item Ouro não pertence a este lote.');
    }

    if ($temOuro) {
        if ($idOuro > 0) {
            $stmtUp->execute([$produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $notaDb, $idOuro, $loteId]);
        } else {
            $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $notaDb]);
            $idOuro = (int)$pdo->lastInsertId();
        }
    } else {
        if ($idOuro > 0) {
            $stmtDel->execute([$idOuro, $loteId]);
            $idOuro = 0;
        }
    }

    $pdo->commit();

    // AFTER
    $idsAfter = array_values(array_filter([$idPrata, $idOuro], fn($x) => $x > 0));
    $after = [];
    if ($idsAfter) {
        $place2 = implode(',', array_fill(0, count($idsAfter), '?'));
        $stmtA = $pdo->prepare("
            SELECT id, lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota
            FROM lote_itens
            WHERE lote_id = ? AND id IN ($place2)
        ");
        $stmtA->execute(array_merge([$loteId], $idsAfter));
        $after = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    }

    audit_log(
        $pdo,
        'update',
        'lote_item_group',
        ($idPrata > 0 ? $idPrata : ($idOuro > 0 ? $idOuro : null)),
        [
            'lote_id' => $loteId,
            'recebimento_id' => $recebimentoId,
            'produto_id' => $produtoId,
            'produto_nome' => $produtoNome,
            'tem_prata' => $temPrata ? 1 : 0,
            'tem_ouro' => $temOuro ? 1 : 0,
        ],
        ['items' => $before],
        ['items' => $after],
        true,
        'lote_item_edit_full_group',
        "Editou completo item(ns) do lote #{$loteId} (recebimento #{$recebimentoId})."
    );

    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Editado completo!'
    ]));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    audit_log(
        $pdo,
        'update',
        'lote_item_group',
        ($idPrata > 0 ? $idPrata : ($idOuro > 0 ? $idOuro : null)),
        [
            'lote_id' => $loteId,
            'recebimento_id' => $recebimentoId,
            'error' => $e->getMessage()
        ],
        null,
        null,
        false,
        'lote_item_edit_full_group_error',
        'Erro ao editar completo item(ns) do lote.'
    );

    $log = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/../storage_lote_edit_full_group_error.log', $log, FILE_APPEND);

    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Erro ao editar completo. Veja storage_lote_edit_full_group_error.log'
    ]));
    exit;
}
