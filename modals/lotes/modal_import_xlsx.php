<?php
// modals/lotes/modal_import_xlsx.php
// Dependências esperadas do lote_view.php:
// $lote, $recebimentoAtualId, $editMode
?>

<?php if (!empty($editMode)): ?>
    <div class="modal fade" id="modalImportXlsx" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="actions/lote_import_xlsx.php" class="modal-content" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_import_xlsx')) ?>">
                <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
                <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">

                <div class="modal-header">
                    <h5 class="modal-title">📥 Importar itens (XLSX)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="fw-semibold mb-1">Formato esperado (primeira aba):</div>
                        <code>produto_nome | variacao | qtd_prevista</code>
                        <div class="small text-muted mt-1">
                            • <strong>variacao</strong>: Prata ou Ouro<br>
                            • <strong>qtd_prevista</strong>: inteiro (>= 0)
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Arquivo .xlsx</label>
                        <input
                            type="file"
                            name="xlsx"
                            class="form-control"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required>
                        <div class="form-text">Apenas .xlsx (Excel). Tamanho recomendado: até 5MB.</div>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Se o item já existir (mesmo produto + variação no recebimento)</label>
                            <select name="on_conflict" class="form-select">
                                <option value="ignore">Ignorar (não altera)</option>
                                <option value="sum">Somar na qtd prevista</option>
                                <option value="replace">Substituir qtd prevista</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Comportamento para produto não encontrado</label>
                            <select name="on_missing_product" class="form-select">
                                <option value="skip">Ignorar e registrar no relatório</option>
                                <option value="fail">Parar importação (erro)</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="small text-muted">
                        Dica: se você quiser, eu te passo um “modelo.xlsx” depois (pra padronizar).
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Importar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>