<?php
// Espera ter $r disponível (vindo do foreach do _load_modals.php)
?>

<div class="modal fade" id="modalExcluir<?= (int)$r['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="/InterYSY/actions/excluir_pedido.php" class="modal-content">
            <?php require_once __DIR__ . '/../helpers/csrf.php'; ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('excluir_pedido')) ?>">
            <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">


            <div class="modal-header">
                <h5 class="modal-title">🗑 Excluir pedido #<?= (int)$r['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body text-start">
                <p class="mb-2">
                    Essa ação remove o pedido da lista (soft delete), mas mantém ele registrado no sistema.
                </p>

                <div class="mb-2">
                    <div><strong>Produto:</strong> <?= htmlspecialchars($r['produto']) ?></div>
                    <div><strong>Qtd:</strong> <?= (int)$r['quantidade_solicitada'] ?></div>
                    <div><strong>Tipo:</strong> <?= htmlspecialchars($r['tipo']) ?></div>
                </div>

                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="usuario" value="Cauã">

                <label class="form-label mt-2">
                    Digite para confirmar: <strong>EXCLUIR <?= (int)$r['id'] ?></strong>
                </label>
                <input type="text" name="confirm" class="form-control" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar exclusão</button>
            </div>
        </form>
    </div>
</div>