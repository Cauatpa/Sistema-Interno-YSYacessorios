<div class="modal fade" id="modalNovoPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form id="formNovoPedido" action="actions/novo_pedido.php" method="POST">
                <?php require_once __DIR__ . '/../helpers/csrf.php'; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('novo_pedido')) ?>">

                <!-- ✅ ÚNICA fonte da verdade -->
                <input type="hidden" name="next" id="novoPedidoNext" value="0">

                <div class="modal-header">
                    <h5 class="modal-title">📦 Novo Pedido de Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <input
                            type="text"
                            name="produto"
                            class="form-control"
                            placeholder="Ex: Anel Coração"
                            required
                            autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="prata">Prata</option>
                            <option value="ouro">Ouro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade solicitada (peças)</label>
                        <input
                            type="number"
                            name="quantidade_solicitada"
                            class="form-control"
                            min="1"
                            value="1"
                            required>
                        <div class="form-text">Quantas peças a pessoa pediu.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Solicitante</label>
                        <input
                            type="text"
                            name="solicitante"
                            class="form-control"
                            placeholder="Nome de quem solicitou o item"
                            required
                            autocomplete="off">
                    </div>

                    <div class="alert alert-light border py-2 small mb-0">
                        Atalhos: <strong>Enter</strong> salva • <strong>Shift + Enter</strong> salva e abre o próximo
                    </div>
                </div>

                <div class="modal-footer d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm flex-fill" id="btnNovoSalvar">
                        💾 Salvar
                    </button>

                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="btnNovoProximo">
                        → Próximo
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>