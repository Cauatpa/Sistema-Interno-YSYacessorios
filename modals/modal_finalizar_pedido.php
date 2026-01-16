<div class="modal fade" id="modalFinalizar<?= (int)$r['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
        <div class="modal-content">

            <form action="actions/finalizar_pedido.php" method="POST">
                <?php require_once __DIR__ . '/../helpers/csrf.php'; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('finalizar_pedido')) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                <input type="hidden" name="precisa_balanco" value="0">
                <input type="hidden" name="sem_estoque" value="0">

                <!-- default: não é próximo -->
                <input type="hidden" name="next" value="0">
                <!-- ✅ usado quando o JS calcula o próximo pendente -->
                <input type="hidden" name="next_target_id" value="0">

                <div class="modal-header">
                    <h5 class="modal-title">📦 Finalizar Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div><strong>Produto:</strong> <?= htmlspecialchars((string)($r['produto'] ?? '')) ?></div>
                        <div><strong>Tipo:</strong> <?= htmlspecialchars(ucfirst((string)($r['tipo'] ?? ''))) ?></div>
                        <div><strong>Solicitado:</strong> <?= (int)($r['quantidade_solicitada'] ?? 0) ?> peça(s)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Itens entregues (retirados do estoque)</label>
                        <input
                            type="number"
                            name="quantidade_retirada"
                            id="qtdRetirada<?= (int)$r['id'] ?>"
                            class="form-control"
                            min="0"
                            required
                            autocomplete="off">
                        <div class="form-text">Preencha quantas peças foram entregues de fato.</div>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="precisa_balanco" value="1" id="balanco<?= (int)$r['id'] ?>">
                        <label class="form-check-label fw-semibold" for="balanco<?= (int)$r['id'] ?>">
                            ⚠ Marcar para balanço
                        </label>
                    </div>

                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="sem_estoque"
                            value="1"
                            id="sem<?= (int)$r['id'] ?>"
                            onchange="
                                const qtd = document.getElementById('qtdRetirada<?= (int)$r['id'] ?>');
                                const bal = document.getElementById('balanco<?= (int)$r['id'] ?>');
                                if (this.checked) {
                                    if (qtd) { qtd.value = 0; qtd.disabled = true; }
                                    if (bal) { bal.checked = true; }
                                } else {
                                    if (qtd) { qtd.disabled = false; qtd.focus(); }
                                }
                            ">
                        <label class="form-check-label fw-semibold" for="sem<?= (int)$r['id'] ?>">
                            ❌ Produto sem estoque
                        </label>
                    </div>

                    <div class="alert alert-light border py-2 small mt-3 mb-0">
                        Atalhos: <strong>Enter</strong> finaliza • <strong>Shift + Enter</strong> finaliza e abre o próximo
                    </div>
                </div>

                <div class="modal-footer d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm flex-fill">
                        ✅ Finalizar
                    </button>

                    <button type="submit" class="btn btn-outline-primary btn-sm flex-fill" name="next" value="1">
                        → Próximo
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>