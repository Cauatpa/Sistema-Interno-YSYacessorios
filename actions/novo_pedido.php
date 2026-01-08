<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$produto     = $_POST['produto'] ?? null;
$quantidade  = $_POST['quantidade_solicitada'] ?? null;
$tipo        = $_POST['tipo'] ?? null;
$solicitante = $_POST['solicitante'] ?? null;

if (!$produto || !$quantidade || !$tipo || !$solicitante) {
    die('Preencha todos os campos');
}

$sql = "
    require_once __DIR__ . '../helpers/competencia.php';
    require_once __DIR__ . '../services/fechamento.php';

    INSERT INTO retiradas 
    (produto, quantidade_solicitada, tipo, solicitante, status, data_pedido, competencia, status_mes)
    VALUES (?, ?, ?, ?, 'pedido', NOW())
";

// SALVAR MÊS FECHADO
$data_pedido = $data_pedido ?? date('Y-m-d H:i:s'); // se já existir, mantém
$competencia = competencia_from_datetime($data_pedido);

if (mes_esta_fechado($pdo, $competencia)) {
    die("Não é possível criar retirada em mês fechado ($competencia).");
}

// EXECUTAR INSERÇÃO
$stmt = $pdo->prepare($sql);

if ($stmt->execute([$produto, $quantidade, $tipo, $solicitante])) {
    header('Location: ../index.php');
    exit;
}

die('Erro ao salvar pedido');
