<?php
// precisa de $u (usuário do loop)
?>
<div class="modal fade" id="modalSenha<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="/InterYSY/actions/admin_trocar_senha.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_trocar_senha')) ?>">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title">🔑 Trocar senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <div class="small text-muted">Usuário:</div>
                        <div class="fw-bold"><?= htmlspecialchars($u['nome'] ?? '') ?> (<?= htmlspecialchars($u['usuario'] ?? '') ?>)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="senha" class="form-control" minlength="6" required>
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmar nova senha</label>
                        <input type="password" name="senha2" class="form-control" minlength="6" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100">💾 Salvar nova senha</button>
                </div>

            </form>

        </div>
    </div>
</div>