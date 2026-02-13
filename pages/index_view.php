<?php
// Permissões (operador+ e admin)
$canOperate = auth_has_role('operador') || auth_has_role('admin'); // operador OU admin
$canAdmin   = auth_has_role('admin');                               // somente admin

// Usuário logado
$u = $u ?? [];
$nomeUsuario = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($nomeUsuario === '') $nomeUsuario = '—';

// Datas
$dataIni = (string)($f['dataIni'] ?? $f['data_ini'] ?? '');
$dataFim = (string)($f['dataFim'] ?? $f['data_fim'] ?? '');

// Paginação
$perPageOptions = $perPageOptions ?? [30, 50, 100];

// itens por página
$perPage = (int)($_GET['per_page'] ?? ($perPage ?? 30));

// validação
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 30;
}

// página atual
$page = isset($page) ? (int)$page : (int)($_GET['p'] ?? 1);
$page = max(1, $page);

// totais (se o controller não mandar)
$total = isset($total) ? (int)$total : 0;
$totalPages = isset($totalPages) ? (int)$totalPages : max(1, (int)ceil($total / $perPage));

// offset
$offset = ($page - 1) * $perPage;

// Helper de URL para paginação preservando filtros atuais
function page_url(int $p): string
{
    $q = $_GET;
    $q['p'] = $p;
    return 'index.php?' . http_build_query($q);
}

// URL de retorno após ações
$returnUrl = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light"
    data-toast="<?= htmlspecialchars((string)$toast) ?>"
    data-highlight-id="<?= (int)$highlightId ?>">

<head>
    <meta charset="UTF-8">
    <title>Controle de Estoque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS próprio -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Ícone da aba -->
    <link rel="icon" type="image/png" href="assets/imgs/Y.png">
</head>

