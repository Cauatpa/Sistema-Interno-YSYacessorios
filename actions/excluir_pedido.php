<?php
require_once '../config/database.php';

require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$usuario = trim($_POST['usuario'] ?? 'Admin');
$confirm = trim($_POST['confirm'] ?? '');

if ($id <= 0) {
    die('ID inválido');
}

// Buscar competencia + se já foi excluído
$stmt = $pdo->prepare("SELECT competencia, deleted_at FROM retiradas WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('Pedido não encontrado');
}

$competencia = $row['competencia'] ?? competencia_atual();
$jaExcluido = !empty($row['deleted_at']);

if ($jaExcluido) {
    header('Location: ../index.php?competencia=' . urlencode($competencia));
    exit;
}

// Bloquear se mês fechado
if ($competencia && mes_esta_fechado($pdo, $competencia)) {
    die("Esse mês ($competencia) está fechado. Não é possível excluir pedidos.");
}

// Confirmação forte
if ($confirm !== "EXCLUIR $id") {
    die("Confirmação inválida. Digite exatamente: EXCLUIR $id");
}

// Soft delete
$upd = $pdo->prepare("
    UPDATE retiradas
    SET deleted_at = NOW(), deleted_by = ?
    WHERE id = ? AND deleted_at IS NULL
");
$upd->execute([$usuario, $id]);

header('Location: ../index.php?competencia=' . urlencode($competencia) . '&toast=excluido');
exit;
