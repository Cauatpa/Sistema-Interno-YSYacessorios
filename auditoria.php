<?php
require 'config/database.php';

require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/csrf.php';

auth_session_start();
auth_require_role('admin');

/**
 * Helpers
 */
function h($v): string
{
    return htmlspecialchars((string)$v);
}

function pretty_json(?string $json): string
{
    if (!$json) return '';
    $d = json_decode($json, true);
    if (!is_array($d)) return (string)$json;
    return json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * Querystring helper: mantém filtros e troca só o que precisa.
 */
function url_with(array $overrides = []): string
{
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return 'auditoria.php?' . http_build_query($q);
}

/**
 * Config paginação
 */
$limitAllowed = [10, 25, 50, 100];
$limit = (int)($_GET['limit'] ?? 25);
if (!in_array($limit, $limitAllowed, true)) $limit = 25;

$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

/**
 * Filtros
 */
$q         = trim((string)($_GET['q'] ?? ''));
$action    = trim((string)($_GET['action'] ?? ''));
$entity    = trim((string)($_GET['entity'] ?? ''));
$success   = trim((string)($_GET['success'] ?? ''));   // '', '1', '0'
$eventCode = trim((string)($_GET['event_code'] ?? ''));

$where = " WHERE 1=1 ";
$params = [];

if ($q !== '') {
    $where .= " AND (
        COALESCE(u.nome,'') LIKE ?
        OR COALESCE(u.usuario,'') LIKE ?
        OR COALESCE(a.message,'') LIKE ?
        OR COALESCE(a.event_code,'') LIKE ?
        OR a.action LIKE ?
        OR a.entity LIKE ?
    ) ";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
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

if ($success === '1' || $success === '0') {
    $where .= " AND a.success = ? ";
    $params[] = (int)$success;
}

if ($eventCode !== '') {
    $where .= " AND a.event_code = ? ";
    $params[] = $eventCode;
}

/**
 * Total
 */
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*)
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    $where
");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();

$totalPages = max(1, (int)ceil($total / $limit));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $limit;

/**
 * Lista
 */
