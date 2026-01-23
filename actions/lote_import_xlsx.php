<?php
// actions/lote_import_xlsx.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';

// PhpSpreadsheet
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

auth_session_start();
auth_require_role('operador');
post_only();

if (!csrf_validate($_POST['csrf_token'] ?? null, 'lote_import_xlsx')) {
    audit_log($pdo, 'import', 'lote', null, ['reason' => 'csrf_invalid'], null, null, false, 'csrf_invalid', 'CSRF inválido.', 'Falha ao importar XLSX (CSRF).');
    http_response_code(403);
    exit('CSRF inválido.');
}

$loteId = (int)($_POST['lote_id'] ?? 0);
$recebimentoId = (int)($_POST['recebimento_id'] ?? 0);

$onConflict = (string)($_POST['on_conflict'] ?? 'ignore'); // ignore|sum|replace
$onMissing  = (string)($_POST['on_missing_product'] ?? 'skip'); // skip|fail

if (!in_array($onConflict, ['ignore', 'sum', 'replace'], true)) $onConflict = 'ignore';
if (!in_array($onMissing, ['skip', 'fail'], true)) $onMissing = 'skip';

if ($loteId <= 0) {
    header('Location: ../lotes.php?toast=' . urlencode('Lote inválido.'));
    exit;
}
if ($recebimentoId <= 0) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Selecione um recebimento antes de importar.'));
    exit;
}

// valida recebimento pertence ao lote
$stmtR = $pdo->prepare("SELECT id FROM lote_recebimentos WHERE id = ? AND lote_id = ? LIMIT 1");
$stmtR->execute([$recebimentoId, $loteId]);
if (!$stmtR->fetchColumn()) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&toast=' . urlencode('Recebimento inválido para este lote.'));
    exit;
}

// arquivo
if (!isset($_FILES['xlsx']) || !is_array($_FILES['xlsx'])) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Envie um arquivo XLSX.'));
    exit;
}

$f = $_FILES['xlsx'];

if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Falha no upload do XLSX.'));
    exit;
}

$tmpPath = (string)($f['tmp_name'] ?? '');
$origName = (string)($f['name'] ?? 'arquivo.xlsx');

if ($tmpPath === '' || !is_file($tmpPath)) {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Arquivo inválido.'));
    exit;
}

// segurança simples: extensão
if (mb_strtolower(pathinfo($origName, PATHINFO_EXTENSION), 'UTF-8') !== 'xlsx') {
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Envie um arquivo .xlsx.'));
    exit;
}

