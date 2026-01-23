<?php
// actions/lote_marcar_fechado.php

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

if (!csrf_validate('lote_marcar_fechado', $_POST['csrf_token'] ?? '')) {
    audit_log(
        $pdo,
        'close',
        'lote',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao fechar lote (sessão expirada/CSRF inválido).'
    );

    header('Location: ../lotes.php?toast=' . urlencode('Sessão expirada. Tente novamente.'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

// BEFORE
$stmtB = $pdo->prepare("SELECT id, codigo, status, competencia, fornecedor, observacoes FROM lotes WHERE id = ? LIMIT 1");
$stmtB->execute([$id]);
$before = $stmtB->fetch(PDO::FETCH_ASSOC);

if (!$before) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote não encontrado.'));
    exit;
}

try {
    $stmtU = $pdo->prepare("UPDATE lotes SET status='fechado' WHERE id = ? LIMIT 1");
    $stmtU->execute([$id]);

    // AFTER
    $stmtA = $pdo->prepare("SELECT id, codigo, status, competencia, fornecedor, observacoes FROM lotes WHERE id = ? LIMIT 1");
    $stmtA->execute([$id]);
    $after = $stmtA->fetch(PDO::FETCH_ASSOC);

    audit_log(
        $pdo,
        'close',
        'lote',
        $id,
        ['lote_id' => $id],
        $before ?: null,
        $after ?: null,
        true,
        'lote_marcar_fechado',
        "Finalizou o lote #{$id} (" . (string)($before['codigo'] ?? '') . ")."
    );

    header('Location: ../lote.php?id=' . $id . '&toast=' . urlencode('Lote FECHADO!'));
    exit;
} catch (Throwable $e) {
    audit_log(
        $pdo,
        'close',
        'lote',
        $id,
        ['lote_id' => $id, 'error' => $e->getMessage()],
        $before ?: null,
        null,
        false,
        'lote_marcar_fechado_error',
        'Erro ao fechar lote.'
    );

    header('Location: ../lote.php?id=' . $id . '&toast=' . urlencode('Erro ao fechar lote.'));
    exit;
}
