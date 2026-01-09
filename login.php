<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/auth.php';

auth_session_start();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null, 'login')) {
        $erro = 'CSRF inválido.';
    } else {
        $usuario = trim((string)($_POST['usuario'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');

        if (auth_login($pdo, $usuario, $senha)) {
            header('Location: index.php');
            exit;
        }
        $erro = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Login - Controle Estoque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-3 d-flex align-items-center" style="min-height:100vh;">
    <div class="container" style="max-width:420px;">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3 text-center">🔐 Acessar sistema</h4>

                <?php if ($erro): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('login')) ?>">

                    <div class="mb-3">
                        <label class="form-label">Usuário</label>
                        <input class="form-control" name="usuario" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input class="form-control" type="password" name="senha" required>
                    </div>

                    <button class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 text-muted mb-0" style="font-size:13px;">
            Controle de Retiradas - YSY
        </p>
    </div>
</body>

</html>