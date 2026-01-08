<?php
require '../config/database.php';

require_once __DIR__ . '/../helpers/competencia.php';
require_once __DIR__ . '/../services/fechamento.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// 🔹 Dados vindos do modal
$id = (int) ($_POST['id'] ?? 0);
$quantidade_retirada = (int) ($_POST['quantidade_retirada'] ?? 0);
$responsavel_estoque = trim($_POST['responsavel_estoque'] ?? '');
$precisa_balanco = (int)($_POST['precisa_balanco'] ?? 0);
$sem_estoque     = (int)($_POST['sem_estoque'] ?? 0);

if ($id <= 0 || $quantidade_retirada < 0 || $responsavel_estoque === '') {
    die('Dados inválidos');
}

// 1️⃣ Buscar quantidade solicitada + competência
$stmt = $pdo->prepare("
    SELECT quantidade_solicitada, competencia
    FROM retiradas
    WHERE id = ?
");
$stmt->execute([$id]);
$retirada = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$retirada) {
    die('Retirada não encontrada');
}

$competencia = $retirada['competencia'] ?? null;
$qtd_solicitada = (int) $retirada['quantidade_solicitada'];

// 🔒 Bloquear edição se o mês estiver fechado
if ($competencia && mes_esta_fechado($pdo, $competencia)) {
    die("Esse mês ($competencia) está fechado. Não é possível finalizar/alterar registros.");
}

// 2️⃣ Regras automáticas
// Se retirou menos do que pediu, precisa balanço e está sem estoque
if ($quantidade_retirada < $qtd_solicitada) {
    $precisa_balanco = 1;
    $sem_estoque = 1;
}

// 3️⃣ Atualizar retirada
$update = $pdo->prepare("
    UPDATE retiradas
    SET
        quantidade_retirada = ?,
        responsavel_estoque = ?,
        precisa_balanco = ?,
        sem_estoque = ?,
        status = 'finalizado',
        data_finalizacao = NOW()
    WHERE id = ?
");
$update->execute([
    $quantidade_retirada,
    $responsavel_estoque,
    $precisa_balanco,
    $sem_estoque,
    $id
]);

// ✅ volta mantendo o mês na tela
$redirComp = $competencia && competencia_valida($competencia) ? $competencia : competencia_atual();
header('Location: ../index.php?competencia=' . urlencode($redirComp));
exit;
