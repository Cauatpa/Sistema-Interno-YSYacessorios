<?php
require 'config/database.php';
require_once __DIR__ . '/helpers/competencia.php';
require_once __DIR__ . '/helpers/ui_retiradas.php';
require_once __DIR__ . '/helpers/filtros_retiradas.php';
require_once __DIR__ . '/helpers/csrf.php';

csrf_session_start();
// ---------------------
// Parâmetros

$toast = $_GET['toast'] ?? '';
$highlightId = (int)($_GET['highlight_id'] ?? 0);

$competencia = $_GET['competencia'] ?? competencia_atual();
if (!competencia_valida($competencia)) {
    $competencia = competencia_atual();
}

// Normaliza filtros
$f = normaliza_filtros($_GET);
$filtro = $f['filtro'];
$busca = $f['busca'];
$tipo = $f['tipo'];
$statusFiltro = $f['statusFiltro'];
$soBalanco = $f['soBalanco'];
$soSemEstoque = $f['soSemEstoque'];

// ---------------------
// Lista de meses
// ---------------------
$stmtMeses = $pdo->query("SELECT DISTINCT competencia FROM retiradas ORDER BY competencia DESC");
$mesesDisponiveis = $stmtMeses->fetchAll(PDO::FETCH_COLUMN);

// ---------------------
// Mês fechado
// ---------------------
$stmtFechado = $pdo->prepare("SELECT 1 FROM fechamentos WHERE competencia = ? LIMIT 1");
$stmtFechado->execute([$competencia]);
$mesFechado = (bool) $stmtFechado->fetchColumn();

// ---------------------
// Dashboard contadores
// ---------------------
$stmtDash = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status <> 'finalizado' THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) AS finalizados,
        SUM(CASE WHEN precisa_balanco = 1 AND sem_estoque = 0 THEN 1 ELSE 0 END) AS balanco,
        SUM(CASE WHEN sem_estoque = 1 THEN 1 ELSE 0 END) AS sem_estoque
    FROM retiradas
    WHERE competencia = ? AND deleted_at IS NULL
");
$stmtDash->execute([$competencia]);
$dash = $stmtDash->fetch(PDO::FETCH_ASSOC) ?: [
    'total' => 0,
    'pendentes' => 0,
    'finalizados' => 0,
    'balanco' => 0,
    'sem_estoque' => 0
];

// ---------------------
// Listagem
// ---------------------
list($where, $params) = montar_where_retiradas($competencia, $f);

