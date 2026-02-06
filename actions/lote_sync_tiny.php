<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/env.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../services/TinyClient.php';

auth_session_start();
auth_require_role('operador');
csrf_session_start();

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_tiny_sync')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId        = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);
$mode          = (string)($_POST['mode'] ?? 'only_null'); // only_null | replace

if ($loteId <= 0 || $recebimentoId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Dados inválidos.'));
    exit;
}

if (!in_array($mode, ['only_null', 'replace'], true)) {
    $mode = 'only_null';
}

// ✅ token via env()
$tinyToken = env('TINY_TOKEN');

if (!$tinyToken) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Tiny não configurado (TINY_TOKEN ausente).'));
    exit;
}

$client = new TinyClient($tinyToken);

// itens do recebimento
$stmtItens = $pdo->prepare("
    SELECT id, produto_nome, variacao, qtd_conferida, tiny_id, tiny_codigo, tiny_nome
    FROM lote_itens
    WHERE lote_id = ? AND recebimento_id = ?
    ORDER BY id ASC
");
$stmtItens->execute([$loteId, $recebimentoId]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$updated = $mapped = $skipped = $pending = $errors = 0;
$pendentes = [];

$stmtUpdTiny = $pdo->prepare("
    UPDATE lote_itens
    SET tiny_id = ?, tiny_codigo = ?, tiny_nome = ?, atualizado_em = NOW()
    WHERE id = ? AND lote_id = ? AND recebimento_id = ?
    LIMIT 1
");

$stmtUpdConf = $pdo->prepare("
    UPDATE lote_itens
    SET qtd_conferida = ?, atualizado_em = NOW()
    WHERE id = ? AND lote_id = ? AND recebimento_id = ?
    LIMIT 1
");

try {
    $pdo->beginTransaction();

    foreach ($itens as $li) {
        $idItem  = (int)$li['id'];
        $nome    = trim((string)$li['produto_nome']);
        $variacao = trim((string)$li['variacao']);

        if ($mode === 'only_null' && $li['qtd_conferida'] !== null && $li['qtd_conferida'] !== '') {
            $skipped++;
            continue;
        }

        $tinyId = trim((string)($li['tiny_id'] ?? ''));

        if ($tinyId === '') {
            $resp = $client->pesquisarProdutos($nome, 1);
            $ret  = $resp['retorno'] ?? null;

            if (!is_array($ret) || ($ret['status'] ?? '') !== 'OK') {
                $errors++;
                continue;
            }

            $lista = $ret['produtos'] ?? [];
            if (!$lista) {
                $pending++;
                continue;
            }

            $p = TinyClient::pickProdutoPorVariacao($lista, $variacao);
            if (!$p || empty($p['id'])) {
                $pending++;
                continue;
            }

            $tinyId = (string)$p['id'];

            $stmtUpdTiny->execute([
                $tinyId,
                (string)($p['codigo'] ?? ''),
                (string)($p['nome'] ?? ''),
                $idItem,
                $loteId,
                $recebimentoId
            ]);

            $mapped++;
        }

        $resp2 = $client->obterEstoquePorId($tinyId);
        $ret2  = $resp2['retorno'] ?? null;

        if (!is_array($ret2) || ($ret2['status'] ?? '') !== 'OK') {
            $errors++;
            continue;
        }

        $saldo = TinyClient::saldoUsavel((array)($ret2['produto'] ?? []));
        $stmtUpdConf->execute([$saldo, $idItem, $loteId, $recebimentoId]);
        $updated++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Erro Tiny: ' . $e->getMessage()));
    exit;
}

audit_log(
    $pdo,
    'sync',
    'lote_tiny',
    $loteId,
    [
        'recebimento_id' => $recebimentoId,
        'mode' => $mode,
        'updated' => $updated,
        'mapped' => $mapped,
        'skipped' => $skipped,
        'pending' => $pending,
        'errors' => $errors,
    ],
    null,
    null,
    true,
    'lote_tiny_sync',
    "Sync Tiny lote #{$loteId} rec #{$recebimentoId}"
);

$toast = "Tiny ✅ Atualizados: {$updated} | Mapeados: {$mapped} | Ignorados: {$skipped} | Pendentes: {$pending} | Erros: {$errors}";
header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode($toast));
exit;