<body class="p-3">

    <!-- Topo: título + usuário + ações -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mb-3">
        <h2 class="text-center mb-0">📦 Controle de Retirada do Estoque</h2>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="small text-muted">
                👤 <?= htmlspecialchars($nomeUsuario) ?>
                <?php if ($canAdmin): ?>
                    <span class="badge text-bg-dark ms-1">admin</span>
                <?php elseif ($canOperate): ?>
                    <span class="badge text-bg-primary ms-1">operador</span>
                <?php else: ?>
                    <span class="badge text-bg-secondary ms-1">visualizador</span>
                <?php endif; ?>
            </div>

            <a href="index.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>

            <a href="/InterYSY/pages/relatorio_retiradas.php?competencia=<?= urlencode($competencia) ?>"
                class="btn btn-outline-success btn-sm">Relatório</a>
            <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
                🌙 Tema escuro
            </button>

            <form method="POST" action="logout.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('logout')) ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <!-- ======================
         DASHBOARD (somente PC)
         ====================== -->
    <div class="d-none d-md-block">
        <div class="row g-2 mb-3 dashboard-cards">

            <div class="col-6 col-md-3">
                <a class="text-decoration-none"
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'todos', 'p' => 1])) ?>">
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
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'pendentes', 'p' => 1])) ?>">
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
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'balanco', 'p' => 1])) ?>">
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
                    href="<?= htmlspecialchars(url_com_query('index.php', $_GET, ['competencia' => $competencia, 'filtro' => 'sem_estoque', 'p' => 1])) ?>">
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
        <form method="GET" action="index.php" class="card p-2 mb-3">
            <input type="hidden" name="page" value="retiradas">

            <input type="hidden" name="p" value="1">
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
                        <option value="ouro" <?= $tipo === 'ouro'  ? 'selected' : '' ?>>Ouro</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="todos" <?= $statusFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendentes" <?= $statusFiltro === 'pendentes' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="finalizados" <?= $statusFiltro === 'finalizados' ? 'selected' : '' ?>>Finalizados</option>
                        <option value="sem_estoque" <?= $statusFiltro === 'sem_estoque' ? 'selected' : '' ?>>Sem Estoque</option>
                        <option value="estoque_preenchido" <?= $statusFiltro === 'estoque_preenchido' ? 'selected' : '' ?>>Estoque preenchido</option>
                        <option value="balanco_feito" <?= $statusFiltro === 'balanco_feito' ? 'selected' : '' ?>>Balanço feito</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">De</label>
                    <input type="date" name="data_ini" class="form-control" value="<?= htmlspecialchars($dataIni) ?>">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Até</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>">
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label mb-1">Por pág.</label>
                    <select name="per_page" class="form-select">
                        <?php foreach ($perPageOptions as $opt): ?>
                            <option value="<?= (int)$opt ?>" <?= ((int)$opt === (int)$perPage) ? 'selected' : '' ?>>
                                <?= (int)$opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="balanco" value="1" id="fBalanco" <?= ((int)$soBalanco === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fBalanco">Só balanço</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sem_estoque" value="1" id="fSemEstoque" <?= ((int)$soSemEstoque === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fSemEstoque">Só sem estoque</label>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Filtrar</button>

                    <a class="btn btn-outline-secondary w-100"
                        href="<?= htmlspecialchars(url_com_query('index.php', $_GET, [
                                    'page' => 'retiradas',   // ✅ mantém o módulo
                                    'filtro' => 'todos',
                                    'q' => null,
                                    'tipo' => null,
                                    'status' => null,
                                    'balanco' => null,
                                    'sem_estoque' => null,
                                    'data_ini' => null,
                                    'data_fim' => null,
                                    'p' => null,
                                    'per_page' => null,
                                ])) ?>">
                        Limpar
                    </a>

                </div>
            </div>
        </form>
    </div>

    <!-- Barra superior: Mês + Fechar/Reabrir mês -->
    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-stretch mb-3">

        <!-- Selecionar competência -->
        <form method="GET" action="index.php" class="d-flex gap-2 align-items-center">
            <label class="fw-bold">Mês:</label>

            <!-- ✅ ESSENCIAL: mantém na rota correta -->
            <input type="hidden" name="page" value="retiradas">

            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFiltro) ?>">
            <input type="hidden" name="balanco" value="<?= (int)$soBalanco ?>">
            <input type="hidden" name="sem_estoque" value="<?= (int)$soSemEstoque ?>">
            <input type="hidden" name="data_ini" value="<?= htmlspecialchars($dataIni) ?>">
            <input type="hidden" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>">
            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">

            <!-- ✅ ao trocar mês, volta para página 1 -->
            <input type="hidden" name="p" value="1">

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

        <!-- Fechar/Reabrir mês (somente admin) -->
        <div class="d-flex gap-2 align-items-center">
            <?php if (!$canAdmin): ?>
                <button type="button" class="btn btn-outline-secondary" disabled>🔒 Sem permissão</button>

            <?php elseif ($mesFechado): ?>
                <button type="button" class="btn btn-outline-secondary" disabled>🔒 Mês fechado</button>

                <form method="POST" action="actions/reabrir_mes.php" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('reabrir_mes')) ?>">
                    <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
                    <input name="confirm" class="form-control" placeholder="REABRIR <?= htmlspecialchars($competencia) ?>" required>
                    <button type="submit" class="btn btn-warning">🔓 Reabrir</button>
                </form>

            <?php else: ?>
                <form method="POST" action="actions/fechar_mes.php" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('fechar_mes')) ?>">
                    <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
                    <input type="hidden" name="usuario" value="<?= htmlspecialchars($nomeUsuario) ?>">
                    <input name="confirm" class="form-control" placeholder="FECHAR <?= htmlspecialchars($competencia) ?>" required>
                    <button type="submit" class="btn btn-danger">📅 Fechar</button>
                </form>
            <?php endif; ?>

            <?php if ($canAdmin && $mesFechado): ?>
                <form method="POST" action="actions/exportar_mes.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('exportar_mes')) ?>">
                    <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
                    <button type="submit" class="btn btn-success btn-sm">📥 XLSX</button>
                </form>

                <form method="POST" action="actions/exportar_mes.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('exportar_mes')) ?>">
                    <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
                    <input type="hidden" name="regen" value="1">
                    <button type="submit" class="btn btn-outline-success btn-sm"
                        onclick="return confirm('Regerar o XLSX do mês <?= htmlspecialchars($competencia) ?>?');">
                        ♻ Regerar
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botão Novo Pedido -->
    <div class="d-flex justify-content-between mb-3">
        <button
            type="button"
            class="btn btn-primary btn-lg w-100 w-md-auto"
            id="btnNovoPedido"
            data-bs-toggle="modal"
            data-bs-target="#modalNovoPedido"
            aria-controls="modalNovoPedido"
            aria-haspopup="dialog"
            <?= ($mesFechado || !$canOperate) ? 'disabled aria-disabled="true"' : '' ?>>
            ➕ Novo Pedido
        </button>
    </div>

    <?php if ($mesFechado): ?>
        <div class="alert alert-warning text-center">
            🔒 Este mês (<?= htmlspecialchars($competencia) ?>) está <strong>FECHADO</strong>.
            Operadores não podem criar/finalizar.
            <?php if ($canAdmin): ?> Admin pode editar e reabrir. <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!$canOperate): ?>
        <div class="alert alert-info text-center">
            👁️ Você está em modo <strong>visualização</strong>. Ações estão desativadas.
        </div>
    <?php endif; ?>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="">
                <tr>
                    <th class="d-none d-md-table-cell">🕒 Pedido</th>
                    <th>📦 Produto</th>
                    <th>🔢 Quantidades</th>

                    <th class="d-none d-md-table-cell">🔖 Tipo</th>
                    <th class="d-none d-md-table-cell">👤 Solicitante</th>
                    <th class="d-none d-md-table-cell">⏱ Finalização</th>
                    <th class="d-none d-md-table-cell">👷 Estoque</th>

                    <th class="d-none d-md-table-cell">📌 Status</th>
                    <th class="d-table-cell d-md-none">🔖 Tipo</th>

                    <th>⚙ Ação</th>
                </tr>
            </thead>

            <tbody>
                <!-- Linhas da tabela -->
                <?php if (empty($retiradas)): ?>
                    <tr>
                        <td colspan="10" class="text-muted py-4">
                            Nenhum registro encontrado com os filtros atuais.
                        </td>
                    </tr>
                    <!--  -->
                <?php else: ?>
                    <?php foreach ($retiradas as $r):
                        $info = retirada_status_info($r);
                        $tipoLinha = htmlspecialchars(ucfirst((string)($r['tipo'] ?? '')));
                        $id = (int)($r['id'] ?? 0);
                        $isFinalizado = (($r['status'] ?? '') === 'finalizado');
                        $isPendente = !$isFinalizado;
                    ?>
                        <!--  -->
                        <tr
                            class="<?= htmlspecialchars($info['classe']) ?>"
                            data-id="<?= $id ?>"
                            data-retirada-id="<?= $id ?>"
                            data-pendente="<?= ($isPendente ? '1' : '0') ?>"
                            data-status="<?= htmlspecialchars((string)($r['status'] ?? '')) ?>">
                            <td class="d-none d-md-table-cell">
                                <?= date('d/m H:i', strtotime((string)$r['data_pedido'])) ?>
                            </td>
                            <td><strong><?= htmlspecialchars((string)$r['produto']) ?></strong></td>
                            <td class="text-start">
                                <div class="fw-semibold">Solicitado: <?= (int)($r['quantidade_solicitada'] ?? 0) ?></div>
                                <div class="text-muted small">
                                    Entregue:
                                    <?php
                                    $entregue = $r['quantidade_retirada'];
                                    echo ($entregue === null || $entregue === '') ? '—' : (int)$entregue;
                                    ?>
                                </div>
                            </td>

                            <td class="d-none d-md-table-cell"><?= $tipoLinha ?></td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars((string)($r['solicitante'] ?? '')) ?></td>

                            <td class="d-none d-md-table-cell">
                                <?= !empty($r['data_finalizacao']) ? date('d/m H:i', strtotime((string)$r['data_finalizacao'])) : '—' ?>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <?= htmlspecialchars((string)($r['responsavel_estoque'] ?? '—')) ?>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <strong><?= htmlspecialchars((string)$info['texto']) ?></strong>
                            </td>

                            <td class="d-table-cell d-md-none"><strong><?= $tipoLinha ?></strong></td>

                            <td>
                                <!-- layout compacto: não quebra fácil -->
                                <div class="d-grid gap-1" style="min-width: 100px;">
                                    <?php
                                    $isSemEstoque = ((int)($r['sem_estoque'] ?? 0) === 1);
                                    ?>

                                    <?php if (($canOperate || $canAdmin) && !$mesFechado && !$isFinalizado && !$isSemEstoque): ?>
                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm"
                                            data-open-finalizar
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalFinalizar<?= $id ?>">
                                            ✅ Finalizar
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($canOperate && !$mesFechado && (int)$r['precisa_balanco'] === 1 && (int)$r['balanco_feito'] === 0): ?>
                                        <button
                                            type="button"
                                            class="btn btn-outline-warning btn-sm"
                                            data-balanco="<?= (int)$r['id'] ?>">
                                            ⚖️ Fazer balanço
                                        </button>
                                    <?php endif; ?>

                                    <?php
                                    $isSemEstoque = ((int)($r['sem_estoque'] ?? 0) === 1);
                                    ?>

                                    <?php if (($canOperate || $canAdmin) && !$mesFechado && !$isFinalizado && $isSemEstoque): ?>
                                        <form
                                            method="POST"
                                            action="/InterYSY/actions/estoque_chegou.php"
                                            onsubmit="return confirm('Confirmar que o estoque chegou e finalizar este pedido?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('estoque_chegou')) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                            <!-- ✅ volta pra mesma tela (com filtros/página) -->
                                            <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl ?? '/InterYSY/index.php') ?>">

                                            <button type="submit" class="btn btn-outline-success btn-sm w-100">
                                                📦 Estoque chegou
                                            </button>
                                        </form>
                                    <?php endif; ?>


                                    <?php if ($canAdmin): ?>
                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditar<?= $id ?>">
                                            ✏ Editar
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($canAdmin && !$mesFechado): ?>
                                        <button type="button"
                                            class="btn btn-outline-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalExcluir<?= $id ?>">
                                            🗑 Excluir
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!$canAdmin && !$canOperate): ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
            <div class="small text-muted">
                Mostrando <?= count($retiradas) ?> de <?= (int)$total ?> |
                Página <?= (int)$page ?> / <?= (int)$totalPages ?>
            </div>

            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(page_url(max(1, $page - 1))) ?>">← Anterior</a>
                    </li>

                    <?php if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(page_url(1)) ?>">1</a></li>
                        <?php if ($start > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(page_url($i)) ?>"><?= (int)$i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(page_url($totalPages)) ?>"><?= (int)$totalPages ?></a></li>
                    <?php endif; ?>

                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(page_url(min($totalPages, $page + 1))) ?>">Próxima →</a>
                    </li>
                </ul>
            </nav>
        </div>

        <p class="text-center mt-4 text-muted" style="font-size:13px;">
            InterYSY • Sistema Interno
        </p>
    <?php endif; ?>

    <!-- Modais -->
    <?php include 'modals/_load_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toast UI -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="appToastBody" class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js" defer></script>
    <script src="assets/js/ux_atalhos.js" defer></script>
    <script src="assets/js/theme.js" defer></script>
    <script>
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-balanco]');
            if (!btn) return;

            const id = btn.dataset.balanco;
            if (!id) return;

            if (!confirm('Confirmar balanço realizado?')) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', '<?= csrf_token('balanco_feito') ?>');

            let res, text, out = null;

            try {
                res = await fetch('actions/retirada_balanco_feito.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                text = await res.text();
                try {
                    out = JSON.parse(text);
                } catch (_) {
                    out = null;
                }

                if (res.ok && out && out.ok) {
                    location.reload();
                    return;
                }

                // se não veio JSON, mostra um erro “útil”
                alert((out && out.error) ? out.error : ('Erro ao marcar balanço. Resposta: ' + (text?.slice(0, 120) || 'vazia')));
            } catch (err) {
                alert('Erro ao marcar balanço: ' + (err?.message || err));
            }
        });
    </script>

    <?php require __DIR__ . '/../modals/modal_minha_senha.php'; ?>
</body>

</html>