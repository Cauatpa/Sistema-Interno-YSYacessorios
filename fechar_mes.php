<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/competencia.php';
require_once __DIR__ . '/services/fechamento.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/validation.php';

post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'fechar_mes')) {
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('fechar_mes');

$competencia = trim((string)($_POST['competencia'] ?? ''));
$usuario = trim((string)($_POST['usuario'] ?? 'Admin'));
$confirm = trim((string)($_POST['confirm'] ?? ''));
$observacao = isset($_POST['observacao']) ? trim((string)$_POST['observacao']) : null;

if (!competencia_valida($competencia)) {
    http_response_code(400);
    exit('Competência inválida.');
}

if ($confirm !== "FECHAR {$competencia}") {
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: FECHAR {$competencia}");
}

// Fecha mês
$result = fechar_mes($pdo, $competencia, $usuario, $observacao);

if (!is_array($result) || empty($result['ok'])) {
    http_response_code(400);
    $msg = is_array($result) && !empty($result['error']) ? $result['error'] : 'Erro ao fechar mês.';
    exit($msg);
}

// volta pra tela do mês fechado
redirect_with_query('index.php', ['competencia' => $competencia]);
