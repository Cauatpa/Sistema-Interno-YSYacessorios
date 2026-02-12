<?php
// actions/lote_edit.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('admin')) {
    http_response_code(403);
    exit('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_edit')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

// bloqueia se lote fechado
$stmtSt = $pdo->prepare("SELECT status FROM lotes WHERE id = ? LIMIT 1");
$stmtSt->execute([$loteId]);
$statusAtual = (string)($stmtSt->fetchColumn() ?: '');
if ($statusAtual === 'fechado') {
    header('Location: ../lote.php?id=' . $loteId . '&toast=' . urlencode('Lote fechado. Reabra para editar.'));
    exit;
}

// ---- dados do lote (colunas REAIS)
$codigo = trim((string)($_POST['codigo'] ?? ''));
$dataReceb = trim((string)($_POST['data_recebimento'] ?? '')); // YYYY-MM-DD ou vazio
$fornecedor = trim((string)($_POST['fornecedor'] ?? ''));
$obs = trim((string)($_POST['observacoes'] ?? ''));

$status = (string)($_POST['status'] ?? 'aberto');
$allowStatus = ['aberto', 'conferido', 'fechado'];
if (!in_array($status, $allowStatus, true)) $status = 'aberto';

// normaliza NULLs
$codigoDb = $codigo === '' ? null : $codigo;
$fornecedorDb = $fornecedor === '' ? null : $fornecedor;
$obsDb = $obs === '' ? null : $obs;

// valida data (simples)
$dataRecebDb = null;
if ($dataReceb !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $dataReceb);
    if (!$dt) {
        header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Data de recebimento inválida.'));
        exit;
    }
    $dataRecebDb = $dataReceb;
}

// ---- dados do recebimento atual (opcional)
$recDataHora = trim((string)($_POST['rec_data_hora'] ?? '')); // YYYY-MM-DDTHH:MM ou vazio
$recVolume   = trim((string)($_POST['rec_volume_label'] ?? ''));
$recRastreio = trim((string)($_POST['rec_rastreio'] ?? ''));
$recNota     = trim((string)($_POST['rec_nota'] ?? ''));
$recObs      = trim((string)($_POST['rec_observacoes'] ?? ''));

$recVolumeDb   = $recVolume === '' ? null : $recVolume;
$recRastreioDb = $recRastreio === '' ? null : $recRastreio;
$recNotaDb     = $recNota === '' ? null : $recNota;
$recObsDb      = $recObs === '' ? null : $recObs;

$recDataHoraDb = null;
if ($recDataHora !== '') {
    // vem do input datetime-local: "YYYY-MM-DDTHH:MM"
    $dt2 = DateTime::createFromFormat('Y-m-d\TH:i', $recDataHora);
    if (!$dt2) {
        header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Data/hora do recebimento inválida.'));
        exit;
    }
    $recDataHoraDb = $dt2->format('Y-m-d H:i:s');
}

try {
    $pdo->beginTransaction();

    // update lote
    $stmtU = $pdo->prepare("
        UPDATE lotes
        SET codigo = ?, data_recebimento = ?, fornecedor = ?, observacoes = ?, status = ?, atualizado_em = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    $stmtU->execute([$codigoDb, $dataRecebDb, $fornecedorDb, $obsDb, $status, $loteId]);

    // update recebimento (se veio id e pertence ao lote)
    if ($recebimentoId > 0) {
        $stmtChk = $pdo->prepare("SELECT id FROM lote_recebimentos WHERE id = ? AND lote_id = ? LIMIT 1");
        $stmtChk->execute([$recebimentoId, $loteId]);
        if ($stmtChk->fetchColumn()) {
            $stmtRec = $pdo->prepare("
                UPDATE lote_recebimentos
                SET data_hora = COALESCE(?, data_hora),
                    volume_label = ?,
                    rastreio = ?,
                    nota = ?,
                    observacoes = ?,
                    atualizado_em = NOW()
                WHERE id = ? AND lote_id = ?
                LIMIT 1
            ");
            $stmtRec->execute([
                $recDataHoraDb,
                $recVolumeDb,
                $recRastreioDb,
                $recNotaDb,
                $recObsDb,
                $recebimentoId,
                $loteId
            ]);
        }
    }

    $pdo->commit();
    csrf_rotate('lote_edit');

    audit_log(
        $pdo,
        'edit',
        'lote',
        $loteId,
        [
            'lote' => [
                'codigo' => $codigoDb,
                'data_recebimento' => $dataRecebDb,
                'fornecedor' => $fornecedorDb,
                'observacoes' => $obsDb,
                'status' => $status,
            ],
            'recebimento_id' => $recebimentoId,
        ],
        null,
        null,
        true,
        'lote_edit',
        "Editou lote #{$loteId}."
    );

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Lote atualizado!'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    @file_put_contents(
        __DIR__ . '/../storage_lote_edit_error.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Erro ao salvar (veja storage_lote_edit_error.log)'));
    exit;
}
