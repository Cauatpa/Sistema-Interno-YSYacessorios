<?php
require 'config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/csrf.php';

auth_session_start();
$u = auth_require_login();

$toast = $_GET['toast'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Trocar minha senha</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-3">
    <div class="container" style="max-width: 560px;">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="m-0">🔑 Trocar minha senha</h4>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
        </div>

        <?php if ($toast === 'ok'): ?>
            <div class="alert alert-success">✅ Senha alterada com sucesso!</div>
        <?php elseif ($toast === 'erro'): ?>
            <div class="alert alert-danger">❌ Não foi possível alterar a senha. Confira os dados.</div>
        <?php endif; ?>

        <div class="card p-3">
            <form method="POST" action="actions/minha_senha_trocar.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('minha_senha_trocar')) ?>">

                <div class="mb-3">
                    <label class="form-label">Senha atual</label>
                    <input type="password" name="senha_atual" class="form-control" required autocomplete="current-password">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nova senha</label>
                    <input type="password" name="senha_nova" class="form-control" minlength="8" required autocomplete="new-password">
                    <small class="text-muted">Mínimo 8 caracteres.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirmar nova senha</label>
                    <input type="password" name="senha_confirm" class="form-control" minlength="8" required autocomplete="new-password">
                </div>

                <button class="btn btn-primary w-100">Salvar</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>