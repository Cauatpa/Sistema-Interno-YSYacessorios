<?php
// Este arquivo é incluído dentro do foreach ($users as $u) no usuarios.php
// Então a variável $u existe aqui.

require_once __DIR__ . '/../helpers/csrf.php';

// garante sessão para gerar/ler token com consistência
csrf_session_start();

$idUser = (int)($u['id'] ?? 0);
$nomeUser = (string)($u['nome'] ?? '');
$usuarioLogin = (string)($u['usuario'] ?? '');

if ($idUser <= 0) {
    // evita renderizar modal inválido caso venha dado quebrado
    return;
}
?>

<div class="modal fade" id="modalSenha<?= $idUser ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form method="POST" action="actions/usuarios_trocar_senha.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('usuarios_trocar_senha')) ?>">
                <input type="hidden" name="id" value="<?= (int)$idUser ?>">

                <div class="modal-header">
                    <h5 class="modal-title">🔑 Trocar senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-secondary py-2">
                        <div><strong>Usuário:</strong> <?= htmlspecialchars($nomeUser) ?></div>
                        <div><strong>Login:</strong> <code><?= htmlspecialchars($usuarioLogin) ?></code></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nova senha</label>
                        <input
                            type="password"
                            name="senha"
                            class="form-control"
                            minlength="8"
                            required
                            autocomplete="new-password">
                        <small class="text-muted">Mínimo 8 caracteres.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Confirmar senha</label>
                        <input
                            type="password"
                            name="senha_confirm"
                            class="form-control"
                            minlength="8"
                            required
                            autocomplete="new-password">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Salvar senha
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>