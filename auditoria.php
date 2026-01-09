<?php
require 'config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/csrf.php';

auth_session_start();
auth_require_role('admin');

$limit = 200;

$stmt = $pdo->prepare("
    SELECT a.*, u.nome AS user_nome, u.usuario AS user_login
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.id DESC
    LIMIT {$limit}
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h3 class="m-0">🧾 Auditoria</h3>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Quando</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>ID</th>
                        <th>IP</th>
                        <th>Payload</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$l['created_at']) ?></td>
                            <td>
                                <?= htmlspecialchars((string)($l['user_nome'] ?? '—')) ?>
                                <div class="text-muted small"><?= htmlspecialchars((string)($l['user_login'] ?? '')) ?></div>
                            </td>
                            <td><code><?= htmlspecialchars((string)$l['action']) ?></code></td>
                            <td><?= htmlspecialchars((string)$l['entity']) ?></td>
                            <td><?= htmlspecialchars((string)($l['entity_id'] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string)($l['ip'] ?? '')) ?></td>
                            <td style="max-width: 420px; white-space: pre-wrap;">
                                <?= htmlspecialchars((string)($l['payload_json'] ?? '')) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>

</html>