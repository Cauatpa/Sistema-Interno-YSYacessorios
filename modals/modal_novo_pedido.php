<div class="modal fade" id="modalNovoPedido" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form action="actions/novo_pedido.php" method="POST">
                <?php require_once __DIR__ . '/../helpers/csrf.php'; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('novo_pedido')) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">📦 Novo Pedido de Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    <!-- Produto -->
                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <input
                            type="text"
                            name="produto"
                            class="form-control"
                            placeholder="Ex: Anel Coração"
                            required
                            autofocus>
                    </div>

                    <!-- Tipo -->
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="prata">Prata</option>
                            <option value="ouro">Ouro</option>
                        </select>
                    </div>

                    <!-- Quantidade -->
                    <div class="mb-3">
                        <label class="form-label">Quantidade solicitada</label>
                        <input
                            type="number"
                            name="quantidade_solicitada"
                            class="form-control"
                            min="1"
                            required>
                    </div>

                    <!-- Solicitante REAL -->
                    <div class="mb-3">
                        <label class="form-label">Solicitante</label>
                        <input
                            type="text"
                            name="solicitante"
                            class="form-control"
                            placeholder="Nome de quem solicitou o item"
                            required>
                        <div class="form-text">
                            Informe o nome da pessoa que solicitou o item (não precisa ser usuário do sistema).
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        💾 Salvar Pedido
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>