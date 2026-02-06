<?php
$canOperate = auth_has_role('operador');
$canAdmin   = auth_has_role('admin');

$u = $u ?? [];
$nomeUsuario = trim((string)($u['nome'] ?? $u['usuario'] ?? ''));
if ($nomeUsuario === '') $nomeUsuario = '—';

$editMode = !empty($canEdit);        // operador+admin
$editFull = !empty($canEditFull);    // só admin
$statusLote = (string)($lote['status'] ?? 'aberto');

function item_row_style(array $i): string
{
    $situ = (string)($i['situacao'] ?? 'ok');
    $prev = (int)($i['qtd_prevista'] ?? 0);
    $conf = $i['qtd_conferida'];
    $confVal = ($conf === null || $conf === '') ? null : (int)$conf;

    if ($situ === 'banho_trocado') return 'background:#d1ecf1;';
    if ($situ === 'quebra') return 'background:#fff3cd;';
    if (in_array($situ, ['faltando', 'a_mais', 'outro'], true)) return 'background:#f8d7da;';

    if ($confVal !== null) {
        if ($confVal !== $prev) return 'background:#f8d7da;';
        return 'background:#d4edda;';
    }
    return '';
}

// helper pra saber se tem recebimento atual selecionado
$temRecebimentoAtual = ((int)($recebimentoAtualId ?? 0) > 0);

// ✅ auto-abrir modal de adicionar item após "Próximo"
$openItem = ((int)($_GET['open_item'] ?? 0) === 1);
?>

<!DOCTYPE html>
<html lang="pt-br" data-bs-theme=""
    data-toast="<?= htmlspecialchars((string)($toast ?? '')) ?>"
    data-highlight-id="<?= (int)($highlightId ?? 0) ?>">

<head>
    <meta charset="UTF-8">
    <title>Lote #<?= (int)$lote['id'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/imgs/Y.png">
</head>

