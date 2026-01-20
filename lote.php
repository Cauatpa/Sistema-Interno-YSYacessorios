<?php
require_once __DIR__ . '/config/database.php';

require_once __DIR__ . '/helpers/bootstrap_admin.php';
bootstrap_initial_admin($pdo);

require_once __DIR__ . '/helpers/auth.php';
if (!auth_has_role('operador')) {
    http_response_code(403);
    die('Sem permissão.');
}

require_once __DIR__ . '/pages/lote_controller.php';
require_once __DIR__ . '/pages/lote_view.php';
