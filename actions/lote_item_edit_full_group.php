<?php
// actions/lote_item_edit_full_group.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    http_response_code(403);
    exit('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_edit_full_group')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

if ($loteId <= 0 || $recebimentoId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Dados inválidos.'));
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

if ($idPrata <= 0 && $idOuro <= 0) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Nada para editar (sem Prata/Ouro).'));
    exit;
}

// Situação / nota (igual para ambas)
$situacao = (string)($_POST['situacao'] ?? 'ok');
$allowed = ['ok', 'faltando', 'a_mais', 'banho_trocado', 'quebra', 'outro'];
if (!in_array($situacao, $allowed, true)) $situacao = 'ok';

$nota = trim((string)($_POST['nota'] ?? ''));
$notaDb = ($nota === '') ? null : $nota;

// Helpers
$toNullableInt = static function ($raw): ?int {
    if ($raw === null) return null;
    if (is_string($raw)) {
        $raw = trim($raw);
        if ($raw === '') return null;
        if (!preg_match('/^-?\d+$/', $raw)) return null;
        $n = (int)$raw;
        return $n < 0 ? null : $n;
    }
    if (is_int($raw)) return $raw < 0 ? null : $raw;
    if (is_float($raw)) {
        $n = (int)round($raw);
        return $n < 0 ? null : $n;
    }
    return null;
};

$resolveUserId = static function (PDO $pdo, string $text): ?int {
    $t = trim($text);
    if ($t === '') return null;

    // tenta por usuario exato
    $stmt = $pdo->prepare("SELECT id FROM users WHERE usuario = ? LIMIT 1");
    $stmt->execute([$t]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    // tenta por nome exato
    $stmt = $pdo->prepare("SELECT id FROM users WHERE nome = ? LIMIT 1");
    $stmt->execute([$t]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    // tenta por começo do nome (pra ajudar)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE nome LIKE ? ORDER BY nome ASC LIMIT 1");
    $stmt->execute([$t . '%']);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;

    return null;
};

// Dados prata
$qPrevPrata = max(0, (int)($_POST['qtd_prevista_prata'] ?? 0));
$qPrevOuro  = max(0, (int)($_POST['qtd_prevista_ouro'] ?? 0));

$qConfPrata = $toNullableInt($_POST['qtd_conferida_prata'] ?? null);
$qConfOuro  = $toNullableInt($_POST['qtd_conferida_ouro'] ?? null);

$conferidoPorPrataTxt = (string)($_POST['conferido_por_prata'] ?? '');
$conferidoPorOuroTxt  = (string)($_POST['conferido_por_ouro'] ?? '');

$conferidoPorPrataId = $resolveUserId($pdo, $conferidoPorPrataTxt);
$conferidoPorOuroId  = $resolveUserId($pdo, $conferidoPorOuroTxt);

// Verifica pertencimento ao lote + recebimento
$checkOwn = $pdo->prepare("
    SELECT id FROM lote_itens
    WHERE id = ?
      AND lote_id = ?
      AND recebimento_id = ?
    LIMIT 1
");

// Update
$stmtUp = $pdo->prepare("
    UPDATE lote_itens
    SET qtd_prevista = ?,
        qtd_conferida = ?,
        situacao = ?,
        nota = ?,
        conferido_por = ?,
        atualizado_em = NOW()
    WHERE id = ?
      AND lote_id = ?
      AND recebimento_id = ?
    LIMIT 1
");

try {
    $pdo->beginTransaction();

    if ($idPrata > 0) {
        $checkOwn->execute([$idPrata, $loteId, $recebimentoId]);
        if (!$checkOwn->fetchColumn()) {
            throw new RuntimeException('Item Prata não pertence a este lote/recebimento.');
        }

        $stmtUp->execute([
            $qPrevPrata,
            $qConfPrata,
            $situacao,
            $notaDb,
            $conferidoPorPrataId,
            $idPrata,
            $loteId,
            $recebimentoId
        ]);
    }

    if ($idOuro > 0) {
        $checkOwn->execute([$idOuro, $loteId, $recebimentoId]);
        if (!$checkOwn->fetchColumn()) {
            throw new RuntimeException('Item Ouro não pertence a este lote/recebimento.');
        }

        $stmtUp->execute([
            $qPrevOuro,
            $qConfOuro,
            $situacao,
            $notaDb,
            $conferidoPorOuroId,
            $idOuro,
            $loteId,
            $recebimentoId
        ]);
    }

    $pdo->commit();

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Editado completo!'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    $log = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/../storage_lote_edit_full_group_error.log', $log, FILE_APPEND);

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Erro ao editar completo. Veja storage_lote_edit_full_group_error.log'));
    exit;
}
