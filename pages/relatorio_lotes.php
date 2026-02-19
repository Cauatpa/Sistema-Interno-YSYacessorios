<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

auth_session_start();
auth_require_login(); // igual ao seu relatório
// auth_require_role('admin'); // se quiser travar, descomente

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$csrf = csrf_token('relatorio_lotes');

/**
 * Lista de lotes para o select
 * - Não existe coluna "nome" na tabela lotes (seu print confirma)
 * - Monta label rica: "CODIGO • 2026-02 • Fornecedor"
 */
$stmt = $pdo->query("
  SELECT
    id,
    competencia,
    codigo,
    fornecedor,
    data_recebimento,
    status
  FROM lotes
  WHERE deleted_at IS NULL
  ORDER BY id DESC
");
$lotes = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

function lote_label(array $l): string
{
    $codigo = trim((string)($l['codigo'] ?? ''));
    $comp   = trim((string)($l['competencia'] ?? ''));
    $forn   = trim((string)($l['fornecedor'] ?? ''));

    $base = $codigo !== '' ? $codigo : ('Lote #' . (int)$l['id']);
    $parts = [];
    if ($comp !== '') $parts[] = $comp;
    if ($forn !== '') $parts[] = $forn;

    return $parts ? ($base . ' • ' . implode(' • ', $parts)) : $base;
}
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="">

<head>
    <meta charset="UTF-8" />
    <title>Relatório • Lotes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- ✅ MESMO PADRÃO DO relatorio_retiradas.php -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ CSS do relatório atual -->
    <link rel="stylesheet" href="../assets/css/relatorio.css">
    <link rel="stylesheet" href="../assets/css/relatorios.css">

    <link rel="icon" type="image/png" href="../assets/imgs/Y.png">
</head>

<body class="p-3 p-md-4">
    <div class="container report-container" data-page="relatorio-lotes">
        <!-- Topbar (igual ao seu) -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <div class="page-title">📦 Relatório de Lotes</div>
                <div class="subtle small">Selecione um lote para visualizar indicadores, status e divergências.</div>
            </div>

            <div class="d-flex gap-2">
                <a href="../index.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>

                <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
                    🌙 Tema escuro
                </button>
            </div>
        </div>

        <!-- Header Card (Select do lote + status) -->
        <div class="card card-soft p-3 mb-3">
            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 toolbar">
                <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center w-100">
                    <div class="fw-semibold">Lote</div>

                    <select id="selLote" class="form-select" style="max-width: 520px;">
                        <option value="" selected>Selecione…</option>
                        <?php foreach ($lotes as $l): ?>
                            <option value="<?= (int)$l['id'] ?>">
                                <?= h(lote_label($l)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <noscript>
                        <div class="small text-muted ms-1">Ative JavaScript para carregar os dados do lote.</div>
                    </noscript>
                </div>

                <div class="d-flex justify-content-between justify-content-md-end align-items-center gap-2 width-auto w-100">
                    <!-- preenchido pelo JS quando escolher o lote -->
                    <span id="pillLoteStatus" class="pill pill-closed">—</span>
                </div>
            </div>

            <div class="mt-3 muted-divider"></div>

            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-3">
                <div class="small text-muted">
                    Lote selecionado: <strong id="lblLoteSelecionado">—</strong>
                </div>
                <div class="small text-muted">
                    Competência: <strong id="lblCompetencia">—</strong>
                    <span class="ms-2">• Fornecedor: <strong id="lblFornecedor">—</strong></span>
                </div>
            </div>
        </div>

        <!-- KPI Grid (mesma linguagem visual) -->
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon gray">📦</div>
                        <div class="kpi-label">Total previsto</div>
                    </div>
                    <div class="kpi-value mt-2" id="kpiPrevisto">—</div>
                    <div class="kpi-foot">Soma do previsto no lote</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon">📥</div>
                        <div class="kpi-label">Total recebido</div>
                    </div>
                    <div class="kpi-value mt-2" id="kpiRecebido">—</div>
                    <div class="kpi-foot">Soma do conferido no lote</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon green">✅</div>
                        <div class="kpi-label">Itens OK</div>
                    </div>
                    <div class="kpi-value mt-2" id="kpiOk">—</div>
                    <div class="kpi-foot">Conferido = Previsto</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon red">⚠️</div>
                        <div class="kpi-label">Divergências</div>
                    </div>
                    <div class="kpi-value mt-2" id="kpiDiverg">—</div>
                    <div class="kpi-foot">Faltando / Sobrando</div>
                </div>
            </div>
        </div>

        <!-- Gráficos (mesma organização visual) -->
        <div class="row col-12 mb-3 mt-3 charts-row">
            <div class="col-12 col-lg-4 charts-col">
                <div class="card card-soft p-3 h-100">
                    <div class="fw-semibold mb-2">Status do lote (itens)</div>
                    <div class="chart-wrapper">
                        <canvas id="chartStatusLote"></canvas>
                    </div>
                    <div class="small text-muted" id="statusHint"></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 charts-col">
                <div class="card card-soft p-3 h-100">
                    <div class="fw-semibold mb-2">Top divergências (SKU)</div>
                    <div class="chart-wrapper">
                        <canvas id="chartTopDiverg"></canvas>
                    </div>
                    <div class="small text-muted" id="topHint"></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 charts-col">
                <div class="card card-soft p-3 h-100">
                    <div class="fw-semibold mb-2">Resumo</div>
                    <div class="small text-muted" id="loteResumo">Selecione um lote para ver os dados.</div>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card card-soft p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="fw-semibold">Itens do lote</div>

                <div class="d-flex gap-2">
                    <select id="selStatusFiltro" class="form-select form-select-sm" style="min-width: 220px;">
                        <option value="">Todos os status</option>
                    </select>

                    <input id="txtBusca" class="form-control form-control-sm" placeholder="Buscar produto..." style="min-width: 240px;" />
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th class="text-muted">Variação</th>
                            <th>Previsto</th>
                            <th>Conferido</th>
                            <th>Diferença</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyItens">
                        <tr>
                            <td colspan="6" class="text-muted small">Selecione um lote para carregar os itens.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="small text-muted mt-2" id="tableHint"></div>
        </div>
    </div>

    <!-- Modal: Itens por status (clique no gráfico de status) -->
    <div class="modal fade" id="modalLoteStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="modalLoteStatusTitle">Itens</h5>
                        <div class="text-muted small" id="modalLoteStatusSub"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div id="modalLoteStatusLoading" class="text-muted small">Carregando...</div>
                    <div id="modalLoteStatusErro" class="alert alert-danger small" style="display:none;"></div>

                    <div id="modalLoteStatusContent" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted small">
                                Total itens: <strong id="modalLoteStatusTotal">0</strong>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-muted">Variação</th>
                                        <th>Prev.</th>
                                        <th>Conf.</th>
                                        <th>Dif.</th>
                                    </tr>
                                </thead>
                                <tbody id="modalLoteStatusTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // CSRF para requests do relatório de lotes
        document.documentElement.dataset.csrfRelatorioLotes = <?= json_encode($csrf) ?>;
    </script>

    <!-- ✅ MESMO PADRÃO DO relatorio_retiradas.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Seu theme.js já controla data-bs-theme -->
    <script src="../assets/js/theme.js" defer></script>

    <!-- JS do relatório de lotes -->
    <script src="../assets/js/relatorio_lotes.js" defer></script>
</body>

</html>