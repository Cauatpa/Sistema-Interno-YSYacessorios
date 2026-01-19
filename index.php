<?php
require_once __DIR__ . '/helpers/bootstrap_admin.php';
require_once __DIR__ . '/pages/index_controller.php';
require_once __DIR__ . '/pages/index_view.php';
bootstrap_initial_admin($pdo);

// página padrão
$page = $_GET['page'] ?? 'home';

// allowlist (segurança)
$routes = [
    'home'  => ['controller' => 'pages/home_controller.php',  'view' => 'pages/home_view.php'],
    'lotes' => ['controller' => 'pages/lotes_controller.php', 'view' => 'pages/lotes_view.php'],
    // depois: 'lote' => ... etc
];

if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'home'; // ou uma view 404
}
