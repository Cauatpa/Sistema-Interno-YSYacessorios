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

    // ok: se conferiu e bateu -> verde, se conferiu e divergiu -> vermelho, se não conferiu -> neutro
    if ($confVal !== null) {
        if ($confVal !== $prev) return 'background:#f8d7da;';
        return 'background:#d4edda;';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light"
    data-toast="<?= htmlspecialchars((string)($toast ?? '')) ?>"
    data-highlight-id="<?= (int)($highlightId ?? 0) ?>">

<head>
    <meta charset="UTF-8">
    <title>Lote #<?= (int)$lote['id'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

            <form method="POST" action="logout.php" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('logout')) ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
            </form>
        </div>
    </div>

    <!-- Card cabeçalho -->
    <div class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <div class="fw-bold">Fornecedor</div>
                <div><?= htmlspecialchars((string)($lote['fornecedor'] ?? '—')) ?></div>
            </div>

            <div class="col-6 col-md-3">
                <div class="fw-bold">Recebimento</div>
                <div>
                    <?= !empty($lote['data_recebimento']) ? date('d/m/Y', strtotime((string)$lote['data_recebimento'])) : '—' ?>
                </div>
            </div>

            <div class="col-6 col-md-2">
                <div class="fw-bold">Status</div>
                <div><span class="badge text-bg-secondary"><?= htmlspecialchars($statusLote) ?></span></div>
            </div>

            <div class="col-12 col-md-2 text-md-end">
                <?php if ($editMode): ?>
                    <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalAddItem" type="button">
                        ➕ Item
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($lote['observacoes'])): ?>
                <div class="col-12 pt-2">
                    <div class="fw-bold">Observações</div>
                    <div class="text-muted"><?= nl2br(htmlspecialchars((string)$lote['observacoes'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Legenda -->
    <!-- <div class="d-flex flex-wrap gap-3 align-items-center mb-2">
        <div class="d-flex align-items-center gap-2"><span class="badge rounded-pill" style="background:#d4edda;color:#000;">&nbsp;&nbsp;</span><span class="small">quantidade certa (OK)</span></div>
        <div class="d-flex align-items-center gap-2"><span class="badge rounded-pill" style="background:#f8d7da;color:#000;">&nbsp;&nbsp;</span><span class="small">divergência / faltando / a mais</span></div>
        <div class="d-flex align-items-center gap-2"><span class="badge rounded-pill" style="background:#d1ecf1;color:#000;">&nbsp;&nbsp;</span><span class="small">banho trocado</span></div>
        <div class="d-flex align-items-center gap-2"><span class="badge rounded-pill" style="background:#fff3cd;color:#000;">&nbsp;&nbsp;</span><span class="small">quebra</span></div>
    </div> -->

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
                    <?php if ($editMode): ?><th>Ação</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr>
                        <td colspan="<?= $editMode ? 8 : 7 ?>" class="text-muted py-4">Nenhum item cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($itens as $i): $iid = (int)$i['id']; ?>
                        <tr style="<?= htmlspecialchars(item_row_style($i)) ?>">
                            <td><?= $iid ?></td>
                            <td class="text-start"><?= htmlspecialchars((string)$i['produto_nome']) ?></td>
                            <td><?= htmlspecialchars((string)($i['variacao'] ?? '—')) ?></td>
                            <td><?= (int)$i['qtd_prevista'] ?></td>

                            <td>
                                <?php if ($editMode): ?>
                                    <form method="POST" action="actions/lote_item_update.php" class="d-flex gap-2 justify-content-center">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_update')) ?>">
                                        <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                        <input type="hidden" name="id" value="<?= $iid ?>">
                                        <input type="number" name="qtd_conferida" class="form-control form-control-sm" style="max-width:120px;"
                                            value="<?= htmlspecialchars((string)($i['qtd_conferida'] ?? '')) ?>" placeholder="—">
                                    <?php else: ?>
                                        <?= ($i['qtd_conferida'] === null || $i['qtd_conferida'] === '') ? '—' : (int)$i['qtd_conferida'] ?>
                                    <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($editMode): ?>
                                    <select name="situacao" class="form-select form-select-sm" style="max-width:170px;">
                                        <?php
                                        $opts = ['ok' => 'OK', 'faltando' => 'Faltando', 'a_mais' => 'A mais', 'banho_trocado' => 'Banho trocado', 'quebra' => 'Quebra', 'outro' => 'Outro'];
                                        $cur = (string)($i['situacao'] ?? 'ok');
                                        foreach ($opts as $k => $label):
                                        ?>
                                            <option value="<?= htmlspecialchars($k) ?>" <?= $k === $cur ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <?= htmlspecialchars((string)($i['situacao'] ?? 'ok')) ?>
                                <?php endif; ?>
                            </td>

                            <td class="text-start">
                                <?php if ($editMode): ?>
                                    <input name="nota" class="form-control form-control-sm" value="<?= htmlspecialchars((string)($i['nota'] ?? '')) ?>" placeholder="(opcional)">
                                <?php else: ?>
                                    <?= htmlspecialchars((string)($i['nota'] ?? '')) ?>
                                <?php endif; ?>
                            </td>

                            <?php if ($editMode): ?>
                                <td style="min-width:160px;">
                                    <button class="btn btn-primary btn-sm w-50" type="submit">Salvar</button>
                                    </form>

                                    <?php if ($editFull): ?>
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm w-50 mt-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditFull<?= $iid ?>">
                                            🛠 Editar completo
                                        </button>

                                        <form method="POST" action="actions/lote_item_delete.php" class="mt-1"
                                            onsubmit="return confirm('Excluir este item?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_delete')) ?>">
                                            <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                            <input type="hidden" name="id" value="<?= $iid ?>">
                                            <button class="btn btn-outline-danger btn-sm w-50" type="submit">Excluir</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($editFull): ?>
                                        <div class="modal fade" id="modalEditFull<?= $iid ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <form method="POST" action="actions/lote_item_edit_full.php" class="modal-content">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_edit_full')) ?>">
                                                    <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                                    <input type="hidden" name="id" value="<?= $iid ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">🛠 Editar item completo (#<?= $iid ?>)</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row g-2">
                                                            <div class="col-12 col-md-8">
                                                                <label class="form-label">Produto</label>
                                                                <select name="produto_id" class="form-select" required>
                                                                    <?php foreach ($produtos as $p): ?>
                                                                        <option value="<?= (int)$p['id'] ?>"
                                                                            <?= ((int)$p['id'] === (int)($i['produto_id'] ?? 0)) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars((string)$p['nome']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label">Variação</label>
                                                                <select name="variacao" class="form-select">
                                                                    <option value="">—</option>
                                                                    <option value="Prata">Prata</option>
                                                                    <option value="Ouro">Ouro</option>
                                                                </select>
                                                            </div>


                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label">Qtd prevista</label>
                                                                <input type="number" name="qtd_prevista" class="form-control" min="0"
                                                                    value="<?= (int)($i['qtd_prevista'] ?? 0) ?>" required>
                                                            </div>

                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label">Qtd conferida</label>
                                                                <input type="number" name="qtd_conferida" class="form-control"
                                                                    value="<?= htmlspecialchars((string)($i['qtd_conferida'] ?? '')) ?>">
                                                            </div>

                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label">Situação</label>
                                                                <select name="situacao" class="form-select">
                                                                    <?php
                                                                    $opts = ['ok' => 'OK', 'faltando' => 'Faltando', 'a_mais' => 'A mais', 'banho_trocado' => 'Banho trocado', 'quebra' => 'Quebra', 'outro' => 'Outro'];
                                                                    $cur = (string)($i['situacao'] ?? 'ok');
                                                                    foreach ($opts as $k => $label):
                                                                    ?>
                                                                        <option value="<?= htmlspecialchars($k) ?>" <?= $k === $cur ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($label) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-12">
                                                                <label class="form-label">Nota</label>
                                                                <input name="nota" class="form-control" value="<?= htmlspecialchars((string)($i['nota'] ?? '')) ?>">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-success">Salvar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal adicionar item -->
    <?php if ($editMode): ?>
        <div class="modal fade" id="modalAddItem" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="actions/lote_item_add.php" class="modal-content">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_add')) ?>">
                    <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">➕ Adicionar item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12 col-md-8">
                                <label class="form-label">Produto</label>
                                <select name="produto_id" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($produtos as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Variação</label>
                                <select name="variacao" class="form-select">
                                    <option value="">—</option>
                                    <option value="Prata">Prata</option>
                                    <option value="Ouro">Ouro</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Qtd prevista</label>
                                <input type="number" name="qtd_prevista" class="form-control" value="0" min="0" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Qtd conferida (opcional)</label>
                                <input type="number" name="qtd_conferida" class="form-control" placeholder="—">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Situação</label>
                                <select name="situacao" class="form-select">
                                    <option value="ok">OK</option>
                                    <option value="faltando">Faltando</option>
                                    <option value="a_mais">A mais</option>
                                    <option value="banho_trocado">Banho trocado</option>
                                    <option value="quebra">Quebra</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nota</label>
                                <input name="nota" class="form-control" placeholder="(opcional)">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Toast (igual index) -->
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
</body>

</html>