$stmt = $pdo->prepare("SELECT * FROM retiradas {$where} ORDER BY data_pedido DESC");
$stmt->execute($params);
$retiradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Controle de Estoque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS próprio -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="p-3">

    <h2 class="text-center mb-4">📦 Controle de Retirada do Estoque</h2>

    <!-- ======================
     DASHBOARD (somente PC)
     ====================== -->
    <div class="d-none d-md-block">
        <div class="row g-2 mb-3 dashboard-cards">

            <div class="col-6 col-md-3">
                <a class="text-decoration-none"
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'todos'])) ?>">
                    <div class="card <?= card_class($filtro, 'todos') ?>">
                        <div class="card-body text-center">
                            <div class="fw-bold">📊 Total</div>
                            <div class="fs-4"><?= (int)$dash['total'] ?></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a class="text-decoration-none"
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'pendentes'])) ?>">
                    <div class="card <?= card_class($filtro, 'pendentes') ?>">
                        <div class="card-body text-center">
                            <div class="fw-bold">⏳ Pendentes</div>
                            <div class="fs-4"><?= (int)$dash['pendentes'] ?></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a class="text-decoration-none"
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'balanco'])) ?>">
                    <div class="card <?= card_class($filtro, 'balanco') ?>">
                        <div class="card-body text-center">
                            <div class="fw-bold">🟡 Balanço</div>
                            <div class="fs-4"><?= (int)$dash['balanco'] ?></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a class="text-decoration-none"
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'sem_estoque'])) ?>">
                    <div class="card <?= card_class($filtro, 'sem_estoque') ?>">
                        <div class="card-body text-center">
                            <div class="fw-bold">🔴 Sem estoque</div>
                            <div class="fs-4"><?= (int)$dash['sem_estoque'] ?></div>
                        </div>
                    </div>
                </a>
            </div>

        </div>

        <!-- ======================
         Pesquisa + Filtros (somente PC)
         ====================== -->
        <form method="GET" class="card p-2 mb-3">
            <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">

            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">Pesquisar</label>
                    <input type="text" name="q" class="form-control"
                        placeholder="Produto, solicitante ou responsável..."
                        value="<?= htmlspecialchars($busca) ?>">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="todos" <?= $tipo === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="prata" <?= $tipo === 'prata' ? 'selected' : '' ?>>Prata</option>
                        <option value="ouro" <?= $tipo === 'ouro' ? 'selected' : '' ?>>Ouro</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="todos" <?= $statusFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendentes" <?= $statusFiltro === 'pendentes' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="finalizados" <?= $statusFiltro === 'finalizados' ? 'selected' : '' ?>>Finalizados</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="balanco" value="1" id="fBalanco"
                            <?= ((int)$soBalanco === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="fBalanco">Só balanço</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sem_estoque" value="1" id="fSemEstoque"
                            <?= ((int)$soSemEstoque === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="fSemEstoque">Só sem estoque</label>
                    </div>
                </div>

                <div class="col-6 col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Filtrar</button>

                    <a class="btn btn-outline-secondary w-100"
                        href="<?= htmlspecialchars(url_com_query('index.php', $_GET, [
                                    'filtro' => 'todos',
                                    'q' => null,
                                    'tipo' => null,
                                    'status' => null,
                                    'balanco' => null,
                                    'sem_estoque' => null
                                ])) ?>">
                        Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Barra superior: Mês + Fechar mês -->
    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-stretch mb-3">
        <form method="GET" class="d-flex gap-2 align-items-center">

            <label class="fw-bold">Mês:</label>

            <!-- mantém filtros ao trocar mês -->
            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFiltro) ?>">
            <input type="hidden" name="balanco" value="<?= (int)$soBalanco ?>">
            <input type="hidden" name="sem_estoque" value="<?= (int)$soSemEstoque ?>">


            <select name="competencia" class="form-select" onchange="this.form.submit()">
                <?php if (!in_array($competencia, $mesesDisponiveis, true)): ?>
                    <option value="<?= htmlspecialchars($competencia) ?>" selected>
                        <?= htmlspecialchars($competencia) ?> (atual)
                    </option>
                <?php endif; ?>

                <?php foreach ($mesesDisponiveis as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $m === $competencia ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <noscript><button class="btn btn-secondary">Filtrar</button></noscript>
        </form>

        <form method="POST" action="fechar_mes.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('fechar_mes')) ?>">
            <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
            <input type="hidden" name="usuario" value="Cauã">

            <?php if ($mesFechado): ?>
                <button type="button" class="btn btn-outline-secondary" disabled>🔒 Mês fechado</button>
            <?php else: ?>
                <input name="confirm" class="form-control" placeholder="FECHAR <?= htmlspecialchars($competencia) ?>" required>
                <button type="submit" class="btn btn-danger">📅 Fechar mês</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Botão Novo Pedido -->
    <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-primary btn-lg w-100 w-md-auto"
            data-bs-toggle="modal"
            data-bs-target="#modalNovoPedido"
            <?= $mesFechado ? 'disabled' : '' ?>>
            ➕ Novo Pedido
        </button>
    </div>

    <?php if ($mesFechado): ?>
        <div class="alert alert-warning text-center">
            🔒 Este mês (<?= htmlspecialchars($competencia) ?>) está <strong>FECHADO</strong>. Não é possível criar ou finalizar pedidos nele.
        </div>
    <?php endif; ?>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>🕒 Pedido</th>
                    <th>📦 Produto</th>
                    <th>🔢 Qtd (Lote)</th>

                    <th class="d-none d-md-table-cell">🔖 Tipo</th>
                    <th class="d-none d-md-table-cell">👤 Solicitante</th>
                    <th class="d-none d-md-table-cell">⏱ Finalização</th>
                    <th class="d-none d-md-table-cell">👷 Estoque</th>

                    <th class="d-none d-md-table-cell">📌 Status</th>
                    <th class="d-table-cell d-md-none">🔖 Tipo</th>

                    <th>⚙ Ação</th>
                </tr>
            </thead>

            <!-- Tabela de Retiradas -->
            <tbody>
                <?php foreach ($retiradas as $r):
                    $info = retirada_status_info($r);
                ?>
                    <tr class="<?= htmlspecialchars($info['classe']) ?>" data-id="<?= (int)$r['id'] ?>">
                        <td><?= date('d/m H:i', strtotime($r['data_pedido'])) ?></td>
                        <td><strong><?= htmlspecialchars($r['produto']) ?></strong></td>
                        <td><?= (int)$r['quantidade_solicitada'] ?></td>

                        <td class="d-none d-md-table-cell"><?= ucfirst($r['tipo']) ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($r['solicitante']) ?></td>

                        <td class="d-none d-md-table-cell">
                            <?= $r['data_finalizacao'] ? date('d/m H:i', strtotime($r['data_finalizacao'])) : '—' ?>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <?= $r['responsavel_estoque'] ?? '—' ?>
                        </td>

                        <td class="d-none d-md-table-cell"><strong><?= htmlspecialchars($info['texto']) ?></strong></td>
                        <td class="d-table-cell d-md-none"><strong><?= ucfirst($r['tipo']) ?></strong></td>

                        <td>
                            <?php if (!$mesFechado): ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php if (($r['status'] ?? '') !== 'finalizado'): ?>
                                        <button class="btn btn-success w-100"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalFinalizar<?= (int)$r['id'] ?>">
                                            ✅ Finalizar
                                        </button>
                                    <?php endif; ?>

                                    <button type="button"
                                        class="btn btn-outline-danger w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluir<?= (int)$r['id'] ?>">
                                        🗑 Excluir
                                    </button>
                                </div>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

    <!-- Modais -->
    <?php include 'modals/_load_modals.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="appToastBody" class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <script>
        (function() {
            const toastType = "<?= htmlspecialchars($toast) ?>";
            const highlightId = <?= (int)$highlightId ?>;

            // Toast (finalizado / excluido)
            if (toastType) {
                const el = document.getElementById('appToast');
                const body = document.getElementById('appToastBody');

                if (el && body && window.bootstrap) {
                    el.classList.remove('text-bg-success', 'text-bg-danger');

                    if (toastType === 'finalizado') {
                        el.classList.add('text-bg-success');
                        body.textContent = '✅ Pedido finalizado com sucesso!';
                    } else if (toastType === 'excluido') {
                        el.classList.add('text-bg-danger');
                        body.textContent = '🗑 Pedido excluído com sucesso!';
                    } else {
                        return;
                    }

                    new bootstrap.Toast(el, {
                        delay: 2500
                    }).show();
                }
            }

            // highlight de linha
            if (highlightId > 0) {
                const row = document.querySelector('tr[data-id="' + highlightId + '"]');
                if (row) {
                    row.classList.add('row-highlight');
                    setTimeout(() => row.classList.remove('row-highlight'), 3500);
                }
            }

            // limpa URL
            const url = new URL(window.location.href);
            if (url.searchParams.has('toast') || url.searchParams.has('highlight_id')) {
                url.searchParams.delete('toast');
                url.searchParams.delete('highlight_id');
                window.history.replaceState({}, document.title, url.toString());
            }
        })();
    </script>

</body>

</html>