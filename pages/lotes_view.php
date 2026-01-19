<?php
// pages/lotes_view.php

// Permissões
$canOperate = auth_has_role('operador'); // operador ou admin
$canAdmin   = auth_has_role('admin');    // somente admin

// Usuário logado (o controller fornece $u; aqui deixo seguro)
$u = $u ?? [];
$nomeUsuario = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($nomeUsuario === '') $nomeUsuario = '—';

// Paginação (defensivo)
$perPageOptions = $perPageOptions ?? [30, 50, 100];
$perPage = (int)($_GET['per_page'] ?? ($perPage ?? 30));
if (!in_array($perPage, $perPageOptions, true)) $perPage = 30;

$page = isset($page) ? (int)$page : (int)($_GET['p'] ?? 1);
$page = max(1, $page);

$total = isset($total) ? (int)$total : 0;
$totalPages = isset($totalPages) ? (int)$totalPages : max(1, (int)ceil($total / $perPage));

// Helper de URL para paginação preservando filtros atuais
function page_url(int $p): string
{
    $q = $_GET;
    $q['p'] = $p;
    return 'lotes.php?' . http_build_query($q);
}

$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? 'todos');
$dataIni = (string)($_GET['data_ini'] ?? '');
$dataFim = (string)($_GET['data_fim'] ?? '');
?>

<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light"
    data-toast="<?= htmlspecialchars((string)($toast ?? '')) ?>"
    data-highlight-id="<?= (int)($highlightId ?? 0) ?>">

<head>
    <meta charset="UTF-8">
    <title>Lotes</title>
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
        <h2 class="text-center mb-0">📦Controle Lotes</h2>

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

            <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
                🌙 Tema escuro
            </button>

            <form method="POST" action="logout.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('logout')) ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="card p-2 mb-3">
        <input type="hidden" name="p" value="1">

        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label mb-1">Pesquisar</label>
                <input type="text" name="q" class="form-control"
                    placeholder="Código do lote ou fornecedor..."
                    value="<?= htmlspecialchars($q) ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="todos" <?= $status === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="aberto" <?= $status === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                    <option value="conferido" <?= $status === 'conferido' ? 'selected' : '' ?>>Conferido</option>
                    <option value="fechado" <?= $status === 'fechado' ? 'selected' : '' ?>>Fechado</option>
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

            <div class="col-12 col-md-6 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>

                <a class="btn btn-outline-secondary w-100"
                    href="<?= htmlspecialchars('lotes.php') ?>">
                    Limpar
                </a>
            </div>

            <div class="col-12 col-md-6 d-flex justify-content-md-end">
                <button type="button"
                    class="btn btn-success w-100 w-md-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#modalNovoLote"
                    <?= !$canOperate ? 'disabled' : '' ?>>
                    ➕ Novo Lote
                </button>
            </div>
        </div>
    </form>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="">
                <tr>
                    <th>#</th>
                    <th>📦 Código</th>
                    <th class="d-none d-md-table-cell">📅 Recebimento</th>
                    <th class="d-none d-md-table-cell">🏷 Fornecedor</th>
                    <th>📄 Itens</th>
                    <th>⚠️ Divergências</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">👤 Criado por</th>
                    <th>⚙ Ação</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($lotes ?? [])): ?>
                    <tr>
                        <td colspan="9" class="text-muted py-4">
                            Nenhum lote encontrado com os filtros atuais.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach (($lotes ?? []) as $l):
                        $id = (int)($l['id'] ?? 0);
                        $st = (string)($l['status'] ?? 'aberto');

                        $badge = 'text-bg-primary';
                        $stTxt = 'Aberto';
                        if ($st === 'conferido') {
                            $badge = 'text-bg-warning';
                            $stTxt = 'Conferido';
                        }
                        if ($st === 'fechado') {
                            $badge = 'text-bg-secondary';
                            $stTxt = 'Fechado';
                        }

                        $criadoPor = trim((string)($l['criado_por_nome'] ?? ''));
                        if ($criadoPor === '') $criadoPor = trim((string)($l['criado_por_usuario'] ?? '—'));
                        if ($criadoPor === '') $criadoPor = '—';

                        $div = (int)($l['divergencias'] ?? 0);
                    ?>
                        <tr data-id="<?= $id ?>">
                            <td><?= $id ?></td>

                            <td class="text-start">
                                <strong><?= htmlspecialchars((string)($l['codigo'] ?? '')) ?></strong>
                                <?php if (!empty($l['observacoes'])): ?>
                                    <div class="text-muted small"><?= htmlspecialchars((string)$l['observacoes']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <?= !empty($l['data_recebimento'])
                                    ? date('d/m/Y', strtotime((string)$l['data_recebimento']))
                                    : '—' ?>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <?= htmlspecialchars((string)($l['fornecedor'] ?? '—')) ?>
                            </td>

                            <td><?= (int)($l['itens_total'] ?? 0) ?></td>

                            <td>
                                <?php if ($div > 0): ?>
                                    <span class="badge text-bg-danger"><?= $div ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">0</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($stTxt) ?></span>
                            </td>

                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($criadoPor) ?></td>

                            <td>
                                <div class="d-grid gap-1" style="min-width: 130px;">
                                    <a href="<?= htmlspecialchars('lote.php?id=' . $id) ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        👁 Ver
                                    </a>

                                    <?php if ($canAdmin && $st !== 'fechado'): ?>
                                        <a href="<?= htmlspecialchars('lote.php?id=' . $id . '&edit=1') ?>"
                                            class="btn btn-outline-secondary btn-sm">
                                            ✏ Editar
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="text-muted small mt-1">
                                    * `lote.php` é a próxima tela (detalhe/itens)
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </div>

    <!-- Paginação -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
            <div class="small text-muted">
                Mostrando <?= count($lotes ?? []) ?> de <?= (int)$total ?> |
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
    <?php endif; ?>

    <!-- Modal: Novo Lote (MVP simples) -->
    <div class="modal fade" id="modalNovoLote" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="actions/lotes_salvar.php" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lotes_salvar')) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">➕ Novo Lote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Código</label>
                            <input name="codigo" class="form-control" placeholder="Ex: LOTE 10 SETEMBRO" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Data de recebimento</label>
                            <input type="date" name="data_recebimento" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Fornecedor (opcional)</label>
                            <input name="fornecedor" class="form-control" placeholder="Ex: Fornecedor X">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3"
                                placeholder="Ex: veio com variações, conferir banho..."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        Depois eu crio a tela <strong>lote.php</strong> (detalhe + itens). Por enquanto, esse modal só cria o “cabeçalho” do lote.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/app.js" defer></script>
    <script src="assets/js/ux_atalhos.js" defer></script>
    <script src="assets/js/theme.js" defer></script>

    <?php require __DIR__ . '/../modals/modal_minha_senha.php'; ?>
</body>

</html>