$stmt = $pdo->prepare("
    SELECT a.*, u.nome, u.usuario
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    $where
    ORDER BY a.id DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Range exibido (X–Y de Z)
 */
$from = ($total === 0) ? 0 : ($offset + 1);
$to   = min($offset + $limit, $total);

/**
 * Paginação compacta com “...”
 */
function pagination_items(int $page, int $totalPages): array
{
    // sempre mostra: 1, último, e um “miolo” perto da página atual
    $items = [];

    $add = function ($v) use (&$items) {
        $items[] = $v;
    };

    if ($totalPages <= 9) {
        for ($i = 1; $i <= $totalPages; $i++) $add($i);
        return $items;
    }

    $add(1);

    $left = max(2, $page - 2);
    $right = min($totalPages - 1, $page + 2);

    if ($left > 2) $add('…');

    for ($i = $left; $i <= $right; $i++) $add($i);

    if ($right < $totalPages - 1) $add('…');

    $add($totalPages);

    return $items;
}

$actionOptions = ['create', 'finalize', 'edit', 'delete', 'import', 'close_month', 'reopen_month', 'export', 'reset_password', 'change_password', 'login'];

$entityOptions = ['retirada', 'fechamento', 'user']; // ajuste se você adicionar outros
?>
<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Auditoria</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/imgs/Y.png">
</head>

<body class="p-3">
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">🧾 Auditoria (logs)</h3>

            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">← Voltar</a>
                <button id="btnTheme" class="btn btn-outline-secondary btn-sm">🌙 Tema escuro</button>
            </div>
        </div>

        <form method="GET" class="card p-3 mb-3">
            <input type="hidden" name="p" value="1">

            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">Buscar</label>
                    <input name="q" class="form-control" value="<?= h($q) ?>"
                        placeholder="nome, usuário, message, event_code, action, entity...">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="success" class="form-select">
                        <option value="" <?= $success === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="1" <?= $success === '1' ? 'selected' : '' ?>>OK</option>
                        <option value="0" <?= $success === '0' ? 'selected' : '' ?>>Falha</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Action</label>
                    <select name="action" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($actionOptions as $a): ?>
                            <option value="<?= h($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= h($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label mb-1">Entity</label>
                    <select name="entity" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($entityOptions as $e): ?>
                            <option value="<?= h($e) ?>" <?= $entity === $e ? 'selected' : '' ?>><?= h($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label mb-1">Code</label>
                    <input name="event_code" class="form-control" value="<?= h($eventCode) ?>" placeholder="ex: db_error">
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label mb-1">Por pág.</label>
                    <select name="limit" class="form-select">
                        <?php foreach ($limitAllowed as $l): ?>
                            <option value="<?= (int)$l ?>" <?= $limit === (int)$l ? 'selected' : '' ?>><?= (int)$l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">Filtrar</button>
                    <a class="btn btn-outline-secondary" href="auditoria.php">Limpar</a>
                </div>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted small">
                Mostrando <strong><?= (int)$from ?></strong>–<strong><?= (int)$to ?></strong> de <strong><?= (int)$total ?></strong>
                <?php if ($totalPages > 1): ?>
                    | Página <strong><?= (int)$page ?></strong> / <strong><?= (int)$totalPages ?></strong>
                <?php endif; ?>
            </div>
            <div class="text-muted small">
                Ordenado por <code>id DESC</code>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Quando</th>
                        <th>Usuário</th>
                        <th>Status</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>ID</th>
                        <th>Resumo</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <?php $pid = (int)($l['id'] ?? 0); ?>
                        <tr>
                            <td><?= $pid ?></td>
                            <td><?= h($l['created_at'] ?? '') ?></td>
                            <td>
                                <?= h($l['nome'] ?? '—') ?>
                                <br><small class="text-muted">@<?= h($l['usuario'] ?? '—') ?></small>
                            </td>
                            <td class="text-nowrap">
                                <?php if ((int)($l['success'] ?? 1) === 1): ?>
                                    <span class="badge bg-success">OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">FALHA</span>
                                <?php endif; ?>
                                <?php if (!empty($l['event_code'])): ?>
                                    <div><small class="text-muted"><?= h($l['event_code']) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td><code><?= h($l['action'] ?? '') ?></code></td>
                            <td><code><?= h($l['entity'] ?? '') ?></code></td>
                            <td><?= h($l['entity_id'] ?? '—') ?></td>
                            <td class="text-start" style="max-width:520px;">
                                <div class="small fw-semibold"><?= h($l['message'] ?? '') ?></div>
                                <div class="mt-2 d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#auditModal<?= $pid ?>">
                                        Detalhes
                                    </button>
                                </div>
                            </td>
                            <td><?= h($l['ip'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$logs): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">Sem logs.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <?php
            $items = pagination_items($page, $totalPages);
            ?>
            <nav class="mt-2">
                <ul class="pagination pagination-sm flex-wrap">

                    <!-- First -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= h(url_with(['p' => 1])) ?>">«</a>
                    </li>

                    <!-- Prev -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= h(url_with(['p' => max(1, $page - 1)])) ?>">←</a>
                    </li>

                    <?php foreach ($items as $it): ?>
                        <?php if ($it === '…'): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php else: ?>
                            <li class="page-item <?= ((int)$it === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= h(url_with(['p' => (int)$it])) ?>"><?= (int)$it ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Next -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= h(url_with(['p' => min($totalPages, $page + 1)])) ?>">→</a>
                    </li>

                    <!-- Last -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= h(url_with(['p' => $totalPages])) ?>">»</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    </div>

    <!-- Modais -->
    <?php foreach ($logs as $l): ?>
        <?php $pid = (int)($l['id'] ?? 0); ?>
        <div class="modal fade" id="auditModal<?= $pid ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Log #<?= $pid ?> — <?= h($l['message'] ?? '') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <div class="small text-muted">Action / Entity</div>
                            <div><code><?= h($l['action'] ?? '') ?></code> / <code><?= h($l['entity'] ?? '') ?></code></div>
                        </div>

                        <div class="mb-3">
                            <div class="small text-muted">Status</div>
                            <?php if ((int)($l['success'] ?? 1) === 1): ?>
                                <span class="badge bg-success">OK</span>
                            <?php else: ?>
                                <span class="badge bg-danger">FALHA</span>
                            <?php endif; ?>
                            <?php if (!empty($l['event_code'])): ?>
                                <span class="ms-2 badge bg-secondary"><?= h($l['event_code']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($l['before_json'])): ?>
                            <h6>Before</h6>
                            <pre class="bg-light p-2 rounded small" style="white-space:pre-wrap;"><?= h(pretty_json($l['before_json'])) ?></pre>
                        <?php endif; ?>

                        <?php if (!empty($l['after_json'])): ?>
                            <h6>After</h6>
                            <pre class="bg-light p-2 rounded small" style="white-space:pre-wrap;"><?= h(pretty_json($l['after_json'])) ?></pre>
                        <?php endif; ?>

                        <?php if (!empty($l['payload_json'])): ?>
                            <h6>Payload</h6>
                            <pre class="bg-light p-2 rounded small" style="white-space:pre-wrap;"><?= h(pretty_json($l['payload_json'])) ?></pre>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Fix warning de foco (aria-hidden) -->
    <script>
        (() => {
            let lastFocused = null;

            document.addEventListener('show.bs.modal', (e) => {
                lastFocused = e.relatedTarget || document.activeElement;
            }, true);

            document.addEventListener('hide.bs.modal', () => {
                const a = document.activeElement;
                if (a && typeof a.blur === 'function') a.blur();
            }, true);

            document.addEventListener('hidden.bs.modal', () => {
                if (lastFocused && typeof lastFocused.focus === 'function') {
                    lastFocused.focus();
                } else {
                    document.body.setAttribute('tabindex', '-1');
                    document.body.focus();
                    document.body.removeAttribute('tabindex');
                }
                lastFocused = null;
            }, true);
        })();
    </script>

    <script src="assets/js/theme.js" defer></script>
</body>

</html>