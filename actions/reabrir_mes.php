<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/audit.php';

// PhpSpreadsheet (pra remover aba)
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

auth_session_start();
auth_require_role('admin');
post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'reabrir_mes')) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, [
        'reason' => 'csrf_invalid'
    ]);
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('reabrir_mes');

$competencia = (string)($_POST['competencia'] ?? '');
$confirm = trim((string)($_POST['confirm'] ?? ''));

if (!competencia_valida($competencia)) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, [
        'competencia' => $competencia,
        'reason' => 'invalid_competencia',
    ]);
    http_response_code(400);
    exit('Competência inválida.');
}

$expected = "REABRIR {$competencia}";
if (mb_strtoupper($confirm, 'UTF-8') !== $expected) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, [
        'competencia' => $competencia,
        'reason' => 'invalid_confirm',
        'confirm' => $confirm,
    ]);
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: {$expected}");
}

// Before: existe fechamento?
$chk = $pdo->prepare("SELECT competencia FROM fechamentos WHERE competencia = ? LIMIT 1");
$chk->execute([$competencia]);
$before = $chk->fetch(PDO::FETCH_ASSOC) ?: null;

// Reabre (delete do fechamento)
$stmt = $pdo->prepare("DELETE FROM fechamentos WHERE competencia = ? LIMIT 1");
$ok = $stmt->execute([$competencia]);

if (!$ok) {
    audit_log($pdo, 'reopen_month', 'fechamento', null, [
        'competencia' => $competencia,
        'reason' => 'db_error_delete',
    ]);
    http_response_code(500);
    exit('Erro ao reabrir mês.');
}

// ✅ Remove aba do Excel master (se existir)
$xlsxInfo = remove_month_sheet_from_master($competencia);

// Audit
audit_log($pdo, 'reopen_month', 'fechamento', null, [
    'competencia' => $competencia,
    'had_closure' => $before ? 1 : 0,
    'xlsx' => $xlsxInfo,
]);

redirect_with_query('../index.php', [
    'competencia' => $competencia,
    'toast' => 'mes_reaberto'
]);

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

        // Remove a aba
        $idx = $spreadsheet->getIndex($sheet);
        $spreadsheet->removeSheetByIndex($idx);

        // Se ficou sem abas, mantém 1 aba "INFO" (Excel não gosta de arquivo sem sheet)
        if ($spreadsheet->getSheetCount() === 0) {
            $newSheet = $spreadsheet->createSheet();
            $newSheet->setTitle('INFO');
            $newSheet->setCellValue('A1', 'Planilha gerada pelo sistema (exports).');
        }

        // Salva de volta
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