$summary = [
    'file' => $origName,
    'on_conflict' => $onConflict,
    'on_missing_product' => $onMissing,
    'total_rows' => 0,
    'imported' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

$errors = []; // lista de erros por linha

try {
    $spreadsheet = IOFactory::load($tmpPath);
    $sheet = $spreadsheet->getSheet(0);

    // pega todas as linhas (array)
    $rows = $sheet->toArray(null, true, true, true);
    if (!$rows || count($rows) < 2) {
        throw new RuntimeException('Planilha vazia ou sem dados.');
    }

    // Detecta cabeçalho (primeira linha)
    $header = $rows[1] ?? [];
    $map = map_header($header);

    // precisa das 3 colunas
    foreach (['produto_nome', 'variacao', 'qtd_prevista'] as $req) {
        if (!isset($map[$req])) {
            throw new RuntimeException("Cabeçalho inválido. Coluna obrigatória ausente: {$req}");
        }
    }

    // prepared statements
    $stmtProd = $pdo->prepare("SELECT id, nome FROM produtos WHERE nome = ? LIMIT 1");

    $stmtChkItem = $pdo->prepare("
        SELECT id, qtd_prevista
        FROM lote_itens
        WHERE lote_id = ?
          AND recebimento_id = ?
          AND produto_id = ?
          AND variacao = ?
        LIMIT 1
    ");

    $stmtIns = $pdo->prepare("
        INSERT INTO lote_itens
          (lote_id, recebimento_id, produto_id, produto_nome, variacao, qtd_prevista, qtd_conferida, situacao, nota)
        VALUES
          (?, ?, ?, ?, ?, ?, NULL, 'ok', NULL)
    ");

    $stmtUpd = $pdo->prepare("
        UPDATE lote_itens
        SET qtd_prevista = ?, atualizado_em = NOW()
        WHERE id = ? AND lote_id = ?
        LIMIT 1
    ");

    $pdo->beginTransaction();

    // percorre a partir da linha 2
    for ($i = 2; $i <= count($rows); $i++) {
        $r = $rows[$i] ?? null;
        if (!is_array($r)) continue;

        $produtoNome = norm_str((string)($r[$map['produto_nome']] ?? ''));
        $variacaoRaw = norm_str((string)($r[$map['variacao']] ?? ''));
        $qPrevRaw    = $r[$map['qtd_prevista']] ?? '';

        // pula linha completamente vazia
        if ($produtoNome === '' && $variacaoRaw === '' && (string)$qPrevRaw === '') {
            continue;
        }

        $summary['total_rows']++;

        $variacao = normalize_variacao($variacaoRaw);
        $qPrev = normalize_int($qPrevRaw);

        if ($produtoNome === '' || $variacao === '' || $qPrev === null || $qPrev < 0) {
            $summary['errors']++;
            $errors[] = ['line' => $i, 'error' => 'Dados inválidos (produto_nome/variacao/qtd_prevista).', 'produto_nome' => $produtoNome, 'variacao' => $variacaoRaw, 'qtd_prevista' => $qPrevRaw];
            continue;
        }

        // acha produto
        $stmtProd->execute([$produtoNome]);
        $p = $stmtProd->fetch(PDO::FETCH_ASSOC);

        if (!$p) {
            if ($onMissing === 'fail') {
                throw new RuntimeException("Produto não encontrado na linha {$i}: {$produtoNome}");
            }
            $summary['skipped']++;
            $errors[] = ['line' => $i, 'error' => 'Produto não encontrado.', 'produto_nome' => $produtoNome];
            continue;
        }

        $produtoId = (int)$p['id'];
        $produtoNomeDb = (string)$p['nome']; // usa o nome do banco (padrão)

        // existe item?
        $stmtChkItem->execute([$loteId, $recebimentoId, $produtoId, $variacao]);
        $existing = $stmtChkItem->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($onConflict === 'ignore') {
                $summary['skipped']++;
                continue;
            }

            $oldPrev = (int)($existing['qtd_prevista'] ?? 0);
            $newPrev = $qPrev;

            if ($onConflict === 'sum') {
                $newPrev = $oldPrev + $qPrev;
            }

            $stmtUpd->execute([$newPrev, (int)$existing['id'], $loteId]);
            $summary['updated']++;
        } else {
            $stmtIns->execute([$loteId, $recebimentoId, $produtoId, $produtoNomeDb, ucfirst($variacao), $qPrev]);
            $summary['imported']++;
        }
    }

    $pdo->commit();

    csrf_rotate('lote_import_xlsx');

    // auditoria
    audit_log(
        $pdo,
        'import',
        'lote',
        $loteId,
        [
            'recebimento_id' => $recebimentoId,
            'file' => $origName,
            'on_conflict' => $onConflict,
            'on_missing_product' => $onMissing,
            'summary' => $summary,
            'errors_sample' => array_slice($errors, 0, 20),
        ],
        null,
        null,
        true,
        null,
        "Importou XLSX no lote #{$loteId} (rec {$recebimentoId}) — imp: {$summary['imported']} | upd: {$summary['updated']} | skip: {$summary['skipped']} | err: {$summary['errors']}."
    );

    // toast compacto
    $toast = "Importado: {$summary['imported']} | Atualizados: {$summary['updated']} | Ignorados: {$summary['skipped']} | Erros: {$summary['errors']}";
    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode($toast));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    audit_log(
        $pdo,
        'import',
        'lote',
        $loteId ?: null,
        [
            'recebimento_id' => $recebimentoId,
            'file' => $origName,
            'on_conflict' => $onConflict,
            'on_missing_product' => $onMissing,
            'summary' => $summary,
            'errors_sample' => array_slice($errors, 0, 20),
            'exception' => $e->getMessage(),
        ],
        null,
        null,
        false,
        'import_error',
        'Falha ao importar XLSX.',
        "Falha ao importar XLSX no lote #{$loteId}."
    );

    $msg = $e->getMessage();
    if (strlen($msg) > 180) $msg = substr($msg, 0, 180) . '...';

    header('Location: ../lote.php?id=' . $loteId . '&edit=1&recebimento_id=' . $recebimentoId . '&toast=' . urlencode('Erro importação: ' . $msg));
    exit;
}

/**
 * -------- helpers locais --------
 */

function norm_str(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    return trim($s);
}

function normalize_variacao(string $v): string
{
    $v = mb_strtolower(norm_str($v), 'UTF-8');

    // aceita: prata/ouro, p/o, etc
    if ($v === 'prata' || $v === 'p') return 'prata';
    if ($v === 'ouro' || $v === 'o') return 'ouro';

    // aceita "banho ouro" etc (se vier sujo)
    if (str_contains($v, 'prata')) return 'prata';
    if (str_contains($v, 'ouro')) return 'ouro';

    return '';
}

function normalize_int($raw): ?int
{
    // PhpSpreadsheet pode trazer número como float/int ou string
    if (is_int($raw)) return $raw;
    if (is_float($raw)) return (int)round($raw);

    if (is_string($raw)) {
        $raw = trim($raw);
        if ($raw === '') return null;

        // troca vírgula por ponto e remove lixo
        $raw = str_replace(',', '.', $raw);
        // pega somente parte numérica
        if (!preg_match('/^-?\d+(\.\d+)?$/', $raw)) {
            return null;
        }
        return (int)round((float)$raw);
    }

    return null;
}

/**
 * Mapeia cabeçalho (linha 1) para colunas obrigatórias.
 * Aceita nomes alternativos.
 */
function map_header(array $headerRow): array
{
    // $headerRow vem como ['A' => 'Produto', 'B' => 'Variação', ...]
    $normalized = [];
    foreach ($headerRow as $col => $name) {
        $n = mb_strtolower(trim((string)$name), 'UTF-8');
        $n = preg_replace('/\s+/', '_', $n) ?? $n;
        $normalized[$col] = $n;
    }

    // aliases aceitos
    $aliases = [
        'produto_nome' => ['produto_nome', 'produto', 'nome', 'produto name'],
        'variacao' => ['variacao', 'variação', 'tipo', 'banho', 'material'],
        'qtd_prevista' => ['qtd_prevista', 'quantidade_prevista', 'qtd', 'quantidade', 'qtd_esperada', 'quantidade_esperada'],
    ];

    $map = [];

    foreach ($aliases as $key => $alts) {
        foreach ($normalized as $col => $n) {
            if (in_array($n, $alts, true)) {
                $map[$key] = $col;
                break;
            }
        }
    }

    return $map;
}
