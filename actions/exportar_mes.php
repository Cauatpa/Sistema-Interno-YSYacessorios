<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../services/fechamento.php';

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
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('exportar_mes');

$competencia = (string)($_POST['competencia'] ?? '');
$regen = (int)($_POST['regen'] ?? 0);

if (!competencia_valida($competencia)) {
    http_response_code(400);
    exit('Competência inválida.');
}

// Só exporta mês fechado
if (!mes_esta_fechado($pdo, $competencia)) {
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
    http_response_code(500);
    exit('Não foi possível preparar a pasta exports/.');
}

// ✅ UM arquivo só, com abas por mês
$filename = "retiradas_fechadas.xlsx";
$filepath = $exportDir . DIRECTORY_SEPARATOR . $filename;

// Se arquivo existe e aba já existe e não é regen → baixa direto (cache)
if ($regen !== 1 && is_file($filepath) && filesize($filepath) > 0) {
    if (xlsx_has_sheet($filepath, $competencia)) {
        download_file($filepath, $filename);
        exit;
    }
}

// Gera/atualiza a aba do mês dentro do mesmo XLSX
upsert_sheet_retiradas($pdo, $competencia, $filepath, ($regen === 1));

// Baixa
download_file($filepath, $filename);
exit;

/**
 * Verifica se o XLSX já tem uma aba com esse nome.
 */
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

/**
 * Cria ou atualiza a aba do mês dentro do arquivo master.
 */
function upsert_sheet_retiradas(PDO $pdo, string $competencia, string $filepath, bool $forceRegen): void
{
    // Busca dados
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

    // Abre ou cria planilha master
    if (is_file($filepath) && filesize($filepath) > 0) {
        $spreadsheet = IOFactory::load($filepath);
    } else {
        $spreadsheet = new Spreadsheet();
        // remove aba default vazia, pra ficar limpo
        $spreadsheet->removeSheetByIndex(0);
    }

    // Se já existe a aba:
    $existing = $spreadsheet->getSheetByName($competencia);

    if ($existing !== null) {
        if (!$forceRegen) {
            // já existe e não pediu regen: só garante que arquivo será salvo (normalmente nem precisa)
            return;
        }
        // regen: remove e recria
        $idx = $spreadsheet->getIndex($existing);
        $spreadsheet->removeSheetByIndex($idx);
    }

    // Cria nova aba
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($competencia);
    $spreadsheet->setActiveSheetIndexByName($competencia);

    // Cabeçalho
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

    // Dados (lote)
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

    // Estilo leve
    $lastCol = Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
    $sheet->freezePane('A2');

    // Larguras fixas (AutoSize é lento)
    $widths = [8, 20, 40, 14, 10, 20, 12, 12, 14, 12, 22, 20];
    foreach ($widths as $i => $w) {
        $col = Coordinate::stringFromColumnIndex($i + 1);
        $sheet->getColumnDimension($col)->setWidth($w);
    }

    // Salva
    $dir = dirname($filepath);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $writer = new Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save($filepath);

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
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
// ----------------- FIM DA EXPORTAÇÃO -----------------
