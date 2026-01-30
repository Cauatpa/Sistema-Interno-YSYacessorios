<?php
// pages/relatorios_view.php
// usa $user e $cards

require_once __DIR__ . '/../helpers/csrf.php';
csrf_session_start();

/**
 * Base URL: garante paths corretos mesmo com router (index.php) ou acesso direto em /pages
 */
$script = str_replace('\\', '/', ($_SERVER['SCRIPT_NAME'] ?? ''));
$dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

$base = $dir;
if (substr($dir, -6) === '/pages') {
    $base = substr($dir, 0, -6); // remove "/pages"
}
$base = rtrim($base, '/');

$asset = function (string $path) use ($base) {
    return $base . '/' . ltrim($path, '/');
};
$goIndex = $base . '/index.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>InterYSY - Relatórios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ✅ aplica tema ANTES do CSS (não nasce branco) -->
    <script>
        (function() {
            const key = "theme";
            const saved = localStorage.getItem(key);
            const theme = (saved === "dark" || saved === "light") ? saved : "light";
            document.documentElement.setAttribute("data-bs-theme", theme);
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ mesmo estilo do portal -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($asset('assets/imgs/Y.png')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/central.css')) ?>">
</head>

<body class="p-3">
    <div class="container">

        <!-- Cabeçalho igual portal -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0">Relatórios</h3>
                <small class="text-muted">
                    Olá, <?= htmlspecialchars($user['nome'] ?? '') ?>
                </small>
            </div>

            <div class="d-flex gap-2">
                <!-- ✅ voltar SEM depender de page=portal -->
                <a href="<?= htmlspecialchars($goIndex) ?>" class="btn btn-outline-secondary btn-sm">← Voltar</a>

                <!-- ✅ mesmo id do portal -->
                <button id="btnTheme" type="button" class="btn btn-outline-secondary btn-sm">🌙 Tema escuro</button>
            </div>
        </div>

        <!-- Cards com o mesmo “skin” do portal -->
        <div class="row g-3">
            <?php foreach (($cards ?? []) as $c): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <?php if (!empty($c['enabled'])): ?>
                        <a href="<?= htmlspecialchars($c['href'] ?? '#') ?>" class="text-decoration-none">
                            <div class="card h-100 portal-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="portal-emoji"><?= $c['icon'] ?? '📊' ?></div>

                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 portal-title"><?= htmlspecialchars($c['title'] ?? '') ?></h5>
                                            <p class="mb-0 portal-desc"><?= htmlspecialchars($c['desc'] ?? '') ?></p>
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
                                        <p class="mb-0 portal-desc"><?= htmlspecialchars($c['desc'] ?? '') ?></p>
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
            InterYSY • Central de Sistemas
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ✅ carrega o theme.js com path garantido -->
    <script src="<?= htmlspecialchars($asset('assets/js/theme.js')) ?>"></script>
</body>

</html>