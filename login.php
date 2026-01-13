<?php
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/auth.php';

auth_session_start();

// Se já estiver logado, manda pra home
if (auth_user()) {
    header('Location: index.php');
    exit;
}

// Erro genérico (não revela se user existe etc.)
$err = (int)($_GET['err'] ?? 0);
$wait = (int)($_GET['wait'] ?? 0);

$erroMsg = '';
if ($err === 1) {
    $erroMsg = ($wait > 0)
        ? "Muitas tentativas. Aguarde {$wait} min e tente novamente."
        : "Usuário ou senha inválidos.";
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

                <?php if ($erroMsg !== ''): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erroMsg) ?></div>
                <?php endif; ?>

                <form method="POST" action="actions/login.php" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('login')) ?>">

                    <div class="mb-3">
                        <label class="form-label">Usuário</label>
                        <input
                            class="form-control"
                            name="usuario"
                            required
                            maxlength="80"
                            autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input
                            class="form-control"
                            type="password"
                            name="senha"
                            required
                            autocomplete="current-password">
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