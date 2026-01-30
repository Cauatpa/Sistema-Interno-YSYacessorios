<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../services/fechamento.php';

auth_session_start();
auth_require_login();

$competencia = (string)($_GET['competencia'] ?? competencia_atual());
if (!competencia_valida($competencia)) {
    $competencia = competencia_atual();
}

// meses disponíveis
$stmtMeses = $pdo->query("
    SELECT DISTINCT competencia
    FROM retiradas
    WHERE deleted_at IS NULL
    ORDER BY competencia DESC
");
$mesesDisponiveis = $stmtMeses->fetchAll(PDO::FETCH_COLUMN) ?: [];

$mesFechado = mes_esta_fechado($pdo, $competencia);

// ====== KPIs ======
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_pedidos,
        COALESCE(SUM(COALESCE(quantidade_solicitada,0)), 0) AS total_itens_solicitados,
        COALESCE(SUM(COALESCE(quantidade_retirada,0)), 0) AS total_itens_retirados,

        -- Sem estoque (contagem)
        COALESCE(SUM(CASE WHEN sem_estoque = 1 THEN 1 ELSE 0 END), 0) AS total_sem_estoque,

        -- Precisa balanço (contagem) - somente quando não é sem estoque e ainda não fez balanço
        COALESCE(SUM(
            CASE
                WHEN precisa_balanco = 1
                 AND sem_estoque = 0
                 AND COALESCE(balanco_feito,0) = 0
                THEN 1 ELSE 0
            END
        ), 0) AS total_balanco,

        -- Balanço feito (contagem)
        COALESCE(SUM(CASE WHEN COALESCE(balanco_feito,0) = 1 THEN 1 ELSE 0 END), 0) AS total_balanco_feito,

        -- Finalizados (contagem)
        COALESCE(SUM(
            CASE
                WHEN sem_estoque = 0
                 AND NOT (precisa_balanco = 1 AND COALESCE(balanco_feito,0) = 0)
                 AND TRIM(LOWER(COALESCE(status,''))) = 'finalizado'
                THEN 1 ELSE 0
            END
        ), 0) AS total_finalizados,

        -- Pendentes (contagem)
        COALESCE(SUM(
            CASE
                WHEN NOT (
                    sem_estoque = 1
                    OR (precisa_balanco = 1 AND sem_estoque = 0 AND COALESCE(balanco_feito,0) = 0)
                    OR (
                        sem_estoque = 0
                        AND NOT (precisa_balanco = 1 AND COALESCE(balanco_feito,0) = 0)
                        AND TRIM(LOWER(COALESCE(status,''))) = 'finalizado'
                    )
                )
                THEN 1 ELSE 0
            END
        ), 0) AS total_pendentes,

        -- =========================
        -- ITENS POR CARD (PEDIDOS e ENTREGUES)
        -- =========================

        -- Sem estoque
        COALESCE(SUM(CASE WHEN sem_estoque = 1 THEN COALESCE(quantidade_solicitada,0) ELSE 0 END), 0) AS itens_sem_estoque_pedidos,
        COALESCE(SUM(CASE WHEN sem_estoque = 1 THEN COALESCE(quantidade_retirada,0)    ELSE 0 END), 0) AS itens_sem_estoque_entregues,

        -- Precisa balanço (aberto)
        COALESCE(SUM(
            CASE
                WHEN precisa_balanco = 1
                 AND sem_estoque = 0
                 AND COALESCE(balanco_feito,0) = 0
                THEN COALESCE(quantidade_solicitada,0) ELSE 0
            END
        ), 0) AS itens_precisa_balanco_pedidos,
        COALESCE(SUM(
            CASE
                WHEN precisa_balanco = 1
                 AND sem_estoque = 0
                 AND COALESCE(balanco_feito,0) = 0
                THEN COALESCE(quantidade_retirada,0) ELSE 0
            END
        ), 0) AS itens_precisa_balanco_entregues,

        -- ✅ Balanço feito (itens)
        COALESCE(SUM(
            CASE
                WHEN COALESCE(balanco_feito,0) = 1
                THEN COALESCE(quantidade_solicitada,0) ELSE 0
            END
        ), 0) AS itens_balanco_feito_pedidos,
        COALESCE(SUM(
            CASE
                WHEN COALESCE(balanco_feito,0) = 1
                THEN COALESCE(quantidade_retirada,0) ELSE 0
            END
        ), 0) AS itens_balanco_feito_entregues,

        -- Finalizados (itens)
        COALESCE(SUM(
            CASE
                WHEN sem_estoque = 0
                 AND NOT (precisa_balanco = 1 AND COALESCE(balanco_feito,0) = 0)
                 AND TRIM(LOWER(COALESCE(status,''))) = 'finalizado'
                THEN COALESCE(quantidade_solicitada,0) ELSE 0
            END
        ), 0) AS itens_finalizados_pedidos,
        COALESCE(SUM(
            CASE
                WHEN sem_estoque = 0
                 AND NOT (precisa_balanco = 1 AND COALESCE(balanco_feito,0) = 0)
                 AND TRIM(LOWER(COALESCE(status,''))) = 'finalizado'
                THEN COALESCE(quantidade_retirada,0) ELSE 0
            END
        ), 0) AS itens_finalizados_entregues,

        -- Outros (diagnóstico)
        COALESCE(SUM(
            CASE
                WHEN NOT (
                    sem_estoque = 1
                    OR (precisa_balanco = 1 AND sem_estoque = 0 AND COALESCE(balanco_feito,0) = 0)
                    OR (
                        sem_estoque = 0
                        AND NOT (precisa_balanco = 1 AND COALESCE(balanco_feito,0) = 0)
                        AND TRIM(LOWER(COALESCE(status,''))) = 'finalizado'
                    )
                )
                THEN COALESCE(quantidade_retirada,0) ELSE 0
            END
        ), 0) AS itens_outros

    FROM retiradas
    WHERE competencia = ?
      AND deleted_at IS NULL
");

$stmt->execute([$competencia]);
$kpis = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];


