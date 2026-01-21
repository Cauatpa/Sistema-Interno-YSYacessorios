<?php
// actions/lote_recebimento_add.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

// ATENÇÃO: ordem correta -> (token, formId)
if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_recebimento_add')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido. Recarregue a página.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

// valida lote existe
$stmtL = $pdo->prepare("SELECT id, status, competencia FROM lotes WHERE id = ? LIMIT 1");
$stmtL->execute([$loteId]);
$lote = $stmtL->fetch(PDO::FETCH_ASSOC);

if (!$lote) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote não encontrado.'));
    exit;
}

// se quiser travar quando fechado (opcional):
// if ((string)$lote['status'] === 'fechado') {
//     header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Lote fechado. Não é possível criar recebimentos.'));
//     exit;
// }

$dataHora = trim((string)($_POST['data_hora'] ?? ''));
$volumeLabel = trim((string)($_POST['volume_label'] ?? ''));
$rastreio = trim((string)($_POST['rastreio'] ?? ''));
$nota = trim((string)($_POST['nota'] ?? ''));

// normaliza
$volumeLabel = ($volumeLabel === '') ? null : $volumeLabel;
$rastreio = ($rastreio === '') ? null : $rastreio;
$nota = ($nota === '') ? null : $nota;

// datetime-local vem "YYYY-MM-DDTHH:MM"
if ($dataHora === '') {
    $dataHora = date('Y-m-d H:i:s');
} else {
    $dataHora = str_replace('T', ' ', $dataHora) . ':00';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dataHora);
    if (!$dt) {
        header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Data/Hora inválida.'));
        exit;
    }
    $dataHora = $dt->format('Y-m-d H:i:s');
}

$recebidoPor = (int)($u['id'] ?? 0);
if ($recebidoPor <= 0) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Usuário inválido.'));
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // IMPORTANTE: esse INSERT pressupõe que a tabela tem a coluna "nota"
    $stmt = $pdo->prepare("
        INSERT INTO lote_recebimentos (lote_id, data_hora, recebido_por, volume_label, rastreio, nota)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$loteId, $dataHora, $recebidoPor, $volumeLabel, $rastreio, $nota]);

    $newRid = (int)$pdo->lastInsertId();

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $newRid . '&toast=' . urlencode('Recebimento criado!'));
    exit;
} catch (Throwable $e) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/../storage_recebimentos_error.log', $log, FILE_APPEND);

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Erro ao salvar recebimento. Veja storage_recebimentos_error.log'));
    exit;
}
