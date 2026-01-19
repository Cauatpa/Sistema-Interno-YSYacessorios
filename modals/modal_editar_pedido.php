<div class="modal fade" id="modalEditar<?= (int)$r['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
        <div class="modal-content">

            <form action="actions/editar_pedido.php" method="POST">
                <?php require_once __DIR__ . '/../helpers/csrf.php'; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('editar_pedido')) ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">

                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                <!-- Defaults -->
                <input type="hidden" name="precisa_balanco" value="0">
                <input type="hidden" name="sem_estoque" value="0">

                <div class="modal-header">
                    <h5 class="modal-title">✏ Editar Pedido #<?= (int)$r['id'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    <?php
                    $statusAtual = (string)($r['status'] ?? '');
                    $isFinalizado = ($statusAtual === 'finalizado');
                    $qtdSolicAtual = (int)($r['quantidade_solicitada'] ?? 1);
                    $qtdRetAtual = isset($r['quantidade_retirada']) ? (int)$r['quantidade_retirada'] : null;
                    ?>

                    <div class="alert alert-light border mb-3">
                        <div><strong>Status:</strong> <?= htmlspecialchars($statusAtual !== '' ? $statusAtual : '—') ?></div>
                        <div><strong>Mês:</strong> <?= htmlspecialchars((string)($r['competencia'] ?? '—')) ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <input
                            type="text"
                            name="produto"
                            class="form-control"
                            value="<?= htmlspecialchars((string)($r['produto'] ?? '')) ?>"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="prata" <?= (($r['tipo'] ?? '') === 'prata') ? 'selected' : '' ?>>Prata</option>
                            <option value="ouro" <?= (($r['tipo'] ?? '') === 'ouro')  ? 'selected' : '' ?>>Ouro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Solicitante</label>
                        <input
                            type="text"
                            name="solicitante"
                            class="form-control"
                            value="<?= htmlspecialchars((string)($r['solicitante'] ?? '')) ?>"
                            required>
                        <div class="form-text">Nome de quem solicitou o item (não precisa ser usuário do sistema).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade solicitada (peças)</label>
                        <input
                            type="number"
                            name="quantidade_solicitada"
                            class="form-control"
                            min="1"
                            value="<?= $qtdSolicAtual ?>"
                            required>
                    </div>

                    <?php if ($isFinalizado): ?>
                        <div class="mb-3">
                            <label class="form-label">Quantidade entregue (peças)</label>
                            <input
                                type="number"
                                name="quantidade_retirada"
                                class="form-control"
                                min="0"
                                value="<?= ($qtdRetAtual !== null) ? (int)$qtdRetAtual : 0 ?>"
                                autocomplete="off">
                            <div class="form-text">
                                Só aparece para pedidos <strong>finalizados</strong>. (Isso é o que realmente foi entregue.)
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info py-2 small">
                            ℹ️ A “Quantidade entregue” só pode ser alterada quando o pedido estiver <strong>finalizado</strong>.
                        </div>
                    <?php endif; ?>

                    <hr>

                    <div class="d-flex flex-column gap-2">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="precisa_balanco"
                                value="1"
                                id="editBalanco<?= (int)$r['id'] ?>"
                                <?= !empty($r['precisa_balanco']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="editBalanco<?= (int)$r['id'] ?>">
                                ⚠ Marcar para balanço
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="sem_estoque"
                                value="1"
                                id="editSem<?= (int)$r['id'] ?>"
                                <?= !empty($r['sem_estoque']) ? 'checked' : '' ?>
                                onchange="
                                    const b = document.getElementById('editBalanco<?= (int)$r['id'] ?>');
                                    if (this.checked) b.checked = true;
                                ">
                            <label class="form-check-label fw-semibold" for="editSem<?= (int)$r['id'] ?>">
                                ❌ Produto sem estoque
                            </label>
                        </div>

                        <div class="form-text">
                            Você pode deixar ambos desmarcados. Se marcar <strong>sem estoque</strong>, o sistema marca <strong>balanço</strong> automaticamente.
                        </div>
                    </div>

                </div>

                <div class="modal-footer d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">💾 Salvar</button>
                </div>

            </form>

        </div>
    </div>
</div>