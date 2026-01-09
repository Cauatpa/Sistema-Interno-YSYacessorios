<?php
// Permissões (operador+ e admin)
$canOperate = auth_has_role('operador'); // operador ou admin
$canAdmin   = auth_has_role('admin');    // somente admin

// Usuário logado (o controller deve fornecer $u; aqui deixo seguro)
$u = $u ?? [];
$nomeUsuario = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($nomeUsuario === '') $nomeUsuario = '—';

// Datas (compatível com chaves diferentes, caso seu normalizador use outro padrão)
$dataIni = (string)($f['dataIni'] ?? $f['data_ini'] ?? '');
$dataFim = (string)($f['dataFim'] ?? $f['data_fim'] ?? '');
?>

<!DOCTYPE html>
<html lang="pt-br"
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

            <?php if ($canAdmin): ?>
                <a href="usuarios.php" class="btn btn-outline-primary btn-sm">Usuários</a>
            <?php endif; ?>

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
                <!-- Linha 1 -->
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
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">De</label>
                    <input type="date" name="data_ini" class="form-control"
                        value="<?= htmlspecialchars($dataIni) ?>">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Até</label>
                    <input type="date" name="data_fim" class="form-control"
                        value="<?= htmlspecialchars($dataFim) ?>">
                </div>

                <!-- Linha 2 -->
                <div class="col-12 col-md-8">
                    <div class="d-flex flex-wrap gap-3">
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
                </div>

                <div class="col-12 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Filtrar</button>

                    <a class="btn btn-outline-secondary w-100"
                        href="<?= htmlspecialchars(url_com_query('index.php', $_GET, [
                                    'filtro' => 'todos',
                                    'q' => null,
                                    'tipo' => null,
                                    'status' => null,
                                    'balanco' => null,
                                    'sem_estoque' => null,
                                    'data_ini' => null,
                                    'data_fim' => null,
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
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="fw-bold">Mês:</label>

            <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFiltro) ?>">
            <input type="hidden" name="balanco" value="<?= (int)$soBalanco ?>">
            <input type="hidden" name="sem_estoque" value="<?= (int)$soSemEstoque ?>">

            <!-- ✅ preserva datas ao trocar mês -->
            <input type="hidden" name="data_ini" value="<?= htmlspecialchars($dataIni) ?>">
            <input type="hidden" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>">

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
                    <button type="submit" class="btn btn-warning">🔓 Reabrir mês</button>
                </form>

            <?php else: ?>
                <form method="POST" action="fechar_mes.php" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('fechar_mes')) ?>">
                    <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
                    <input type="hidden" name="usuario" value="<?= htmlspecialchars($nomeUsuario) ?>">

                    <input name="confirm" class="form-control" placeholder="FECHAR <?= htmlspecialchars($competencia) ?>" required>
                    <button type="submit" class="btn btn-danger">📅 Fechar mês</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botão Novo Pedido -->
    <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-primary btn-lg w-100 w-md-auto"
            data-bs-toggle="modal"
            data-bs-target="#modalNovoPedido"
            <?= ($mesFechado || !$canOperate) ? 'disabled' : '' ?>>
            ➕ Novo Pedido
        </button>
    </div>

    <?php if ($mesFechado): ?>
        <div class="alert alert-warning text-center">
            🔒 Este mês (<?= htmlspecialchars($competencia) ?>) está <strong>FECHADO</strong>.
            Operadores não podem criar/finalizar/excluir.
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

            <tbody>
                <?php foreach ($retiradas as $r):
                    $info = retirada_status_info($r);
                    $tipoLinha = htmlspecialchars(ucfirst((string)($r['tipo'] ?? '')));
                    $id = (int)($r['id'] ?? 0);
                    $isFinalizado = (($r['status'] ?? '') === 'finalizado');
                ?>
                    <tr class="<?= htmlspecialchars($info['classe']) ?>" data-id="<?= $id ?>">
                        <td><?= date('d/m H:i', strtotime((string)$r['data_pedido'])) ?></td>
                        <td><strong><?= htmlspecialchars((string)$r['produto']) ?></strong></td>
                        <td><?= (int)($r['quantidade_solicitada'] ?? 0) ?></td>

                        <td class="d-none d-md-table-cell"><?= $tipoLinha ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars((string)($r['solicitante'] ?? '')) ?></td>

                        <td class="d-none d-md-table-cell">
                            <?= !empty($r['data_finalizacao']) ? date('d/m H:i', strtotime((string)$r['data_finalizacao'])) : '—' ?>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <?= htmlspecialchars((string)($r['responsavel_estoque'] ?? '—')) ?>
                        </td>

                        <td class="d-none d-md-table-cell"><strong><?= htmlspecialchars((string)$info['texto']) ?></strong></td>
                        <td class="d-table-cell d-md-none"><strong><?= $tipoLinha ?></strong></td>

                        <td>
                            <div class="d-flex flex-column gap-2">
                                <!-- Finalizar: somente operador+, mês aberto, e se não finalizado -->
                                <?php if ($canOperate && !$mesFechado && !$isFinalizado): ?>
                                    <button class="btn btn-success w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalFinalizar<?= $id ?>">
                                        ✅ Finalizar
                                    </button>
                                <?php endif; ?>

                                <!-- Editar: somente admin (mesmo mês fechado) -->
                                <?php if ($canAdmin): ?>
                                    <button type="button"
                                        class="btn btn-outline-primary w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditar<?= $id ?>">
                                        ✏ Editar
                                    </button>
                                <?php endif; ?>

                                <!-- Excluir: somente operador+ e mês aberto -->
                                <?php if ($canAdmin && !$mesFechado): ?>
                                    <button type="button"
                                        class="btn btn-outline-danger w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalExcluir<?= $id ?>">
                                        🗑 Excluir
                                    </button>
                                <?php endif; ?>

                                <?php if (!$canAdmin && !$canOperate): ?>
                                    —
                                <?php endif; ?>
                            </div>
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

    <!-- Toast UI -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="appToastBody" class="toast-body"></div>
                <button type="button" class="btn btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>

    <!-- Seu JS (toast/highlight) -->
    <script src="assets/js/app.js" defer></script>

</body>

</html>