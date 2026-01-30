<?php
// usa $user e $cards
require_once __DIR__ . '/../helpers/csrf.php';
csrf_session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>InterYSY - Central</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/imgs/Y.png">
    <link rel="stylesheet" href="assets/css/central.css">
</head>

<body class="p-3">
    <div class="container">

        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Central InterYSY</h3>
                <small class="text-muted">
                    Olá, <?= htmlspecialchars($user['nome'] ?? '') ?>
                </small>
            </div>
            <div>
                <button id="btnTheme" class="btn btn-outline-secondary btn-sm">🌙 Tema escuro</button>

                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMinhaSenha">
                    🔑 Minha senha
                </button>

                <form method="POST" action="logout.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('logout')) ?>">
                    <button class="btn btn-outline-danger btn-sm" type="submit">Sair</button>
                </form>
            </div>
        </div>

        <!-- Cards -->
        <div class="row g-3">
            <?php foreach ($cards as $c): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <?php if (!empty($c['enabled'])): ?>
                        <a href="<?= htmlspecialchars($c['href'] ?? '#') ?>" class="text-decoration-none">
                            <div class="card h-100 portal-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="portal-emoji"><?= $c['icon'] ?? '📦' ?></div>

                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <h5 class="mb-1 portal-title"><?= htmlspecialchars($c['title'] ?? '') ?></h5>
                                            </div>

                                            <p class="mb-0 portal-desc">
                                                <?= htmlspecialchars($c['desc'] ?? '') ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-auto pt-3 portal-open">
                                        <span>Acessar</span>
                                        <span class="portal-open-arrow">→</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="card h-100 portal-card portal-disabled">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="portal-emoji"><?= $c['icon'] ?? '⏳' ?></div>

                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 portal-title"><?= htmlspecialchars($c['title'] ?? '') ?></h5>
                                        <p class="mb-0 portal-desc">
                                            <?= htmlspecialchars($c['desc'] ?? '') ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-auto pt-3">
                                    <span class="badge text-bg-secondary">Em breve</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>


        <p class="text-center mt-4 text-muted" style="font-size:13px;">
            InterYSY • Sistema Interno
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js" defer></script>

    <!-- Modal Minha Senha -->
    <div class="modal fade" id="modalMinhaSenha" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">

                <form method="POST" action="actions/minha_senha.php" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('minha_senha')) ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">🔑 Alterar minha senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">

                        <div class="alert alert-secondary py-2">
                            <div><strong>Usuário:</strong> <?= htmlspecialchars($user['nome'] ?? '—') ?></div>
                            <div><strong>Login:</strong> <code><?= htmlspecialchars($user['usuario'] ?? '—') ?></code></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Senha atual</label>
                            <input
                                type="password"
                                name="senha_atual"
                                class="form-control"
                                required
                                autocomplete="current-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nova senha</label>
                            <input
                                type="password"
                                name="senha_nova"
                                class="form-control"
                                minlength="8"
                                required
                                autocomplete="new-password">
                            <small class="text-muted">Mínimo 8 caracteres.</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Confirmar nova senha</label>
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

</body>

</html>