$itensOutros = (int)($kpis['itens_outros'] ?? 0);

function h($v): string
{
    return htmlspecialchars((string)$v);
}

$totalPedidos      = (int)($kpis['total_pedidos'] ?? 0);
$totalItensSolic   = (int)($kpis['total_itens_solicitados'] ?? 0);
$totalRetirados    = (int)($kpis['total_itens_retirados'] ?? 0);

$totalFinal        = (int)($kpis['total_finalizados'] ?? 0);
$totalPend         = (int)($kpis['total_pendentes'] ?? 0);

$totalSemEstoque   = (int)($kpis['total_sem_estoque'] ?? 0);
$totalBalanco      = (int)($kpis['total_balanco'] ?? 0);
$totalBalancoFeito = (int)($kpis['total_balanco_feito'] ?? 0);

$itensSemEstoquePedidos   = (int)($kpis['itens_sem_estoque_pedidos'] ?? 0);
$itensSemEstoqueEntregues = (int)($kpis['itens_sem_estoque_entregues'] ?? 0);

$itensBalancoPedidos      = (int)($kpis['itens_precisa_balanco_pedidos'] ?? 0);
$itensBalancoEntregues    = (int)($kpis['itens_precisa_balanco_entregues'] ?? 0);

$itensFinalPedidos        = (int)($kpis['itens_finalizados_pedidos'] ?? 0);
$itensFinalEntregues      = (int)($kpis['itens_finalizados_entregues'] ?? 0);

$percFinal      = $totalPedidos > 0 ? round(($totalFinal / $totalPedidos) * 100) : 0;
$percSemEstoque = $totalPedidos > 0 ? round(($totalSemEstoque / $totalPedidos) * 100) : 0;
$percBalanco    = $totalPedidos > 0 ? round(($totalBalanco / $totalPedidos) * 100) : 0;

$totalItensSolic = (int)($kpis['total_itens_solicitados'] ?? 0);
$totalRetirados  = (int)($kpis['total_itens_retirados'] ?? 0);

$itensBalancoFeitoPed  = (int)($kpis['itens_balanco_feito_pedidos'] ?? 0);
$itensBalancoFeitoEnt  = (int)($kpis['itens_balanco_feito_entregues'] ?? 0);

$diffItens = $totalRetirados - $totalItensSolic;

// helpers visuais
$diffAbs   = abs($diffItens);
$diffLabel = $diffItens === 0
    ? 'Sem diferença'
    : ($diffItens < 0 ? 'Diferença' : 'Itens excedentes');

