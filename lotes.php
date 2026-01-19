<?php
require_once __DIR__ . '/pages/lotes_controller.php';
require_once __DIR__ . '/pages/lotes_view.php';
require_once __DIR__ . '/helpers/bootstrap_admin.php';
bootstrap_initial_admin($pdo);

// opcional: permissão mínima (operador ou admin)
if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}
