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

// helper pra saber se tem recebimento atual selecionado
$temRecebimentoAtual = ((int)($recebimentoAtualId ?? 0) > 0);
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

            <div class="col-12 col-md-5">
                <div class="fw-bold">Recebimento atual</div>

                <?php if (!empty($recebimentos)): ?>
                    <form method="GET" class="d-flex gap-2">
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

                        <noscript><button class="btn btn-secondary">OK</button></noscript>
                    </form>

                    <div class="text-muted small mt-1">
                        Itens adicionados ficam vinculados ao recebimento selecionado.
                    </div>
                <?php else: ?>
                    <div class="text-muted">Nenhum recebimento cadastrado ainda.</div>
                <?php endif; ?>
            </div>

            <!-- Botões -->
            <div class="col-12 col-md-2 ms-md-auto text-md-end">
                <?php if ($editMode): ?>
                    <div class="d-grid gap-2">
                        <!-- ➕ Item -->
                        <button
                            type="button"
                            class="btn btn-success w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAddItem"
                            <?= ((int)$recebimentoAtualId <= 0) ? '' : '' ?>>
                            ➕ Item
                        </button>

                        <!-- 📦 Novo recebimento -->
                        <button
                            type="button"
                            class="btn btn-outline-primary w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#modalNovoRecebimento">
                            📦 Novo recebimento
                        </button>
                    </div>
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
                <?php if (empty($itensGrouped)): ?>
                    <tr>
                        <td colspan="<?= $editMode ? 8 : 7 ?>" class="text-muted py-4">Nenhum item cadastrado.</td>
                    </tr>
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

                            <?php if ($editMode): ?>
                                <td style="min-width: 180px;">
                                    <!-- botão do form UPDATE_GROUP -->
                                    <button class="btn btn-primary btn-sm w-100" type="submit">Salvar</button>
                                    </form>

                                    <?php if ($editFull): ?>
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm w-100 mt-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?= htmlspecialchars($modalId) ?>">
                                            🛠 Editar Item
                                        </button>

                                        <!-- EXCLUIR (ADMIN) -->
                                        <form method="POST"
                                            action="actions/lote_item_delete_group.php"
                                            class="mt-2"
                                            onsubmit="return confirm('Excluir este item do lote? Isso removerá Prata e/ou Ouro (se existirem).');">

                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_delete_group')) ?>">
                                            <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                            <input type="hidden" name="id_prata" value="<?= (int)$idPrata ?>">
                                            <input type="hidden" name="id_ouro" value="<?= (int)$idOuro ?>">

                                            <button class="btn btn-outline-danger btn-sm w-100" type="submit">
                                                Excluir
                                            </button>
                                        </form>

                                        <!-- MODAL EDIT FULL (igual ao seu, só mantive) -->
                                        <div class="modal fade" id="<?= htmlspecialchars($modalId) ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <form method="POST" action="actions/lote_item_edit_full_group.php" class="modal-content">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_edit_full_group')) ?>">
                                                    <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                                                    <input type="hidden" name="id_prata" value="<?= (int)$idPrata ?>">
                                                    <input type="hidden" name="id_ouro" value="<?= (int)$idOuro ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">🛠 Editar Item (<?= htmlspecialchars($produtoNome) ?>)</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="row g-2">
                                                            <div class="col-12">
                                                                <label class="form-label">Produto</label>
                                                                <select name="produto_id" class="form-select" required>
                                                                    <option value="">Selecione...</option>
                                                                    <?php foreach ($produtos as $p): ?>
                                                                        <option value="<?= (int)$p['id'] ?>"
                                                                            <?= ((int)$p['id'] === (int)($g['produto_id'] ?? 0)) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars((string)$p['nome']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-12">
                                                                <label class="form-label mb-1">Variações</label>
                                                                <div class="d-flex flex-wrap gap-3">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="<?= htmlspecialchars($modalId) ?>_prata"
                                                                            name="tem_prata" value="1"
                                                                            <?= $idPrata ? 'checked' : '' ?>>
                                                                        <label class="form-check-label" for="<?= htmlspecialchars($modalId) ?>_prata">Prata</label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            id="<?= htmlspecialchars($modalId) ?>_ouro"
                                                                            name="tem_ouro" value="1"
                                                                            <?= $idOuro ? 'checked' : '' ?>>
                                                                        <label class="form-check-label" for="<?= htmlspecialchars($modalId) ?>_ouro">Ouro</label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- PRATA -->
                                                            <div class="col-12" id="<?= htmlspecialchars($modalId) ?>_box_prata" style="<?= $idPrata ? '' : 'display:none;' ?>">
                                                                <div class="card p-2">
                                                                    <div class="fw-bold mb-2">Prata</div>
                                                                    <div class="row g-2">
                                                                        <div class="col-12 col-md-6">
                                                                            <label class="form-label">Qtd prevista (Prata)</label>
                                                                            <input type="number" name="qtd_prevista_prata" class="form-control" min="0"
                                                                                value="<?= $idPrata ? (int)$prata['qtd_prevista'] : 0 ?>">
                                                                        </div>
                                                                        <div class="col-12 col-md-6">
                                                                            <label class="form-label">Qtd conferida (Prata)</label>
                                                                            <input type="number" name="qtd_conferida_prata" class="form-control"
                                                                                value="<?= htmlspecialchars((string)($idPrata ? ($prata['qtd_conferida'] ?? '') : '')) ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- OURO -->
                                                            <div class="col-12" id="<?= htmlspecialchars($modalId) ?>_box_ouro" style="<?= $idOuro ? '' : 'display:none;' ?>">
                                                                <div class="card p-2">
                                                                    <div class="fw-bold mb-2">Ouro</div>
                                                                    <div class="row g-2">
                                                                        <div class="col-12 col-md-6">
                                                                            <label class="form-label">Qtd prevista (Ouro)</label>
                                                                            <input type="number" name="qtd_prevista_ouro" class="form-control" min="0"
                                                                                value="<?= $idOuro ? (int)$ouro['qtd_prevista'] : 0 ?>">
                                                                        </div>
                                                                        <div class="col-12 col-md-6">
                                                                            <label class="form-label">Qtd conferida (Ouro)</label>
                                                                            <input type="number" name="qtd_conferida_ouro" class="form-control"
                                                                                value="<?= htmlspecialchars((string)($idOuro ? ($ouro['qtd_conferida'] ?? '') : '')) ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-12 col-md-6">
                                                                <label class="form-label">Situação (igual)</label>
                                                                <select name="situacao" class="form-select">
                                                                    <?php
                                                                    $opts2 = ['ok' => 'OK', 'faltando' => 'Faltando', 'a_mais' => 'A mais', 'banho_trocado' => 'Banho trocado', 'quebra' => 'Quebra', 'outro' => 'Outro'];
                                                                    foreach ($opts2 as $k => $label):
                                                                    ?>
                                                                        <option value="<?= htmlspecialchars($k) ?>" <?= $k === (string)$curSitu ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($label) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-12">
                                                                <label class="form-label">Nota (igual)</label>
                                                                <input name="nota" class="form-control" value="<?= htmlspecialchars($curNota) ?>">
                                                            </div>

                                                            <div class="col-12">
                                                                <div class="alert alert-warning mb-0">
                                                                    Se você <strong>desmarcar</strong> Prata ou Ouro, essa variação será <strong>removida</strong> do lote.
                                                                </div>
                                                            </div>
                                                        </div>
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
    </div>

    <!-- Modal adicionar item -->
    <?php if ($editMode): ?>
        <div class="modal fade" id="modalAddItem" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="actions/lote_item_add.php" class="modal-content">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_add')) ?>">
                    <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                    <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">➕ Adicionar item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Produto</label>
                                <select name="produto_id" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($produtos as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label mb-1">Variações</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="vPrata" name="tem_prata" value="1" checked>
                                        <label class="form-check-label" for="vPrata">Prata</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="vOuro" name="tem_ouro" value="1">
                                        <label class="form-check-label" for="vOuro">Ouro</label>
                                    </div>
                                </div>
                                <div class="text-muted small mt-1">Marque Prata e/ou Ouro. Situação e Nota serão iguais para ambas.</div>
                            </div>

                            <!-- Bloco Prata -->
                            <div class="col-12" id="boxPrata">
                                <div class="card p-2">
                                    <div class="fw-bold mb-2">Prata</div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Qtd prevista (Prata)</label>
                                            <input type="number" name="qtd_prevista_prata" class="form-control" value="0" min="0">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Qtd conferida (Prata)</label>
                                            <input type="number" name="qtd_conferida_prata" class="form-control" placeholder="—">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bloco Ouro -->
                            <div class="col-12" id="boxOuro" style="display:none;">
                                <div class="card p-2">
                                    <div class="fw-bold mb-2">Ouro</div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Qtd prevista (Ouro)</label>
                                            <input type="number" name="qtd_prevista_ouro" class="form-control" value="0" min="0">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label">Qtd conferida (Ouro)</label>
                                            <input type="number" name="qtd_conferida_ouro" class="form-control" placeholder="—">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Situação (igual para ambas)</label>
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
                                <label class="form-label">Nota (igual para ambas)</label>
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

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const vPrata = document.getElementById('vPrata');
                const vOuro = document.getElementById('vOuro');
                const boxPrata = document.getElementById('boxPrata');
                const boxOuro = document.getElementById('boxOuro');

                const sync = () => {
                    if (boxPrata) boxPrata.style.display = vPrata && vPrata.checked ? '' : 'none';
                    if (boxOuro) boxOuro.style.display = vOuro && vOuro.checked ? '' : 'none';
                };

                vPrata && vPrata.addEventListener('change', sync);
                vOuro && vOuro.addEventListener('change', sync);
                sync();
            });
        </script>
    <?php endif; ?>

    <!-- Modal novo recebimento -->
    <?php if ($editMode): ?>
        <div class="modal fade" id="modalNovoRecebimento" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="actions/lote_recebimento_add.php" class="modal-content">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_recebimento_add')) ?>">
                    <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">📦 Novo recebimento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Data/Hora</label>
                                <input type="datetime-local" name="data_hora" class="form-control"
                                    value="<?= date('Y-m-d\TH:i') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Volume/Label (opcional)</label>
                                <input name="volume_label" class="form-control" placeholder="Ex: Caixa 1/3">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Rastreio / CT-e (opcional)</label>
                                <input name="rastreio" class="form-control" placeholder="Ex: AV123... / CTe 000...">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nota (opcional)</label>
                                <input name="nota" class="form-control" placeholder="Ex: chegou caixa avulsa sem etiqueta">
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Após salvar, este recebimento vira o <strong>Recebimento atual</strong>.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar recebimento</button>
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