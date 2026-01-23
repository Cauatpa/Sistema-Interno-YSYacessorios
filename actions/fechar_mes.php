<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';
require_once __DIR__ . '/../helpers/audit.php';

auth_session_start();
auth_require_role('admin');
post_only();

// CSRF
if (!csrf_validate($_POST['csrf_token'] ?? null, 'fechar_mes')) {
    audit_log(
        $pdo,
        'close_month',
        'fechamento',
        null,
        ['reason' => 'csrf_invalid'],
        null,
        null,
        false,
        'csrf_invalid',
        'Falha ao fechar mês (CSRF inválido).'
    );
    http_response_code(403);
    exit('CSRF inválido.');
}
csrf_rotate('fechar_mes');

$competencia = trim((string)($_POST['competencia'] ?? ''));
$usuario = trim((string)($_POST['usuario'] ?? 'Admin'));
$confirm = trim((string)($_POST['confirm'] ?? ''));
$observacao = isset($_POST['observacao']) ? trim((string)$_POST['observacao']) : null;

if (!competencia_valida($competencia)) {
    audit_log(
        $pdo,
        'close_month',
        'fechamento',
        null,
        ['reason' => 'invalid_competencia', 'competencia' => $competencia],
        null,
        null,
        false,
        'invalid_competencia',
        'Falha ao fechar mês (competência inválida).'
    );
    http_response_code(400);
    exit('Competência inválida.');
}

if ($confirm !== "FECHAR {$competencia}") {
    audit_log(
        $pdo,
        'close_month',
        'fechamento',
        null,
        ['reason' => 'invalid_confirm', 'competencia' => $competencia, 'confirm' => $confirm],
        null,
        null,
        false,
        'invalid_confirm',
        "Falha ao fechar mês {$competencia} (confirmação inválida)."
    );
    http_response_code(400);
    exit("Confirmação inválida. Digite exatamente: FECHAR {$competencia}");
}

// BEFORE (se já existe fechamento)
$chk = $pdo->prepare("SELECT * FROM fechamentos WHERE competencia = ? LIMIT 1");
$chk->execute([$competencia]);
$before = $chk->fetch(PDO::FETCH_ASSOC) ?: null;

// Fecha mês
$result = fechar_mes($pdo, $competencia, $usuario, $observacao);

if (!is_array($result) || empty($result['ok'])) {
    $msg = is_array($result) && !empty($result['error']) ? (string)$result['error'] : 'Erro ao fechar mês.';
    audit_log(
        $pdo,
        'close_month',
        'fechamento',
        null,
        [
            'reason' => 'service_error',
            'competencia' => $competencia,
            'usuario' => $usuario,
            'observacao' => $observacao,
            'error' => $msg
        ],
        $before,
        null,
        false,
        'service_error',
        "Falha ao fechar mês {$competencia}."
    );
    http_response_code(400);
    exit($msg);
}

// AFTER
$chk2 = $pdo->prepare("SELECT * FROM fechamentos WHERE competencia = ? LIMIT 1");
$chk2->execute([$competencia]);
$after = $chk2->fetch(PDO::FETCH_ASSOC) ?: ['competencia' => $competencia, 'status' => 'fechado'];

audit_log(
    $pdo,
    'close_month',
    'fechamento',
    null,
    [
        'competencia' => $competencia,
        'usuario' => $usuario,
        'observacao' => $observacao,
        'result' => $result,
        'had_closure_before' => $before ? 1 : 0
    ],
    $before,
    $after,
    true,
    null,
    "Fechou o mês {$competencia}."
);

// volta pra tela do mês fechado
header('Location: ../index.php?toast=mes_fechado&competencia=' . urlencode($competencia));
exit;
