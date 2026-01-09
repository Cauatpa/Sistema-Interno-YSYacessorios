<?php
require_once __DIR__ . '/../helpers/csrf.php';

$id = (int)($r['id'] ?? 0);
$produto = (string)($r['produto'] ?? '');
$tipo = (string)($r['tipo'] ?? '');
$qtd = (int)($r['quantidade_solicitada'] ?? 1);
$status = (string)($r['status'] ?? '');
?>

<div class="modal fade" id="modalEditar<?= $id ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form method="POST" action="actions/editar_pedido.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('editar_pedido')) ?>">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="modal-header">
                    <h5 class="modal-title">✏ Editar pedido #<?= $id ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    <?php if ($status === 'finalizado'): ?>
                        <div class="alert alert-warning py-2">
                            Atenção: este pedido já está <strong>finalizado</strong>. Alterar a quantidade solicitada pode impactar as flags.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Produto</label>
                        <input name="produto" class="form-control" required value="<?= htmlspecialchars($produto) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="prata" <?= $tipo === 'prata' ? 'selected' : '' ?>>Prata</option>
                            <option value="ouro" <?= $tipo === 'ouro' ? 'selected' : '' ?>>Ouro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade solicitada</label>
                        <input type="number" name="quantidade_solicitada" class="form-control" min="1" required value="<?= $qtd ?>">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>

            </form>

        </div>
    </div>
</div>