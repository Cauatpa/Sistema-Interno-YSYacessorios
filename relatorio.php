<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/competencia.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/services/fechamento.php';

auth_session_start();
// verifica se está logado
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
        COALESCE(SUM(quantidade_solicitada), 0) AS total_itens_solicitados,
        COALESCE(SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END), 0) AS total_finalizados,
        COALESCE(SUM(CASE WHEN status <> 'finalizado' THEN 1 ELSE 0 END), 0) AS total_pendentes,
        COALESCE(SUM(CASE WHEN sem_estoque = 1 THEN 1 ELSE 0 END), 0) AS total_sem_estoque,
        COALESCE(SUM(CASE WHEN precisa_balanco = 1 AND sem_estoque = 0 THEN 1 ELSE 0 END), 0) AS total_balanco,
        COALESCE(SUM(COALESCE(quantidade_retirada, 0)), 0) AS total_itens_retirados
    FROM retiradas
    WHERE competencia = ?
      AND deleted_at IS NULL
");
$stmt->execute([$competencia]);
$kpis = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

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


$percFinal      = $totalPedidos > 0 ? round(($totalFinal / $totalPedidos) * 100) : 0;
$percSemEstoque = $totalPedidos > 0 ? round(($totalSemEstoque / $totalPedidos) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt-br" data-competencia="<?= h($competencia) ?>" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Relatório do mês</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/relatorio.css">
    <!-- Ícone da aba -->
    <link rel="icon" type="image/png" href="assets/imgs/Y.png">
</head>

<body class="p-3 p-md-4">
    <div class="container">

        <!-- Topbar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <div class="page-title">📊 Relatório do mês</div>
                <div class="subtle small">Resumo rápido do desempenho e status das retiradas</div>
            </div>

            <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
                🌙 Tema escuro
            </button>

            <div class="d-flex gap-2">
                <a href="index.php?competencia=<?= h($competencia) ?>" class="btn btn-outline-secondary btn-sm">← Voltar</a>
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

                <div class="d-flex justify-content-between justify-content-md-end align-items-center gap-2">
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
                    Finalização: <strong><?= (int)$percFinal ?>%</strong> | Sem estoque: <strong><?= (int)$percSemEstoque ?>%</strong>
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
                    <div class="kpi-foot">Soma de quantidade_solicitada</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon gray">📤</div>
                        <div class="kpi-label">Itens retirados</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalRetirados ?></div>
                    <div class="kpi-foot">Soma de quantidade_retirada</div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon gray">⏳</div>
                        <div class="kpi-label">Pendentes</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalPend ?></div>
                    <div class="kpi-foot">Aguardando finalização</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon red">🔴</div>
                        <div class="kpi-label">Sem estoque</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalSemEstoque ?></div>
                    <div class="kpi-foot"><?= (int)$percSemEstoque ?>% do total</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon yellow">🟡</div>
                        <div class="kpi-label">Precisa balanço</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalBalanco ?></div>
                    <div class="kpi-foot">Balanço (sem sem_estoque)</div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card card-soft p-3 kpi">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="icon green">✅</div>
                        <div class="kpi-label">Finalizados</div>
                    </div>
                    <div class="kpi-value mt-2"><?= (int)$totalFinal ?></div>
                    <div class="kpi-foot"><?= (int)$percFinal ?>% do total</div>
                </div>
            </div>

        </div>

        <!-- Gráficos -->
        <div class="row g-3 mt-1">
            <div class="col-12 col-lg-4">
                <div class="card card-soft p-3">
                    <div class="fw-semibold mb-2">Status do mês</div>
                    <canvas id="chartStatus" height="180"></canvas>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card card-soft p-3">
                    <div class="fw-semibold mb-2">Alertas</div>
                    <canvas id="chartAlertas" height="180"></canvas>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card card-soft p-3">
                    <div class="fw-semibold mb-2">Pedidos por dia</div>
                    <canvas id="chartDias" height="180"></canvas>
                </div>
            </div>

            <div class="col-12">
                <div class="card card-soft p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Top 10 produtos (qtd solicitada)</div>
                        <small class="text-muted">mês selecionado</small>
                    </div>
                    <canvas id="chartTopProdutos" height="220"></canvas>
                </div>
            </div>

            <div class="col-12">
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

                    <div class="mt-3">
                        <canvas id="chartSolicitantes" height="120"></canvas>
                    </div>

                    <div id="solicitanteResumo" class="small subtle mt-2"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="./assets/js/relatorio.js" defer></script>
    <script src="assets/js/theme.js" defer></script>
</body>

</html>