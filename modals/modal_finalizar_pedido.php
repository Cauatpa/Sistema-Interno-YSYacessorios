<div class="modal fade" id="modalFinalizar<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
        <div class="modal-content">

            <form action="actions/finalizar_pedido.php" method="POST">

                <!-- ID do pedido -->
                <input type="hidden" name="id" value="<?= $r['id'] ?>">

                <!-- Defaults para checkbox -->
                <input type="hidden" name="precisa_balanco" value="0">
                <input type="hidden" name="sem_estoque" value="0">

                <div class="modal-header">
                    <h5 class="modal-title">📦 Finalizar Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Resumo -->
                    <div class="alert alert-light border mb-3">
                        <div><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></div>
                        <div><strong>Tipo:</strong> <?= ucfirst($r['tipo']) ?></div>
                        <div><strong>Quantidade solicitada:</strong> <?= $r['quantidade_solicitada'] ?></div>
                    </div>

                    <!-- Quantidade retirada -->
                    <div class="mb-3">
                        <label class="form-label">Quantidade retirada do estoque</label>
                        <input
                            type="number"
                            name="quantidade_retirada"
                            id="qtd<?= $r['id'] ?>"
                            class="form-control form-control-lg"
                            min="0"
                            max="<?= $r['quantidade_solicitada'] ?>"
                            required>
                    </div>

                    <!-- Responsável -->
                    <div class="mb-3">
                        <label class="form-label">Quem retirou do estoque</label>
                        <input
                            type="text"
                            name="responsavel_estoque"
                            class="form-control form-control-lg"
                            required>
                    </div>

                    <!-- Balanço -->
                    <div class="form-check form-switch mb-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="precisa_balanco"
                            value="1"
                            id="balanco<?= $r['id'] ?>">
                        <label class="form-check-label fw-semibold" for="balanco<?= $r['id'] ?>">
                            ⚠ Marcar para balanço
                        </label>
                    </div>

                    <!-- Sem estoque -->
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="sem_estoque"
                            value="1"
                            id="sem<?= $r['id'] ?>"
                            onchange="
                                const qtd = document.getElementById('qtd<?= $r['id'] ?>');
                                if (this.checked) {
                                    qtd.value = 0;
                                    qtd.disabled = true;
                                } else {
                                    qtd.disabled = false;
                                }
                            ">
                        <label class="form-check-label fw-semibold" for="sem<?= $r['id'] ?>">
                            ❌ Produto sem estoque
                        </label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        ✅ Finalizar Retirada
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>