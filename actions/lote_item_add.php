<?php
// actions/lote_item_add.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$u = auth_require_login();
csrf_session_start();

if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

if (!csrf_validate($_POST['csrf_token'] ?? '', 'lote_item_add')) {
    header('Location: ../lotes.php?toast=' . urlencode('CSRF inválido. Recarregue a página.'));
    exit;
}

// ✅ Próximo (0/1)
$wantNext = ((int)($_POST['next'] ?? 0) === 1);

$loteId = (int)($_POST['lote_id'] ?? 0);
$produtoId = (int)($_POST['produto_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

$temPrata = (int)($_POST['tem_prata'] ?? 0) === 1;
$temOuro  = (int)($_POST['tem_ouro'] ?? 0) === 1;

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

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}

if ($recebimentoId <= 0) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Selecione um recebimento antes de adicionar itens.'));
    exit;
}

// ✅ Helper de redirect padronizado (mantém recebimento selecionado e abre modal no "próximo")
$redirectBack = function (string $toastMsg) use ($loteId, $recebimentoId, $wantNext): void {
    $params = [
        'id' => $loteId,
        'edit' => 1,
        'recebimento_id' => $recebimentoId,
        'toast' => $toastMsg,
    ];
    if ($wantNext) {
        $params['open_item'] = 1;
    }
    header('Location: ../lote.php?' . http_build_query($params));
    exit;
};

// valida recebimento pertence ao lote
$stmtR = $pdo->prepare("SELECT id FROM lote_recebimentos WHERE id = ? AND lote_id = ? LIMIT 1");
$stmtR->execute([$recebimentoId, $loteId]);
if (!$stmtR->fetchColumn()) {
    $redirectBack('Recebimento inválido para este lote.');
}

if ($produtoId <= 0) {
    $redirectBack('Produto inválido.');
}

if (!$temPrata && !$temOuro) {
    $redirectBack('Selecione Prata e/ou Ouro.');
}

$stmtP = $pdo->prepare("SELECT nome FROM produtos WHERE id = ? LIMIT 1");
$stmtP->execute([$produtoId]);
$produtoNome = $stmtP->fetchColumn();
if (!$produtoNome) {
    $redirectBack('Produto inválido.');
}

$notaDb = ($nota === '') ? null : $nota;

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // IMPORTANTÍSSIMO: precisa existir a coluna recebimento_id em lote_itens
    $stmtIns = $pdo->prepare("
        INSERT INTO lote_itens (lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($temPrata) {
        $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNome, 'Prata', $qPrevPrata, $qConfPrata, $situacao, $notaDb]);
    }
    if ($temOuro) {
        $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNome, 'Ouro', $qPrevOuro, $qConfOuro, $situacao, $notaDb]);
    }

    // ✅ Sucesso: respeita "Próximo" e abre o modal novamente
    $redirectBack('Item adicionado!');
} catch (Throwable $e) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL;
    @file_put_contents(__DIR__ . '/../storage_itens_error.log', $log, FILE_APPEND);

    $redirectBack('Erro ao salvar item. Veja storage_itens_error.log');
}
