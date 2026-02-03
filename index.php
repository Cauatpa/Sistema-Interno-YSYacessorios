<?php
require_once __DIR__ . '/config/bootstrap.php';
bootstrap_app();
require_once __DIR__ . '/config/database.php';   // cria $pdo
require_once __DIR__ . '/helpers/bootstrap_admin.php';
require_once __DIR__ . '/helpers/auth.php';

bootstrap_initial_admin($pdo);
auth_require_login();

// página padrão
$page = $_GET['page'] ?? 'portal';

// allowlist
$routes = [
    'portal'   => ['controller' => 'pages/portal_controller.php', 'view' => 'pages/portal_view.php'],

    // ✅ Retiradas
    'retiradas' => ['controller' => 'pages/index_controller.php', 'view' => 'pages/index_view.php'],

    // ✅ Lotes
    'lotes'    => ['controller' => 'pages/lotes_controller.php', 'view' => 'pages/lotes_view.php'],

    // ✅ Auditoria
    'auditoria' => ['controller' => 'pages/auditoria_controller.php', 'view' => 'pages/auditoria_view.php'],
];

if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'portal';
}

$controllerPath = __DIR__ . '/' . $routes[$page]['controller'];
$viewPath       = __DIR__ . '/' . $routes[$page]['view'];

if (!is_file($controllerPath) || !is_file($viewPath)) {
    http_response_code(500);
    exit("Rota '{$page}' aponta para arquivos inexistentes.");
}

require_once $controllerPath;
require_once $viewPath;
