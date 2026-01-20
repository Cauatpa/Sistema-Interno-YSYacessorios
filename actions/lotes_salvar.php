<?php
// actions/lotes_salvar.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

$competencia = trim((string)($_POST['competencia'] ?? ''));
if (!competencia_valida($competencia)) {
    $competencia = competencia_atual();
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lotes_salvar')) {
    header('Location: ../lotes.php?competencia=' . urlencode($competencia) . '&toast=' . urlencode('CSRF inválido. Recarregue a página e tente novamente.'));
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $codigo = trim((string)($_POST['codigo'] ?? ''));
    $data_recebimento = trim((string)($_POST['data_recebimento'] ?? ''));
    $fornecedor = trim((string)($_POST['fornecedor'] ?? ''));
    $observacoes = trim((string)($_POST['observacoes'] ?? ''));

    if ($codigo === '' || mb_strlen($codigo) > 60) {
        header('Location: ../lotes.php?competencia=' . urlencode($competencia) . '&toast=' . urlencode('Código inválido.'));
        exit;
    }

    // data opcional
    if ($data_recebimento !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $data_recebimento);
        if (!$dt || $dt->format('Y-m-d') !== $data_recebimento) {
            header('Location: ../lotes.php?competencia=' . urlencode($competencia) . '&toast=' . urlencode('Data inválida.'));
            exit;
        }
    } else {
        $data_recebimento = null;
    }

    if ($fornecedor === '') $fornecedor = null;
    if ($observacoes === '') $observacoes = null;

    $criadoPor = (int)($u['id'] ?? 0);
    if ($criadoPor <= 0) {
        header('Location: ../lotes.php?competencia=' . urlencode($competencia) . '&toast=' . urlencode('Usuário inválido.'));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO lotes (competencia, codigo, data_recebimento, fornecedor, observacoes, status, criado_por)
        VALUES (?, ?, ?, ?, ?, 'aberto', ?)
    ");
    $stmt->execute([$competencia, $codigo, $data_recebimento, $fornecedor, $observacoes, $criadoPor]);

    $newId = (int)$pdo->lastInsertId();

    header('Location: ../lotes.php?competencia=' . urlencode($competencia)
        . '&toast=' . urlencode('Lote criado com sucesso!')
        . '&highlight_id=' . $newId);
    exit;
} catch (Throwable $e) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/../storage_lotes_error.log', $log, FILE_APPEND);

    header('Location: ../lotes.php?competencia=' . urlencode($competencia)
        . '&toast=' . urlencode('Erro ao salvar. Veja storage_lotes_error.log'));
    exit;
}
