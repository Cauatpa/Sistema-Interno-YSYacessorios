<?php
if (empty($editMode)) return;
?>

<!-- Modal de novo recebimento -->
<div class="modal fade" id="modalNovoRecebimento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="actions/lote_recebimento_add.php" class="modal-content" autocomplete="off">
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
                        <input type="datetime-local" name="data_hora" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
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