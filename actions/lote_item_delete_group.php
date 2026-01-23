<?php
// actions/lote_item_delete_group.php

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

$loteId = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_delete_group')) {
    audit_log(
        $pdo,
        'delete',
        'lote_item_group',
        null,
        ['lote_id' => $loteId, 'recebimento_id' => $recebimentoId, 'reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao excluir item do lote (CSRF inválido).'
    );

    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'CSRF inválido.'
    ]));
    exit;
}

$idPrata = (int)($_POST['id_prata'] ?? 0);
$idOuro  = (int)($_POST['id_ouro'] ?? 0);

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

$ids = [];
if ($idPrata > 0) $ids[] = $idPrata;
if ($idOuro  > 0) $ids[] = $idOuro;

if (!$ids) {
    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Nada para excluir.'
    ]));
    exit;
}

try {
    // BEFORE (somente itens que pertencem ao lote)
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmtB = $pdo->prepare("
        SELECT id, lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota
        FROM lote_itens
        WHERE lote_id = ? AND id IN ($place)
    ");
    $stmtB->execute(array_merge([$loteId], $ids));
    $before = $stmtB->fetchAll(PDO::FETCH_ASSOC);

    // DELETE
    $stmtD = $pdo->prepare("DELETE FROM lote_itens WHERE lote_id = ? AND id IN ($place)");
    $stmtD->execute(array_merge([$loteId], $ids));
    $deleted = $stmtD->rowCount();

    audit_log(
        $pdo,
        'delete',
        'lote_item_group',
        ($idPrata > 0 ? $idPrata : $idOuro),
        [
            'lote_id' => $loteId,
            'recebimento_id' => $recebimentoId,
            'ids' => $ids,
            'deleted_rows' => $deleted
        ],
        ['items' => $before],
        ['items' => []],
        true,
        'lote_item_delete_group',
        "Excluiu item(ns) do lote #{$loteId}."
    );

    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Item excluído!'
    ]));
    exit;
} catch (Throwable $e) {
    audit_log(
        $pdo,
        'delete',
        'lote_item_group',
        ($idPrata > 0 ? $idPrata : $idOuro),
        [
            'lote_id' => $loteId,
            'recebimento_id' => $recebimentoId,
            'ids' => $ids,
            'error' => $e->getMessage()
        ],
        null,
        null,
        false,
        'lote_item_delete_group_error',
        'Erro ao excluir item(ns) do lote.'
    );

    header('Location: ../lote.php?' . http_build_query([
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => 'Erro ao excluir.'
    ]));
    exit;
}
