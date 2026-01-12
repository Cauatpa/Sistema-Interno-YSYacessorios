<?php
require 'config/database.php';

require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/csrf.php';

auth_session_start();
auth_require_role('admin');

$limit = 50;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$q = trim((string)($_GET['q'] ?? '')); // busca simples
$action = trim((string)($_GET['action'] ?? ''));
$entity = trim((string)($_GET['entity'] ?? ''));

$where = " WHERE 1=1 ";
$params = [];

// busca por usuário/ação/entidade
if ($q !== '') {
    $where .= " AND (u.nome LIKE ? OR u.usuario LIKE ? OR a.action LIKE ? OR a.entity LIKE ?) ";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($action !== '') {
    $where .= " AND a.action = ? ";
    $params[] = $action;
}
if ($entity !== '') {
    $where .= " AND a.entity = ? ";
    $params[] = $entity;
}

// total
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM audit_logs a
    JOIN users u ON u.id = a.user_id
    $where
");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

// lista
$stmt = $pdo->prepare("
    SELECT a.*, u.nome, u.usuario
    FROM audit_logs a
    JOIN users u ON u.id = a.user_id
    $where
    ORDER BY a.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v): string
{
    return htmlspecialchars((string)$v);
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Auditoria</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-3">
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">🧾 Auditoria (logs)</h3>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
            </div>
        </div>

        <form method="GET" class="card p-3 mb-3">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label mb-1">Buscar</label>
                    <input name="q" class="form-control" value="<?= h($q) ?>" placeholder="nome, usuário, action, entity...">
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Action</label>
                    <select name="action" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (['create', 'finalize', 'edit', 'delete', 'close_month', 'reopen_month', 'reset_password'] as $a): ?>
                            <option value="<?= h($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= h($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Entity</label>
                    <select name="entity" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (['retirada', 'mes', 'user'] as $e): ?>
                            <option value="<?= h($e) ?>" <?= $entity === $e ? 'selected' : '' ?>><?= h($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Filtrar</button>
                    <a class="btn btn-outline-secondary" href="auditoria.php">Limpar</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Quando</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>ID Entidade</th>
                        <th>IP</th>
                        <th>Payload</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= (int)$l['id'] ?></td>
                            <td><?= h($l['created_at']) ?></td>
                            <td><?= h($l['nome']) ?> <br><small class="text-muted">@<?= h($l['usuario']) ?></small></td>
                            <td><code><?= h($l['action']) ?></code></td>
                            <td><code><?= h($l['entity']) ?></code></td>
                            <td><?= h($l['entity_id'] ?? '—') ?></td>
                            <td><?= h($l['ip'] ?? '—') ?></td>
                            <td style="max-width:360px; white-space:pre-wrap;">
                                <small><?= h($l['payload_json'] ?? '') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$logs): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Sem logs.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Total: <?= (int)$total ?> | Página <?= (int)$page ?> / <?= (int)$totalPages ?>
            </div>
            <div class="d-flex gap-2">
                <?php if ($page > 1): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= h('auditoria.php?' . http_build_query(array_merge($_GET, ['p' => $page - 1]))) ?>">←</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= h('auditoria.php?' . http_build_query(array_merge($_GET, ['p' => $page + 1]))) ?>">→</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>