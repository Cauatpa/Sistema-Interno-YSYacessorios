<?php
require 'config/database.php';
require_once __DIR__ . '/helpers/competencia.php';

$competencia = $_GET['competencia'] ?? competencia_atual();
if (!competencia_valida($competencia)) {
    $competencia = competencia_atual();
}

// ✅ SELECT correto com prepare + execute
$stmt = $pdo->prepare("SELECT * FROM retiradas WHERE competencia = ? ORDER BY data_pedido DESC");
$stmt->execute([$competencia]);
$retiradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ lista de meses disponíveis (pra dropdown)
$stmtMeses = $pdo->query("SELECT DISTINCT competencia FROM retiradas ORDER BY competencia DESC");
$mesesDisponiveis = $stmtMeses->fetchAll(PDO::FETCH_COLUMN);

// ✅ saber se o mês atual está fechado (pra bloquear botões)
$stmtFechado = $pdo->prepare("SELECT 1 FROM fechamentos WHERE competencia = ? LIMIT 1");
$stmtFechado->execute([$competencia]);
$mesFechado = (bool) $stmtFechado->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Controle de Estoque</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS próprio -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="p-3">

    <h2 class="text-center mb-4">📦 Controle de Retirada do Estoque</h2>

    <!-- Barra superior: Filtro mês + Fechar mês -->
    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-stretch mb-3">

        <!-- Selecionar competência -->
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="fw-bold">Mês:</label>

            <select name="competencia" class="form-select" onchange="this.form.submit()">
                <?php if (!in_array($competencia, $mesesDisponiveis, true)): ?>
                    <option value="<?= htmlspecialchars($competencia) ?>" selected>
                        <?= htmlspecialchars($competencia) ?> (atual)
                    </option>
                <?php endif; ?>

                <?php foreach ($mesesDisponiveis as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $m === $competencia ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <noscript>
                <button class="btn btn-secondary">Filtrar</button>
            </noscript>
        </form>

        <!-- Fechar mês -->
        <form method="POST" action="fechar_mes.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="competencia" value="<?= htmlspecialchars($competencia) ?>">
            <input type="hidden" name="usuario" value="Cauã"><!-- ideal: usuário logado -->

            <?php if ($mesFechado): ?>
                <button type="button" class="btn btn-outline-secondary" disabled>
                    🔒 Mês fechado
                </button>
            <?php else: ?>
                <input
                    name="confirm"
                    class="form-control"
                    placeholder="FECHAR <?= htmlspecialchars($competencia) ?>"
                    required>
                <button type="submit" class="btn btn-danger">
                    📅 Fechar mês
                </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Botão Novo Pedido -->
    <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-primary btn-lg w-100 w-md-auto"
            data-bs-toggle="modal"
            data-bs-target="#modalNovoPedido"
            <?= $mesFechado ? 'disabled' : '' ?>>
            ➕ Novo Pedido
        </button>
    </div>

    <?php if ($mesFechado): ?>
        <div class="alert alert-warning text-center">
            🔒 Este mês (<?= htmlspecialchars($competencia) ?>) está <strong>FECHADO</strong>. Não é possível criar ou finalizar pedidos nele.
        </div>
    <?php endif; ?>

    <!-- Tabela -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>🕒 Pedido</th>
                    <th>📦 Produto</th>
                    <th>🔢 Qtd</th>

                    <!-- Só aparece no PC -->
                    <th class="d-none d-md-table-cell">🔖 Tipo</th>
                    <th class="d-none d-md-table-cell">👤 Solicitante</th>
                    <th class="d-none d-md-table-cell">⏱ Finalização</th>
                    <th class="d-none d-md-table-cell">👷 Estoque</th>

                    <th>📌 Status</th>
                    <th>⚙ Ação</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($retiradas as $r):

                    if (!empty($r['sem_estoque'])) {
                        $classe = 'status-balanco';
                        $status = '🔴 Precisa de estoque';
                    } elseif (!empty($r['precisa_balanco'])) {
                        $classe = 'status-balanco';
                        $status = '🟡 Precisa de balanço';
                    } elseif ($r['status'] === 'finalizado') {
                        $classe = 'status-finalizado';
                        $status = '✅ Finalizado';
                    } else {
                        $classe = 'status-pedido';
                        $status = '⏳ Pendente';
                    }
                ?>
                    <tr class="<?= $classe ?>">
                        <td><?= date('d/m H:i', strtotime($r['data_pedido'])) ?></td>

                        <td>
                            <strong><?= htmlspecialchars($r['produto']) ?></strong>
                        </td>

                        <td><?= (int)$r['quantidade_solicitada'] ?></td>

                        <!-- PC -->
                        <td class="d-none d-md-table-cell"><?= ucfirst($r['tipo']) ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($r['solicitante']) ?></td>

                        <td class="d-none d-md-table-cell">
                            <?= $r['data_finalizacao']
                                ? date('d/m H:i', strtotime($r['data_finalizacao']))
                                : '—' ?>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <?= $r['responsavel_estoque'] ?? '—' ?>
                        </td>

                        <td><strong><?= $status ?></strong></td>

                        <td>
                            <?php if ($r['status'] !== 'finalizado' && !$mesFechado): ?>
                                <button
                                    class="btn btn-success w-100"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalFinalizar<?= (int)$r['id'] ?>">
                                    ✅ Finalizar
                                </button>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modais -->
    <?php include 'modals/_load_modals.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>