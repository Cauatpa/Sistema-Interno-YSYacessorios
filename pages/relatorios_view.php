<?php
// pages/relatorios_view.php
// usa $user e $cards
require_once __DIR__ . '/../helpers/csrf.php';
csrf_session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>InterYSY - Relatórios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/imgs/Y.png">
</head>

<body class="p-3">
    <div class="container">

        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Relatórios</h3>
                <small class="text-muted">
                    Olá, <?= htmlspecialchars($user['nome'] ?? '') ?>
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="../index.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>
                <button id="btnTheme" class="btn btn-outline-secondary btn-sm">🌙 Tema escuro</button>
            </div>
        </div>

        <!-- Cards -->
        <div class="row g-3">
            <?php foreach ($cards as $c): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <?php if (!empty($c['enabled'])): ?>
                        <a href="<?= htmlspecialchars($c['href']) ?>" class="card h-100 text-decoration-none">
                            <div class="card-body">
                                <div style="font-size:28px"><?= $c['icon'] ?></div>
                                <h5 class="mt-2"><?= htmlspecialchars($c['title']) ?></h5>
                                <p class="text-muted mb-0"><?= htmlspecialchars($c['desc']) ?></p>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="card h-100 opacity-75">
                            <div class="card-body">
                                <div style="font-size:28px"><?= $c['icon'] ?></div>
                                <h5 class="mt-2"><?= htmlspecialchars($c['title']) ?></h5>
                                <p class="text-muted mb-0"><?= htmlspecialchars($c['desc']) ?></p>
                                <span class="badge bg-secondary mt-2">Em breve</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center mt-4 text-muted" style="font-size:13px;">
            InterYSY • Central de Sistemas
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js" defer></script>
</body>

</html>