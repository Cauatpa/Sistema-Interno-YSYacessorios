<?php
require_once __DIR__ . '/../helpers/csrf.php';
?>

<div class="modal fade" id="modalMinhaSenha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form method="POST" action="actions/minha_senha.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('minha_senha')) ?>">

                <div class="modal-header">
                    <h5 class="modal-title">🔑 Trocar minha senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Senha atual</label>
                        <input type="password" name="senha_atual" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="senha_nova" class="form-control" minlength="8" required autocomplete="new-password">
                        <small class="text-muted">Mínimo 8 caracteres.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Confirmar nova senha</label>
                        <input type="password" name="senha_confirm" class="form-control" minlength="8" required autocomplete="new-password">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>

            </form>

        </div>
    </div>
</div>