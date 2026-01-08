<div class="modal fade" id="modalNovoPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form action="actions/novo_pedido.php" method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">📦 Novo Pedido de Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <input
                            type="text"
                            name="produto"
                            class="form-control"
                            placeholder="Ex: Anel Coração"
                            required>
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
                        <label class="form-label">Quantidade</label>
                        <input
                            type="number"
                            name="quantidade_solicitada"
                            class="form-control"
                            min="1"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Solicitante</label>
                        <input
                            type="text"
                            name="solicitante"
                            class="form-control"
                            placeholder="Nome de quem solicitou"
                            required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        💾 Salvar Pedido
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>