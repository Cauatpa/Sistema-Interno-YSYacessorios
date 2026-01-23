<?php
if (!isset($competencia)) {
    $competencia = '';
}
?>

<!-- Modal: Novo Lote -->
<div class="modal fade" id="modalNovoLote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="actions/lotes_salvar.php" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lotes_salvar')) ?>">
            <input type="hidden" name="competencia" value="<?= htmlspecialchars((string)$competencia) ?>">

            <div class="modal-header">
                <h5 class="modal-title">➕ Novo Lote (<?= htmlspecialchars((string)$competencia) ?>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Código</label>
                        <input name="codigo" class="form-control" placeholder="Ex: LOTE 28 OUTUBRO" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Fornecedor (opcional)</label>
                        <input name="fornecedor" class="form-control" placeholder="Ex: Fornecedor X">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="3"
                            placeholder="Ex: conferir banho, veio trocado..."></textarea>
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