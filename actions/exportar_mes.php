<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/audit.php';

// PhpSpreadsheet
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

auth_session_start();
auth_require_role('admin');
post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'exportar_mes')) {
    audit_log(
        $pdo,
        'export',
        'fechamento',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao exportar mês (CSRF inválido).'
    );
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('exportar_mes');

$competencia = (string)($_POST['competencia'] ?? '');
$regen = (int)($_POST['regen'] ?? 0);

if (!competencia_valida($competencia)) {
    audit_log(
        $pdo,
        'export',
        'fechamento',
        null,
        ['reason' => 'invalid_competencia', 'competencia' => $competencia],
        null,
        null,
        false,
        'invalid_competencia',
        'Falha ao exportar mês (competência inválida).'
    );
    http_response_code(400);
    exit('Competência inválida.');
}

// Só exporta mês fechado
if (!mes_esta_fechado($pdo, $competencia)) {
    audit_log(
        $pdo,
        'export',
        'fechamento',
        null,
        ['reason' => 'month_not_closed', 'competencia' => $competencia],
        null,
        null,
        false,
        'month_not_closed',
        "Falha ao exportar: mês {$competencia} não está fechado."
    );
    http_response_code(403);
    exit("Só é permitido exportar meses fechados ({$competencia}).");
}

// Pasta exports
$exportDir = realpath(__DIR__ . '/../exports');
if ($exportDir === false) {
    @mkdir(__DIR__ . '/../exports', 0775, true);
    $exportDir = realpath(__DIR__ . '/../exports');
}
if ($exportDir === false) {
    audit_log(
        $pdo,
        'export',
        'fechamento',
        null,
        ['reason' => 'no_exports_dir', 'competencia' => $competencia],
        null,
        null,
        false,
        'no_exports_dir',
        'Falha ao exportar: pasta exports não disponível.'
    );
    http_response_code(500);
    exit('Não foi possível preparar a pasta exports/.');
}

// ✅ UM arquivo só, com abas por mês
$filename = "retiradas_fechadas.xlsx";
$filepath = $exportDir . DIRECTORY_SEPARATOR . $filename;

// Se arquivo existe e aba já existe e não é regen → baixa direto (cache)
if ($regen !== 1 && is_file($filepath) && filesize($filepath) > 0) {
    if (xlsx_has_sheet($filepath, $competencia)) {
        audit_log(
            $pdo,
            'export',
            'fechamento',
            null,
            ['competencia' => $competencia, 'regen' => 0, 'source' => 'cache', 'file' => $filename],
            null,
            null,
            true,
            null,
            "Exportou mês {$competencia} (cache)."
        );
        download_file($filepath, $filename);
        exit;
    }
}

// Gera/atualiza a aba do mês dentro do mesmo XLSX (retorna meta p/ auditoria)
$meta = upsert_sheet_retiradas($pdo, $competencia, $filepath, ($regen === 1));

audit_log(
    $pdo,
    'export',
    'fechamento',
    null,
    [
        'competencia' => $competencia,
        'regen' => ($regen === 1) ? 1 : 0,
        'source' => 'generated',
        'file' => $filename,
        'meta' => $meta,
    ],
    null,
    null,
    true,
    null,
    "Exportou mês {$competencia}."
);

// Baixa
download_file($filepath, $filename);
exit;

function xlsx_has_sheet(string $filepath, string $sheetName): bool
{
    try {
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        return $sheet !== null;
    } catch (Throwable $e) {
        return false;
    }
}

function upsert_sheet_retiradas(PDO $pdo, string $competencia, string $filepath, bool $forceRegen): array
{
    $stmt = $pdo->prepare("
        SELECT
            id,
            data_pedido,
            produto,
            quantidade_solicitada,
            tipo,
            solicitante,
            status,
            quantidade_retirada,
            precisa_balanco,
            sem_estoque,
            responsavel_estoque,
            data_finalizacao
        FROM retiradas
        WHERE competencia = ?
          AND deleted_at IS NULL
        ORDER BY data_pedido ASC, id ASC
    ");
    $stmt->execute([$competencia]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowsCount = count($rows);

    if (is_file($filepath) && filesize($filepath) > 0) {
        $spreadsheet = IOFactory::load($filepath);
    } else {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
    }

    $existing = $spreadsheet->getSheetByName($competencia);

    if ($existing !== null) {
        if (!$forceRegen) {
            // não regen: mantém
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return ['status' => 'kept_existing', 'rows' => $rowsCount];
        }
        $idx = $spreadsheet->getIndex($existing);
        $spreadsheet->removeSheetByIndex($idx);
    }

    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($competencia);
    $spreadsheet->setActiveSheetIndexByName($competencia);

    $headers = [
        'ID',
        'Data pedido',
        'Produto',
        'Qtd solicitada',
        'Tipo',
        'Solicitante',
        'Status',
        'Qtd retirada',
        'Precisa balanço',
        'Sem estoque',
        'Responsável estoque',
        'Data finalização',
    ];
    $sheet->fromArray($headers, null, 'A1');

    $data = [];
    foreach ($rows as $r) {
        $data[] = [
            (int)($r['id'] ?? 0),
            (string)($r['data_pedido'] ?? ''),
            (string)($r['produto'] ?? ''),
            (int)($r['quantidade_solicitada'] ?? 0),
            (string)($r['tipo'] ?? ''),
            (string)($r['solicitante'] ?? ''),
            (string)($r['status'] ?? ''),
            isset($r['quantidade_retirada']) ? (int)$r['quantidade_retirada'] : '',
            !empty($r['precisa_balanco']) ? 1 : 0,
            !empty($r['sem_estoque']) ? 1 : 0,
            (string)($r['responsavel_estoque'] ?? ''),
            (string)($r['data_finalizacao'] ?? ''),
        ];
    }
    if (!empty($data)) {
        $sheet->fromArray($data, null, 'A2');
    }

    $lastCol = Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
    $sheet->freezePane('A2');

    $widths = [8, 20, 40, 14, 10, 20, 12, 12, 14, 12, 22, 20];
    foreach ($widths as $i => $w) {
        $col = Coordinate::stringFromColumnIndex($i + 1);
        $sheet->getColumnDimension($col)->setWidth($w);
    }

    $dir = dirname($filepath);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $writer = new Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save($filepath);

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    return ['status' => $existing ? 'regenerated' : 'created', 'rows' => $rowsCount];
}

function download_file(string $filepath, string $downloadName): void
{
    if (!is_file($filepath) || filesize($filepath) === 0) {
        http_response_code(500);
        exit('Arquivo de exportação não encontrado.');
    }

    if (ob_get_level()) {
        @ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    readfile($filepath);
    exit;
}
