<?php
require_once __DIR__ . '/config/bootstrap.php';
bootstrap_app();
require_once __DIR__ . '/config/database.php'; // <- CRIA $pdo

require_once __DIR__ . '/helpers/bootstrap_admin.php';
bootstrap_initial_admin($pdo);

// permissão mínima (operador ou admin)
require_once __DIR__ . '/helpers/auth.php';
if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

require_once __DIR__ . '/pages/lotes_controller.php';
require_once __DIR__ . '/pages/lotes_view.php';