<body class="p-3">

    <!-- Topo -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="mb-0">📦 <?= htmlspecialchars((string)$lote['codigo']) ?></h2>
            <div class="small text-muted">
                Lote #<?= (int)$lote['id'] ?> • Competência <?= htmlspecialchars((string)$lote['competencia']) ?>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="small text-muted">
                👤 <?= htmlspecialchars($nomeUsuario) ?>
                <?php if ($canAdmin): ?><span class="badge text-bg-dark ms-1">admin</span>
                <?php elseif ($canOperate): ?><span class="badge text-bg-primary ms-1">operador</span>
                <?php else: ?><span class="badge text-bg-secondary ms-1">visualizador</span>
                <?php endif; ?>
            </div>

            <a href="lotes.php?competencia=<?= urlencode((string)$lote['competencia']) ?>" class="btn btn-outline-secondary btn-sm">← Voltar</a>

            <?php if ($canOperate && !$editMode && $statusLote !== 'fechado'): ?>
                <a href="lote.php?id=<?= (int)$lote['id'] ?>&edit=1" class="btn btn-outline-primary btn-sm">✏ Editar</a>
            <?php endif; ?>

            <?php if ($editMode): ?>
                <a href="lote.php?id=<?= (int)$lote['id'] ?>" class="btn btn-outline-secondary btn-sm">👁 Ver</a>
            <?php endif; ?>

            <button id="btnTheme" class="btn btn-outline-secondary btn-sm">
                🌙 Tema escuro
            </button>

            <form method="POST" action="logout.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('logout')) ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <!-- Card cabeçalho -->
    <div class="card p-3 mb-3">
        <div class="row g-3 align-items-start">

            <!-- ESQUERDA: Fornecedor -->
            <div class="col-12 col-md-4">
                <div class="fw-bold">Fornecedor</div>
                <div><?= htmlspecialchars((string)($lote['fornecedor'] ?? '—')) ?></div>
            </div>

            <!-- MEIO: Status -->
            <div class="col-12 col-md-2">
                <div class="fw-bold">Status</div>
                <div>
                    <span class="badge text-bg-secondary"><?= htmlspecialchars($statusLote) ?></span>
                </div>
            </div>

            <!-- DIREITA: Recebimento + botões -->
            <div class="col-12 col-md-6">
                <div class="d-flex flex-column flex-md-row gap-3 align-items-start align-items-md-end justify-content-between">

                    <!-- Recebimento atual (dropdown) -->
                    <div class="flex-grow-1" style="min-width: 260px;">
                        <div class="fw-bold">Recebimento atual</div>

                        <?php if (!empty($recebimentos)): ?>
                            <form method="GET" class="mt-1">
                                <input type="hidden" name="id" value="<?= (int)$lote['id'] ?>">
                                <?php if ($editMode): ?><input type="hidden" name="edit" value="1"><?php endif; ?>

                                <select name="recebimento_id" class="form-select" onchange="this.form.submit()">
                                    <?php foreach ($recebimentos as $r): ?>
                                        <?php
                                        $rid = (int)$r['id'];
                                        $dt = !empty($r['data_hora']) ? date('d/m/Y H:i', strtotime((string)$r['data_hora'])) : '—';
                                        $label = trim((string)($r['volume_label'] ?? ''));
                                        $txt = $dt . ($label !== '' ? " • {$label}" : '');
                                        ?>
                                        <option value="<?= $rid ?>" <?= $rid === (int)$recebimentoAtualId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($txt) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <noscript><button class="btn btn-secondary btn-sm mt-2">OK</button></noscript>
                            </form>

                            <div class="text-muted small mt-1">
                                Itens adicionados ficam vinculados ao recebimento selecionado.
                            </div>
                        <?php else: ?>
                            <div class="text-muted mt-1">Nenhum recebimento cadastrado ainda.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Botões -->
                    <?php if ($editMode): ?>
                        <div class="d-grid gap-2" style="min-width: 220px;">

                            <!-- ADICIONAR ITEM -->
                            <button
                                class="btn btn-success"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAddItem"
                                <?= !$temRecebimentoAtual ? 'disabled' : '' ?>
                                title="<?= !$temRecebimentoAtual ? 'Crie ou selecione um recebimento antes de adicionar itens' : '' ?>">
                                ➕ Item
                            </button>

                            <!-- NOVO RECEBIMENTO -->
                            <button
                                class="btn btn-outline-primary"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#modalNovoRecebimento">
                                📦 Recebimento
                            </button>

                            <?php if (!$temRecebimentoAtual): ?>
                                <div class="text-muted small text-center">
                                    Crie um recebimento para liberar a adição de itens.
                                </div>
                            <?php endif; ?>

                            <!-- IMPORTAR XLSX -->
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalImportXlsx">
                                📥 Importar XLSX
                            </button>

                            <form method="POST" action="actions/lote_sync_tiny.php" class="d-grid gap-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_tiny_sync')) ?>">
                                <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                                <input type="hidden" name="mode" value="only_null"> <!-- padrão: não sobrescreve -->

                                <button
                                    type="submit"
                                    class="btn btn-outline-primary"
                                    <?= !$temRecebimentoAtual ? 'disabled' : '' ?>
                                    title="<?= !$temRecebimentoAtual ? 'Selecione um recebimento' : '' ?>">
                                    🔄 Puxar conferidos do Tiny
                                </button>
                            </form>

                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <?php if (!empty($lote['observacoes'])): ?>
                <div class="col-12 pt-2">
                    <div class="fw-bold">Observações</div>
                    <div class="text-muted"><?= nl2br(htmlspecialchars((string)$lote['observacoes'])) ?></div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Filtros de itens -->
    <form method="GET" class="card p-2 mb-3">
        <input type="hidden" name="id" value="<?= (int)$lote['id'] ?>">
        <?php if ($editMode): ?><input type="hidden" name="edit" value="1"><?php endif; ?>
        <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label mb-1">Produto</label>
                <input name="q_produto" class="form-control" value="<?= htmlspecialchars((string)($qProduto ?? '')) ?>" placeholder="Buscar nome do produto...">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Variação</label>
                <select name="q_variacao" class="form-select">
                    <option value="">Todas</option>
                    <option value="prata" <?= (($qVariacao ?? '') === 'prata') ? 'selected' : '' ?>>Prata</option>
                    <option value="ouro" <?= (($qVariacao ?? '') === 'ouro') ? 'selected' : '' ?>>Ouro</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Situação</label>
                <select name="q_situacao" class="form-select">
                    <option value="">Todas</option>
                    <?php
                    $opts = ['ok' => 'OK', 'faltando' => 'Faltando', 'a_mais' => 'A mais', 'banho_trocado' => 'Banho trocado', 'quebra' => 'Quebra', 'outro' => 'Outro'];
                    foreach ($opts as $k => $lbl):
                    ?>
                        <option value="<?= $k ?>" <?= (($qSituacao ?? '') === $k) ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label mb-1">Por pág.</label>
                <select name="per_page" class="form-select">
                    <?php foreach ([30, 50, 100, 200] as $n): ?>
                        <option value="<?= $n ?>" <?= ((int)($perPage ?? 50) === $n) ? 'selected' : '' ?>><?= $n ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">Filtrar</button>

                <a class="btn btn-outline-secondary w-100"
                    href="lote.php?id=<?= (int)$lote['id'] ?><?= $editMode ? '&edit=1' : '' ?>&recebimento_id=<?= (int)$recebimentoAtualId ?>">
                    Limpar
                </a>
            </div>
        </div>
    </form>


    <!-- Tabela itens -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="">
                <tr>
                    <th>#</th>
                    <th class="text-start">Produto</th>
                    <th>Variação</th>
                    <th>Previsto</th>
                    <th>Conferido</th>
                    <th>Situação</th>
                    <th class="text-start">Nota</th>
                    <th>Conferido por</th>
                    <?php if ($editMode): ?><th>Ação</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Grupo de itens -->
                <?php if (empty($itensGrouped)): ?>
                    <tr>
                        <td colspan="<?= $editMode ? 8 : 7 ?>" class="text-muted py-4">Nenhum item cadastrado.</td>
                    </tr>
                    <!-- Grupo de itens -->
                <?php else: ?>
                    <?php foreach ($itensGrouped as $g):
                        $produtoNome = (string)($g['produto_nome'] ?? '');
                        $prata = $g['prata'];
                        $ouro  = $g['ouro'];

                        $idPrata = $prata ? (int)$prata['id'] : 0;
                        $idOuro  = $ouro  ? (int)$ouro['id']  : 0;

                        $modalId = 'modalEditFullGroup_' . ($idPrata ?: 0) . '_' . ($idOuro ?: 0) . '_' . md5($produtoNome);

                        $prevPrata = $prata ? (int)$prata['qtd_prevista'] : null;
                        $prevOuro  = $ouro  ? (int)$ouro['qtd_prevista']  : null;

                        $confPrata = $prata ? $prata['qtd_conferida'] : null;
                        $confOuro  = $ouro  ? $ouro['qtd_conferida']  : null;

                        $curSitu = (string)($g['situacao'] ?? 'ok');
                        $curNota = (string)($g['nota'] ?? '');

                        $rowStyle = '';
                        if ($prata) $rowStyle = item_row_style($prata);
                        if ($rowStyle === '' && $ouro) $rowStyle = item_row_style($ouro);
                    ?>
                        <tr style="<?= htmlspecialchars($rowStyle) ?>">
                            <td class="text-muted">—</td>

                            <td class="text-start">
                                <strong><?= htmlspecialchars($produtoNome) ?></strong>
                                <div class="text-muted small">
                                    <?php if ($idPrata): ?>Prata (#<?= $idPrata ?>)<?php endif; ?>
                                    <?php if ($idPrata && $idOuro): ?> • <?php endif; ?>
                                    <?php if ($idOuro): ?>Ouro (#<?= $idOuro ?>)<?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($idPrata): ?><div>Prata</div><?php endif; ?>
                                <?php if ($idOuro): ?><div>Ouro</div><?php endif; ?>
                            </td>

                            <td>
                                <?php if ($idPrata): ?><div><?= (int)$prevPrata ?></div><?php endif; ?>
                                <?php if ($idOuro): ?><div><?= (int)$prevOuro ?></div><?php endif; ?>
                            </td>

                            <td>
                                <?php if ($editMode): ?>
                                    <form method="POST" action="actions/lote_item_update_group.php"
                                        class="d-flex flex-column gap-2 align-items-center">

                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_update_group')) ?>">
                                        <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                        <input type="hidden" name="id_prata" value="<?= (int)$idPrata ?>">
                                        <input type="hidden" name="id_ouro" value="<?= (int)$idOuro ?>">
                                        <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                                        <?php if ($idPrata): ?>
                                            <input type="number" name="qtd_conferida_prata"
                                                class="form-control form-control-sm"
                                                style="max-width:140px;"
                                                value="<?= htmlspecialchars((string)($confPrata ?? '')) ?>"
                                                placeholder="Prata —">
                                        <?php endif; ?>

                                        <?php if ($idOuro): ?>
                                            <input type="number" name="qtd_conferida_ouro"
                                                class="form-control form-control-sm"
                                                style="max-width:140px;"
                                                value="<?= htmlspecialchars((string)($confOuro ?? '')) ?>"
                                                placeholder="Ouro —">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($idPrata): ?><div><?= ($confPrata === null || $confPrata === '') ? '—' : (int)$confPrata ?></div><?php endif; ?>
                                        <?php if ($idOuro): ?><div><?= ($confOuro === null || $confOuro === '') ? '—' : (int)$confOuro ?></div><?php endif; ?>
                                    <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($editMode): ?>
                                    <select name="situacao" class="form-select form-select-sm" style="max-width:190px;">
                                        <?php
                                        $opts = [
                                            'ok' => 'OK',
                                            'faltando' => 'Faltando',
                                            'a_mais' => 'A mais',
                                            'banho_trocado' => 'Banho trocado',
                                            'quebra' => 'Quebra',
                                            'outro' => 'Outro'
                                        ];
                                        foreach ($opts as $k => $label):
                                        ?>
                                            <option value="<?= htmlspecialchars($k) ?>" <?= $k === $curSitu ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <?= htmlspecialchars($curSitu) ?>
                                <?php endif; ?>
                            </td>

                            <td class="text-start">
                                <?php if ($editMode): ?>
                                    <input name="nota" class="form-control form-control-sm"
                                        value="<?= htmlspecialchars($curNota) ?>" placeholder="(opcional)">
                                <?php else: ?>
                                    <?= htmlspecialchars($curNota) ?>
                                <?php endif; ?>
                            </td>

                            <!-- CONFERIDO POR -->
                            <td>
                                <?php if ($editMode): ?>

                                    <?php if ($idPrata): ?>
                                        <input
                                            name="conferido_por_prata"
                                            class="form-control form-control-sm mb-2"
                                            style="max-width:190px;"
                                            list="datalistSolicitantes"
                                            placeholder="Prata — Ex: Josi"
                                            value="<?= htmlspecialchars((string)($prata['conferido_por'] ?? '')) ?>">
                                    <?php endif; ?>

                                    <?php if ($idOuro): ?>
                                        <input
                                            name="conferido_por_ouro"
                                            class="form-control form-control-sm"
                                            style="max-width:190px;"
                                            list="datalistSolicitantes"
                                            placeholder="Ouro — Ex: Jô"
                                            value="<?= htmlspecialchars((string)($ouro['conferido_por'] ?? '')) ?>">
                                    <?php endif; ?>

                                <?php else: ?>

                                    <?php if ($idPrata): ?>
                                        <div class="small"><strong>Prata:</strong> <?= htmlspecialchars($prata['conferido_por'] ?? '—') ?></div>
                                    <?php endif; ?>

                                    <?php if ($idOuro): ?>
                                        <div class="small"><strong>Ouro:</strong> <?= htmlspecialchars($ouro['conferido_por'] ?? '—') ?></div>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </td>

                            <?php if ($editMode): ?>
                                <td style="min-width: 180px;">
                                    <button class="btn btn-primary btn-sm w-100" type="submit">Salvar</button>
                                    </form>

                                    <?php if ($editFull): ?>
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm w-100 mt-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?= htmlspecialchars($modalId) ?>">
                                            🛠 Editar Item
                                        </button>

                                        <!-- Modal delete -->
                                        <form method="POST"
                                            action="actions/lote_item_delete_group.php"
                                            class="mt-2"
                                            onsubmit="return confirm('Excluir este item do lote? Isso removerá Prata e/ou Ouro (se existirem).');">

                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_delete_group')) ?>">
                                            <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                            <input type="hidden" name="id_prata" value="<?= (int)$idPrata ?>">
                                            <input type="hidden" name="id_ouro" value="<?= (int)$idOuro ?>">
                                            <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                                            <button class="btn btn-outline-danger btn-sm w-100" type="submit">Excluir</button>
                                        </form>

                                        <!-- Modal edit full -->
                                        <div class="modal fade" id="<?= htmlspecialchars($modalId) ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <form method="POST" action="actions/lote_item_edit_full_group.php" class="modal-content">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_edit_full_group')) ?>">
                                                    <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                                    <input type="hidden" name="id_prata" value="<?= (int)$idPrata ?>">
                                                    <input type="hidden" name="id_ouro" value="<?= (int)$idOuro ?>">
                                                    <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">🛠 Editar Item (<?= htmlspecialchars($produtoNome) ?>)</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <!-- ... seu modal continua igual ... -->
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-success">Salvar completo</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <script>
                                            document.addEventListener('DOMContentLoaded', () => {
                                                const prata = document.getElementById('<?= htmlspecialchars($modalId) ?>_prata');
                                                const ouro = document.getElementById('<?= htmlspecialchars($modalId) ?>_ouro');
                                                const boxPrata = document.getElementById('<?= htmlspecialchars($modalId) ?>_box_prata');
                                                const boxOuro = document.getElementById('<?= htmlspecialchars($modalId) ?>_box_ouro');

                                                const sync = () => {
                                                    if (boxPrata) boxPrata.style.display = prata && prata.checked ? '' : 'none';
                                                    if (boxOuro) boxOuro.style.display = ouro && ouro.checked ? '' : 'none';
                                                };

                                                prata && prata.addEventListener('change', sync);
                                                ouro && ouro.addEventListener('change', sync);
                                                sync();
                                            });
                                        </script>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p class="text-center mt-4 text-muted" style="font-size:13px;">
            InterYSY • Sistema Interno
        </p>
    </div>

    <!-- Paginação -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center flex-wrap">
                <?php
                $qsBase = $_GET;
                $qsBase['id'] = (int)$lote['id'];
                if ($editMode) $qsBase['edit'] = 1;
                $qsBase['recebimento_id'] = (int)$recebimentoAtualId;

                $mkLink = function (int $p) use ($qsBase) {
                    $qs = $qsBase;
                    $qs['page'] = $p;
                    return 'lote.php?' . http_build_query($qs);
                };

                $cur = (int)($page ?? 1);
                ?>

                <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($mkLink(max(1, $cur - 1))) ?>">«</a>
                </li>

                <?php for ($p = max(1, $cur - 2); $p <= min($totalPages, $cur + 2); $p++): ?>
                    <li class="page-item <?= $p === $cur ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($mkLink($p)) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $cur >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($mkLink(min($totalPages, $cur + 1))) ?>">»</a>
                </li>
            </ul>

            <div class="text-center text-muted small">
                Mostrando <?= count($itens ?? []) ?> de <?= (int)($totalItens ?? 0) ?> itens
            </div>
        </nav>
    <?php endif; ?>

    <?php require_once __DIR__ . '/../modals/lotes/modal_add_item.php'; ?>
    <?php require_once __DIR__ . '/../modals/lotes/modal_novo_recebimento.php'; ?>
    <?php require_once __DIR__ . '/../modals/lotes/modal_import_xlsx.php'; ?>

    <!-- Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <div id="appToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div id="appToastBody" class="toast-body"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js" defer></script>
    <script src="assets/js/theme.js" defer></script>

    <!-- Tom Select -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <!-- Tom Select -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('modalAddItem');
            if (!modalEl) return;

            modalEl.addEventListener('shown.bs.modal', () => {
                const el = document.getElementById('produtoSelectAdd');
                if (!el) return;

                if (!el.tomselect) {
                    new TomSelect(el, {
                        plugins: ['clear_button'],
                        create: false,
                        maxOptions: 1000,
                        placeholder: "Digite para buscar...",
                        allowEmptyOption: true,
                        searchField: ['text']
                    });
                }

                setTimeout(() => el.tomselect?.focus(), 50);
            });
        });
    </script>

    <!-- ✅ Auto abrir modal quando vier open_item=1 -->
    <script>
        (() => {
            const params = new URLSearchParams(window.location.search);
            if (!params.has("open_item")) return;

            const modalEl = document.getElementById("modalAddItem");
            if (!modalEl || !window.bootstrap) return;

            const modal = new bootstrap.Modal(modalEl);
            modal.show();

            // ✅ remove open_item da URL depois de abrir
            params.delete("open_item");
            const newUrl =
                window.location.pathname +
                (params.toString() ? "?" + params.toString() : "");

            window.history.replaceState({}, document.title, newUrl);
        })();
    </script>

    <!-- Datalist de solicitantes -->
    <datalist id="datalistSolicitantes">
        <?php foreach ($solicitantes as $s): ?>
            <option value="<?= htmlspecialchars($s['nome']) ?>"></option>
        <?php endforeach; ?>
    </datalist>

</body>

</html>