$diffClass = $diffItens === 0
    ? 'text-muted'
    : ($diffItens < 0 ? 'text-danger' : 'text-success');
?>
<!DOCTYPE html>
<html lang="pt-br" data-competencia="<?= h($competencia) ?>" data-bs-theme="">

<head>
    <meta charset="UTF-8">
    <title>Relatório do mês</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/relatorio.css">
    <link rel="icon" type="image/png" href="../assets/imgs/Y.png">
</head>

<body class="p-3 p-md-4">
    <div class="container report-container">

        <!-- Topbar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <div class="page-title">📊 Relatório do mês / Controle de Estoque (Retirada)</div>
                <div class="subtle small">Resumo rápido do desempenho e status das retiradas</div>
            </div>

            <div class="d-flex gap-2">
                <a href="../index.php?competencia=<?= h($competencia) ?>" class="btn btn-outline-secondary btn-sm">
                    ← Voltar
                </a>

                <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
                    🌙 Tema escuro
                </button>
            </div>
        </div>

        <!-- Header Card (Mês + Status) -->
        <div class="card card-soft p-3 mb-3">
            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 toolbar">
                <form method="GET" class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center w-100">
                    <div class="fw-semibold">Mês</div>

                    <select name="competencia" class="form-select" onchange="this.form.submit()">
                        <?php if (!in_array($competencia, $mesesDisponiveis, true)): ?>
                            <option value="<?= h($competencia) ?>" selected><?= h($competencia) ?> (atual)</option>
                        <?php endif; ?>
                        <?php foreach ($mesesDisponiveis as $m): ?>
                            <option value="<?= h($m) ?>" <?= $m === $competencia ? 'selected' : '' ?>><?= h($m) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <noscript><button class="btn btn-primary">Ver</button></noscript>
                </form>

                <div class="d-flex justify-content-between justify-content-md-end align-items-center gap-2 width-auto w-100">
                    <?php if ($mesFechado): ?>
                        <span class="pill pill-closed">🔒 Mês fechado</span>
                    <?php else: ?>
                        <span class="pill pill-open">🟢 Mês aberto</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3 muted-divider"></div>

            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-3">
                <div class="small text-muted">
                    Competência selecionada: <strong><?= h($competencia) ?></strong>
                </div>
                <div class="small text-muted">
                    Finalização: <strong><?= (int)$percFinal ?>%</strong> |
                    Sem estoque: <strong><?= (int)$percSemEstoque ?>%</strong> |
                    Precisa balanço: <strong><?= (int)$percBalanco ?>%</strong>
                </div>
            </div>
        </div>

        <!-- KPI Grid -->
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon">🧾</div>
                        <div class="kpi-label">Total de pedidos</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalPedidos ?></div>
                    <div class="kpi-foot">Registros no mês</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon gray">📦</div>
                        <div class="kpi-label">Itens solicitados</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalItensSolic ?></div>
                    <div class="kpi-foot">Soma de quantidade de itens</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon gray">📤</div>
                        <div class="kpi-label">Itens retirados</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalRetirados ?></div>
                    <div class="kpi-foot d-flex justify-content-between align-items-center">
                        <span>Soma de quantidade de itens</span>

                        <span class="<?= $diffClass ?> small">
                            <?= $diffLabel ?>:
                            <strong><?= number_format($diffAbs, 0, ',', '.') ?></strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon green">⚖️</div>
                        <div class="kpi-label">Balanço feito</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalBalancoFeito ?></div>
                    <div class="kpi-foot small text-muted">
                        <span class="ms-2">• Pedidos: <?= number_format($itensBalancoFeitoPed, 0, ',', '.') ?></span>
                        <span class="ms-2">• Entregues: <?= number_format($itensBalancoFeitoEnt, 0, ',', '.') ?></span>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon red">🔴</div>
                        <div class="kpi-label">Sem estoque</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalSemEstoque ?></div>
                    <div class="kpi-foot">
                        <?= (int)$percSemEstoque ?>% do total
                        <span class="ms-2 text-muted">• Pedidos: <?= number_format($itensSemEstoquePedidos, 0, ',', '.') ?></span>
                        <span class="ms-2 text-muted">• Entregues: <?= number_format($itensSemEstoqueEntregues, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon yellow">🟡</div>
                        <div class="kpi-label">Precisa balanço</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalBalanco ?></div>
                    <div class="kpi-foot">
                        <?= (int)$percBalanco ?>% do total
                        <span class="ms-2 text-muted">• Pedidos: <?= number_format($itensBalancoPedidos, 0, ',', '.') ?></span>
                        <span class="ms-2 text-muted">• Entregues: <?= number_format($itensBalancoEntregues, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon green">✅</div>
                        <div class="kpi-label">Finalizados</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalFinal ?></div>
                    <div class="kpi-foot">
                        <?= (int)$percFinal ?>% do total
                        <span class="ms-2 text-muted">• Pedidos: <?= number_format($itensFinalPedidos, 0, ',', '.') ?></span>
                        <span class="ms-2 text-muted">• Entregues: <?= number_format($itensFinalEntregues, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- ✅ Diagnóstico (opcional, mas recomendo MUITO pra fechar a conta) -->
            <?php if ($itensOutros > 0): ?>
                <div class="col-12">
                    <div class="alert alert-warning small mb-0">
                        ⚠️ Existem <strong><?= number_format($itensOutros, 0, ',', '.') ?></strong> itens entregues em registros que não estão em
                        <strong>Sem estoque</strong>, <strong>Precisa balanço</strong> (aberto) ou <strong>Finalizados</strong>.
                        <span class="text-muted">Isso normalmente acontece quando há pedidos pendentes com quantidade_entregue preenchida.</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Gráficos -->
        <div class="row col-12 mb-3 mt-3 charts-row">
            <div class="col-12 col-lg-4 charts-col">
                <div class="card card-soft p-3 h-100">
                    <div class="fw-semibold mb-2">Status do mês</div>
                    <div class="chart-wrapper">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 charts-col">
                <div class="card card-soft p-3 h-100">
                    <div class="fw-semibold mb-2">Alertas</div>
                    <div class="chart-wrapper">
                        <canvas id="chartAlertas" style="margin-top: 50px;"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 charts-col">
                <div class="card card-soft p-3 h-100">
                    <div class="fw-semibold mb-2">Pedidos por dia</div>
                    <div class="chart-wrapper">
                        <canvas id="chartDias" style="margin-top: 50px;"></canvas>
                    </div>
                    <!-- <canvas id="chartDias" height="120"></canvas> -->
                    <div id="diasResumo" class="small text-muted" style="margin-top: 80px; margin-bottom: 0;"></div>
                </div>
            </div>
        </div>

        <div class="col-14 mb-4">
            <div class="card card-soft p-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Top produtos</div>
                    <small class="text-muted">mês selecionado</small>
                </div>
                <canvas id="chartTopProdutos" height="220"></canvas>
            </div>
        </div>

        <div class="col-14 mb-4">
            <div class="card card-soft p-3">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="fw-bold">👤 Solicitantes</div>

                    <div class="d-flex gap-2 align-items-center">
                        <label class="small subtle mb-0">Ver:</label>
                        <select id="selSolicitante" class="form-select form-select-sm" style="min-width:220px;">
                            <option value="">Todos</option>
                        </select>
                    </div>
                </div>

                <div class="chart-wrapper" style="height:360px;">
                    <canvas id="chartSolicitantes"></canvas>
                </div>

                <div id="solicitanteResumo" class="small subtle mt-2"></div>
            </div>
        </div>
    </div>

    <p class="text-center mt-4 text-muted" style="font-size:13px;">
        InterYSY • Sistema Interno
    </p>
    </div>

    <!-- Modal: Detalhe de itens entregues por solicitante -->
    <div class="modal fade" id="modalSolicitanteItens" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Itens entregues</h5>
                        <div class="small text-muted" id="modalSolicitanteSub"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div id="modalSolicitanteLoading" class="py-3 text-muted small">Carregando...</div>

                    <div id="modalSolicitanteContent" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small text-muted">
                                Total entregue: <strong id="modalSolicitanteTotal"></strong>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th style="width:120px;">Tipo</th>
                                        <th style="width:140px;">Entregues</th>
                                        <th style="width:120px;">Pedidos</th>
                                    </tr>
                                </thead>
                                <tbody id="modalSolicitanteTbody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="modalSolicitanteErro" class="alert alert-warning small mb-0" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="../assets/js/relatorio.js" defer></script>
    <script src="../assets/js/theme.js" defer></script>
</body>

</html>