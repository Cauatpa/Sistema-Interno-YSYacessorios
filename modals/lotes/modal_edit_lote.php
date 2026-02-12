<?php if ($editMode && auth_has_role('admin')): ?>
    <div class="modal fade" id="modalEditLote" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="actions/lote_edit.php" class="modal-content" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_edit')) ?>">
                <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                <div class="modal-header">
                    <h5 class="modal-title">✏️ Editar Lote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2">

                        <div class="col-12 col-md-6">
                            <label class="form-label">Código (nome do lote)</label>
                            <input name="codigo" class="form-control"
                                value="<?= htmlspecialchars((string)($lote['codigo'] ?? '')) ?>"
                                placeholder="Ex: LOTE 20 DE JANEIRO">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Fornecedor</label>
                            <input name="fornecedor" class="form-control"
                                value="<?= htmlspecialchars((string)($lote['fornecedor'] ?? '')) ?>"
                                placeholder="Ex: METAL NOBRE...">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações (lote)</label>
                            <textarea name="observacoes" class="form-control" rows="3"
                                placeholder="(opcional)"><?= htmlspecialchars((string)($lote['observacoes'] ?? '')) ?></textarea>
                        </div>

                        <hr class="my-3">

                        <div class="col-12">
                            <div class="fw-bold mb-2">Recebimento atual (opcional)</div>
                            <div class="text-muted small mb-2">
                                Esses campos editam o recebimento selecionado no “Recebimento atual”.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Data/hora do recebimento</label>
                            <?php
                            // converte "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM" pro input
                            $recDH = (string)($recebimentoAtual['data_hora'] ?? '');
                            $recDHInput = '';
                            if ($recDH !== '') {
                                $recDHInput = str_replace(' ', 'T', substr($recDH, 0, 16));
                            }
                            ?>
                            <input type="datetime-local" name="rec_data_hora" class="form-control"
                                value="<?= htmlspecialchars($recDHInput) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Volume label</label>
                            <input name="rec_volume_label" class="form-control"
                                value="<?= htmlspecialchars((string)($recebimentoAtual['volume_label'] ?? '')) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Rastreio</label>
                            <input name="rec_rastreio" class="form-control"
                                value="<?= htmlspecialchars((string)($recebimentoAtual['rastreio'] ?? '')) ?>">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Nota</label>
                            <input name="rec_nota" class="form-control"
                                value="<?= htmlspecialchars((string)($recebimentoAtual['nota'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações (recebimento)</label>
                            <textarea name="rec_observacoes" class="form-control" rows="3"
                                placeholder="(opcional)"><?= htmlspecialchars((string)($recebimentoAtual['observacoes'] ?? '')) ?></textarea>
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