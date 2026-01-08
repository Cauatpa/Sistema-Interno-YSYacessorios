<?php
require_once '../config/database.php';

require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$produto     = trim($_POST['produto'] ?? '');
$quantidade  = (int)($_POST['quantidade_solicitada'] ?? 0);
$tipo        = trim($_POST['tipo'] ?? '');
$solicitante = trim($_POST['solicitante'] ?? '');

if ($produto === '' || $quantidade <= 0 || $tipo === '' || $solicitante === '') {
    die('Preencha todos os campos corretamente');
}

// Define data do pedido e competência
$data_pedido = date('Y-m-d H:i:s');
$competencia = competencia_from_datetime($data_pedido);

// Bloquear criar em mês fechado
if (mes_esta_fechado($pdo, $competencia)) {
    die("Não é possível criar retirada em mês fechado ($competencia).");
}

// Inserir (com placeholders corretos)
$sql = "
    INSERT INTO retiradas 
        (produto, quantidade_solicitada, tipo, solicitante, status, data_pedido, competencia, status_mes)
    VALUES 
        (?, ?, ?, ?, 'pedido', ?, ?, 'ABERTO')
";

$stmt = $pdo->prepare($sql);

$ok = $stmt->execute([
    $produto,
    $quantidade,
    $tipo,
    $solicitante,
    $data_pedido,
    $competencia
]);

if ($ok) {
    // volta já no mês criado
    header('Location: ../index.php?competencia=' . urlencode($competencia));
    exit;
}

die('Erro ao salvar pedido');
