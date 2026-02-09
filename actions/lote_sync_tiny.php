<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../services/TinyClient.php';

bootstrap_app();

auth_session_start();
auth_require_role('operador');
csrf_session_start();

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_tiny_sync')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido.'));
    exit;
}

$loteId       = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);
$mode         = (string)($_POST['mode'] ?? 'baseline'); // baseline | delta

if ($loteId <= 0 || $recebimentoId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Dados inválidos.'));
    exit;
}

if (!in_array($mode, ['baseline', 'delta'], true)) {
    $mode = 'baseline';
}

// Token Tiny vindo do .env carregado no bootstrap
$tinyToken = (string)(getenv('TINY_TOKEN') ?: '');
if ($tinyToken === '') {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Tiny não configurado (sem token).'));
    exit;
}

$client = new TinyClient($tinyToken);

/**
 * Segurança operacional:
 * - baseline: só registra "antes" onde estiver NULL (idempotente)
 * - delta: exige baseline existir; se não existir, bloqueia e não atualiza nada
 */
$stmtHasBaseline = $pdo->prepare("
    SELECT COUNT(*)
    FROM lote_itens
    WHERE lote_id = ?
      AND recebimento_id = ?
      AND tiny_saldo_antes IS NOT NULL
");
$stmtHasBaseline->execute([$loteId, $recebimentoId]);
$hasBaseline = ((int)$stmtHasBaseline->fetchColumn() > 0);

if ($mode === 'delta' && !$hasBaseline) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Antes de atualizar, registre o estoque inicial (baseline) primeiro.'));
    exit;
}

