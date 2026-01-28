<?php
// pages/relatorios_view.php
// usa $user e $cards
require_once __DIR__ . '/../helpers/csrf.php';
csrf_session_start();
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>InterYSY - Relatórios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Ícone + CSS da página -->
    <link rel="icon" type="image/png" href="../assets/imgs/Y.png">
    <link rel="stylesheet" href="../assets/css/relatorios.css">
</head>

<body class="p-3">
    <div class="container">

        <!-- Cabeçalho -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h3 class="mb-0 page-title">Relatórios</h3>
                <small class="text-muted page-sub">
                    Olá, <?= htmlspecialchars($user['nome'] ?? '') ?>
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="../index.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>
                <button id="btnTheme" class="btn btn-outline-secondary btn-sm">🌙 Tema</button>
            </div>
        </div>

        <!-- Cards -->
        <div class="row g-3">
            <?php foreach ($cards as $c): ?>
                <div class="col-12 col-md-6 col-lg-4">

                    <?php if (!empty($c['enabled'])): ?>
                        <a href="<?= htmlspecialchars($c['href'] ?? '#') ?>" class="text-decoration-none">
                            <div class="card h-100 report-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="report-icon"><?= $c['icon'] ?? '📊' ?></div>

                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 report-title"><?= htmlspecialchars($c['title'] ?? '') ?></h5>
                                            <p class="mb-0 report-desc"><?= htmlspecialchars($c['desc'] ?? '') ?></p>
                                        </div>
                                    </div>

                                    <div class="mt-auto pt-3 report-open">
                                        <span>Acessar</span>
                                        <span class="arrow">→</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php else: ?>
                        <div class="card h-100 report-card disabled">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="report-icon"><?= $c['icon'] ?? '⏳' ?></div>

                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 report-title"><?= htmlspecialchars($c['title'] ?? '') ?></h5>
                                        <p class="mb-0 report-desc"><?= htmlspecialchars($c['desc'] ?? '') ?></p>
                                    </div>
                                </div>

                                <div class="mt-auto pt-3">
                                    <span class="badge bg-secondary">Em breve</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

        <p class="text-center mt-4 page-footer">
            InterYSY • Central de Sistemas
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js" defer></script>
</body>

</html>