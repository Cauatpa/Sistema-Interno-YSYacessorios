<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../helpers/return_redirect.php';

// PhpSpreadsheet (pra remover aba)
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

auth_session_start();
auth_require_role('admin');
post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'reabrir_mes')) {
    audit_log(
        $pdo,
        'reopen_month',
        'fechamento',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao reabrir mês (CSRF inválido).'
    );
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('reabrir_mes');

$competencia = (string)($_POST['competencia'] ?? '');
$confirm     = trim((string)($_POST['confirm'] ?? ''));

if (!competencia_valida($competencia)) {
    audit_log(
        $pdo,
        'reopen_month',
        'fechamento',
        null,
        ['competencia' => $competencia, 'reason' => 'invalid_competencia'],
        null,
        null,
        false,
        'invalid_competencia',
        'Falha ao reabrir mês (competência inválida).'
    );
    http_response_code(400);
    exit('Competência inválida.');
}

$expected = "REABRIR {$competencia}";
if (mb_strtoupper($confirm, 'UTF-8') !== $expected) {
    audit_log(
        $pdo,
        'reopen_month',
        'fechamento',
        null,
        ['competencia' => $competencia, 'reason' => 'invalid_confirm', 'confirm' => $confirm],
        null,
        null,
        false,
        'invalid_confirm',
        "Falha ao reabrir mês {$competencia} (confirmação inválida)."
    );
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: {$expected}");
}

// BEFORE: existe fechamento?
$chk = $pdo->prepare("SELECT competencia FROM fechamentos WHERE competencia = ? LIMIT 1");
$chk->execute([$competencia]);
$before = $chk->fetch(PDO::FETCH_ASSOC) ?: null;

// Reabre (delete do fechamento)
$stmt = $pdo->prepare("DELETE FROM fechamentos WHERE competencia = ?");
$ok = $stmt->execute([$competencia]);

if (!$ok) {
    audit_log(
        $pdo,
        'reopen_month',
        'fechamento',
        null,
        ['competencia' => $competencia, 'reason' => 'db_error_delete'],
        $before,
        null,
        false,
        'db_error_delete',
        "Falha ao reabrir mês {$competencia} (erro banco)."
    );
    http_response_code(500);
    exit('Erro ao reabrir mês.');
}

$after = ['competencia' => $competencia, 'status' => 'aberto'];

// Remove aba do Excel master (se existir)
$xlsxInfo = remove_month_sheet_from_master($competencia);

audit_log(
    $pdo,
    'reopen_month',
    'fechamento',
    null,
    [
        'competencia' => $competencia,
        'had_closure' => $before ? 1 : 0,
        'xlsx' => $xlsxInfo,
    ],
    $before ?: ['competencia' => $competencia, 'status' => 'fechado? (não encontrado no banco)'],
    $after,
    true,
    null,
    "Reabriu o mês {$competencia}."
);

// =====================
// ✅ Redirect único e correto (funciona em /InterYSY e em localhost)
// =====================

// limpa qualquer output antes do header
if (ob_get_length()) {
    @ob_end_clean();
}

// /InterYSY/actions -> /InterYSY
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$baseDir   = preg_replace('#/actions$#', '', $scriptDir);

$qs = http_build_query([
    'page'        => 'retiradas',
    'competencia' => $competencia,
    'toast'       => 'mes_reaberto',
]);

header('Location: ' . $baseDir . '/index.php?' . $qs);
exit;

/**
 * Remove a aba "YYYY-MM" do arquivo exports/retiradas_fechadas.xlsx
 * Retorna infos úteis (pra auditoria e debug).
 */
function remove_month_sheet_from_master(string $competencia): array
{
    $path = realpath(__DIR__ . '/../exports');
    if ($path === false) {
        return ['status' => 'no_exports_dir'];
    }

    $file = $path . DIRECTORY_SEPARATOR . 'retiradas_fechadas.xlsx';
    if (!is_file($file) || filesize($file) <= 0) {
        return ['status' => 'no_master_file'];
    }

    try {
        $spreadsheet = IOFactory::load($file);

        $sheet = $spreadsheet->getSheetByName($competencia);
        if ($sheet === null) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return ['status' => 'sheet_not_found', 'sheet' => $competencia];
        }

        $idx = $spreadsheet->getIndex($sheet);
        $spreadsheet->removeSheetByIndex($idx);

        if ($spreadsheet->getSheetCount() === 0) {
            $newSheet = $spreadsheet->createSheet();
            $newSheet->setTitle('INFO');
            $newSheet->setCellValue('A1', 'Planilha gerada pelo sistema (exports).');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($file);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return ['status' => 'removed', 'sheet' => $competencia];
    } catch (Throwable $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