// Pega itens do recebimento
$stmtItens = $pdo->prepare("
  SELECT
    id, produto_nome, variacao, qtd_conferida,
    tiny_id, tiny_codigo, tiny_nome,
    tiny_saldo_antes
  FROM lote_itens
  WHERE lote_id = ? AND recebimento_id = ?
  ORDER BY id ASC
");
$stmtItens->execute([$loteId, $recebimentoId]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$baselined = 0;
$updated   = 0;
$mapped    = 0;
$skipped   = 0;
$pending   = 0;
$errors    = 0;

$pendentes = [];

// Updates
$stmtUpdTiny = $pdo->prepare("
  UPDATE lote_itens
  SET tiny_id = ?, tiny_codigo = ?, tiny_nome = ?, atualizado_em = NOW()
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
  LIMIT 1
");

$stmtSetAntesIfNull = $pdo->prepare("
  UPDATE lote_itens
  SET tiny_saldo_antes = ?, tiny_sync_em = NOW(), atualizado_em = NOW()
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
    AND tiny_saldo_antes IS NULL
  LIMIT 1
");

$stmtSetDepoisDelta = $pdo->prepare("
  UPDATE lote_itens
  SET tiny_saldo_depois = ?, tiny_delta = ?, tiny_sync_em = NOW(), atualizado_em = NOW()
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
  LIMIT 1
");

$stmtUpdConfIfNull = $pdo->prepare("
  UPDATE lote_itens
  SET qtd_conferida = ?, atualizado_em = NOW()
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
    AND qtd_conferida IS NULL
  LIMIT 1
");

$stmtUpdConfForce = $pdo->prepare("
  UPDATE lote_itens
  SET qtd_conferida = ?, atualizado_em = NOW()
  WHERE id = ? AND lote_id = ? AND recebimento_id = ?
  LIMIT 1
");

/**
 * Regras:
 * baseline:
 *  - se tiny_saldo_antes já existe, não mexe (skipped)
 *  - se não existe, registra
 *
 * delta:
 *  - se item não tem tiny_saldo_antes, não atualiza (pendente)
 *  - delta < 0 => pendente (estoque mexeu no meio)
 *  - atualiza tiny_saldo_depois + tiny_delta
 *  - preenche qtd_conferida:
 *      - por padrão: só se estiver NULL (não atropela manual)
 *      - se você quiser forçar sempre, trocamos por $stmtUpdConfForce
 */

try {
    $pdo->beginTransaction();

    foreach ($itens as $li) {
        $idItem   = (int)$li['id'];
        $nome     = trim((string)$li['produto_nome']);
        $variacao = trim((string)$li['variacao']);

        if ($nome === '') {
            $errors++;
            $pendentes[] = ['id' => $idItem, 'nome' => $nome, 'variacao' => $variacao, 'motivo' => 'Produto_nome vazio no item'];
            continue;
        }

        // ---------- 1) Mapear tiny_id se necessário ----------
        $tinyId = trim((string)($li['tiny_id'] ?? ''));
        if ($tinyId === '') {
            $resp = $client->pesquisarProdutos($nome, 1);
            $ret  = $resp['retorno'] ?? null;

            if (!is_array($ret) || ($ret['status'] ?? '') !== 'OK') {
                $errors++;
                $pendentes[] = ['id' => $idItem, 'nome' => $nome, 'variacao' => $variacao, 'motivo' => 'Falha na pesquisa Tiny'];
                continue;
            }

            $lista = $ret['produtos'] ?? [];
            if (!$lista) {
                $pending++;
                $pendentes[] = ['id' => $idItem, 'nome' => $nome, 'variacao' => $variacao, 'motivo' => 'Não encontrado no Tiny'];
                continue;
            }

            $p = TinyClient::pickProdutoPorVariacao($lista, $variacao);
            if (!$p || empty($p['id'])) {
                $pending++;
                $pendentes[] = ['id' => $idItem, 'nome' => $nome, 'variacao' => $variacao, 'motivo' => 'Ambíguo/sem match'];
                continue;
            }

            $tinyId     = (string)$p['id'];
            $tinyCodigo = (string)($p['codigo'] ?? '');
            $tinyNome   = (string)($p['nome'] ?? '');

            $stmtUpdTiny->execute([$tinyId, $tinyCodigo, $tinyNome, $idItem, $loteId, $recebimentoId]);
            $mapped++;
        }

        // ---------- 2) Obter saldo atual ----------
        $resp2 = $client->obterEstoquePorId($tinyId);
        $ret2  = $resp2['retorno'] ?? null;

        if (!is_array($ret2) || ($ret2['status'] ?? '') !== 'OK') {
            $errors++;
            $pendentes[] = ['id' => $idItem, 'nome' => $nome, 'variacao' => $variacao, 'motivo' => 'Falha ao obter estoque'];
            continue;
        }

        $produtoEst = $ret2['produto'] ?? [];
        $saldoAgora = TinyClient::saldoUsavel(is_array($produtoEst) ? $produtoEst : []);

        // ---------- 3) MODE: baseline ----------
        $antesRaw = $li['tiny_saldo_antes'];

        if ($mode === 'baseline') {
            if ($antesRaw !== null && $antesRaw !== '') {
                $skipped++;
                continue;
            }

            $stmtSetAntesIfNull->execute([$saldoAgora, $idItem, $loteId, $recebimentoId]);
            if ($stmtSetAntesIfNull->rowCount() > 0) {
                $baselined++;
            } else {
                $skipped++;
            }
            continue;
        }

        // ---------- 4) MODE: delta ----------
        if ($antesRaw === null || $antesRaw === '') {
            $pending++;
            $pendentes[] = ['id' => $idItem, 'nome' => $nome, 'variacao' => $variacao, 'motivo' => 'Sem baseline (tiny_saldo_antes NULL)'];
            continue;
        }

        $antes = (int)$antesRaw;
        $delta = $saldoAgora - $antes;

        // delta negativo: estoque foi alterado no meio (venda/ajuste)
        if ($delta < 0) {
            $pending++;
            $stmtSetDepoisDelta->execute([$saldoAgora, $delta, $idItem, $loteId, $recebimentoId]);
            $pendentes[] = [
                'id' => $idItem,
                'nome' => $nome,
                'variacao' => $variacao,
                'motivo' => "Delta negativo ({$delta}) — estoque mudou no meio"
            ];
            continue;
        }

        // salva depois + delta
        $stmtSetDepoisDelta->execute([$saldoAgora, $delta, $idItem, $loteId, $recebimentoId]);

        // preenche qtd_conferida (só se NULL, para não atropelar conferência manual)
        $stmtUpdConfIfNull->execute([$delta, $idItem, $loteId, $recebimentoId]);

        $updated++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Erro Tiny: ' . $e->getMessage()));
    exit;
}

// Auditoria (amostra de pendentes)
audit_log(
    $pdo,
    'sync',
    'lote_tiny',
    $loteId,
    [
        'recebimento_id' => $recebimentoId,
        'mode' => $mode,
        'updated' => $updated,
        'baselined' => $baselined,
        'mapped' => $mapped,
        'skipped' => $skipped,
        'pending' => $pending,
        'errors' => $errors,
        'pendentes_sample' => array_slice($pendentes, 0, 20),
    ],
    null,
    null,
    true,
    'lote_tiny_sync',
    "Tiny Sync lote #{$loteId} rec #{$recebimentoId} — mode {$mode} | upd {$updated} | base {$baselined} | map {$mapped} | pend {$pending} | err {$errors}."
);

if ($mode === 'baseline') {
    $toast = "Tiny 📌 Baseline registrado: {$baselined} | Já tinham baseline: {$skipped} | Mapeados: {$mapped} | Pendentes: {$pending} | Erros: {$errors}";
} else {
    $toast = "Tiny 🔄 Conferidos atualizados: {$updated} | Mapeados: {$mapped} | Pendentes: {$pending} | Erros: {$errors}";
}

header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode($toast));
exit;
