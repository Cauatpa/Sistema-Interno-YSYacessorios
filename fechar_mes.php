<?php
// ajuste o include da sua conexão:
require_once __DIR__ . '/config/conexao.php'; // <-- MUDE AQUI se seu arquivo tiver outro nome/caminho

require_once __DIR__ . '/services/fechamento.php';
require_once __DIR__ . '/helpers/competencia.php';

$competencia = $_POST['competencia'] ?? '';
$usuario = $_POST['usuario'] ?? 'Admin'; // ideal: usuário logado
$confirm = $_POST['confirm'] ?? '';
$observacao = $_POST['observacao'] ?? null;

if ($confirm !== "FECHAR $competencia") {
    http_response_code(400);
    echo "Confirmação inválida. Digite exatamente: FECHAR $competencia";
    exit;
}

$result = fechar_mes($pdo, $competencia, $usuario, $observacao);

if (!$result['ok']) {
    http_response_code(400);
    echo $result['error'];
    exit;
}

echo "Mês {$result['competencia']} fechado com sucesso. Total de registros: {$result['total_registros']}";
