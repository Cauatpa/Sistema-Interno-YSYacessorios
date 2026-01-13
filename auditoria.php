<?php
require 'config/database.php';

require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/csrf.php';

auth_session_start();
auth_require_role('admin');

$limit = 5;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$q = trim((string)($_GET['q'] ?? '')); // busca simples
$action = trim((string)($_GET['action'] ?? ''));
$entity = trim((string)($_GET['entity'] ?? ''));

$where = " WHERE 1=1 ";
$params = [];

// busca por usuário/ação/entidade
if ($q !== '') {
    $where .= " AND (COALESCE(u.nome,'') LIKE ? OR COALESCE(u.usuario,'') LIKE ? OR a.action LIKE ? OR a.entity LIKE ?) ";
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
    LEFT JOIN users u ON u.id = a.user_id
    $where
");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

// lista
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

function h($v): string
{
    return htmlspecialchars((string)$v);
}

function payload_array(?string $json): array
{
    if (!$json) return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function resumo_log(array $l): string
{
    $action = (string)($l['action'] ?? '');
    $entity = (string)($l['entity'] ?? '');
    $entityId = (string)($l['entity_id'] ?? '—');

    $p = payload_array($l['payload_json'] ?? null);

    // se existe message, usa ela (normalmente é o melhor resumo)
    if (!empty($p['message']) && is_string($p['message'])) {
        return $p['message'];
    }

    // tenta ler dados padrão
    $comp = $p['payload']['competencia'] ?? ($p['competencia'] ?? '');

    if ($entity === 'retirada') {
        if ($action === 'create') {
            $prod = $p['payload']['produto'] ?? ($p['after']['produto'] ?? '');
            $qtd  = $p['payload']['quantidade_solicitada'] ?? ($p['after']['quantidade_solicitada'] ?? '');
            $tipo = $p['payload']['tipo'] ?? ($p['after']['tipo'] ?? '');
            $sol  = $p['payload']['solicitante'] ?? ($p['after']['solicitante'] ?? '');
            $txt = "Criou retirada #{$entityId}";
            if ($prod !== '') $txt .= " | {$prod}";
            if ($qtd !== '') $txt .= " | qtd {$qtd}";
            if ($tipo !== '') $txt .= " | {$tipo}";
            if ($sol !== '')  $txt .= " | {$sol}";
            if ($comp !== '') $txt .= " | {$comp}";
            return $txt;
        }

        if ($action === 'finalize' || $action === 'finalizado' || $action === 'finish') {
            $prod = $p['payload']['produto'] ?? ($p['after']['produto'] ?? '');
            $qtdR = $p['payload']['quantidade_retirada'] ?? ($p['after']['quantidade_retirada'] ?? '');
            $resp = $p['payload']['responsavel_estoque'] ?? ($p['after']['responsavel_estoque'] ?? '');
            $txt = "Finalizou retirada #{$entityId}";
            if ($prod !== '') $txt .= " | {$prod}";
            if ($qtdR !== '') $txt .= " | retirado {$qtdR}";
            if ($resp !== '') $txt .= " | {$resp}";
            if ($comp !== '') $txt .= " | {$comp}";
            return $txt;
        }

        if ($action === 'delete') {
            $prod = $p['before']['produto'] ?? ($p['payload']['produto'] ?? '');
            $txt = "Excluiu retirada #{$entityId}";
            if ($prod !== '') $txt .= " | {$prod}";
            if ($comp !== '') $txt .= " | {$comp}";
            return $txt;
        }

        if ($action === 'edit' || $action === 'update') {
            $fields = [];
            if (!empty($p['changed']) && is_array($p['changed'])) {
                $fields = array_keys($p['changed']);
                $fields = array_values(array_diff($fields, ['updated_at', 'request_id', 'user_agent']));
            }
            $fieldsTxt = $fields ? ('campos: ' . implode(', ', array_slice($fields, 0, 5))) : 'editou';
            $txt = "Editou retirada #{$entityId} | {$fieldsTxt}";
            if ($comp !== '') $txt .= " | {$comp}";
            return $txt;
        }
    }

    if ($entity === 'fechamento' || $entity === 'mes') {
        if ($action === 'close_month') return "Fechou mês {$comp}";
        if ($action === 'reopen_month') return "Reabriu mês {$comp}";
    }

    if ($entity === 'user') {
        if ($action === 'reset_password') return "Resetou senha de usuário #{$entityId}";
        if ($action === 'change_password') return "Alterou própria senha";
    }

    return trim("{$action} {$entity} #{$entityId}");
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
            <input type="hidden" name="p" value="1"><!-- reset page ao filtrar -->
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label mb-1">Buscar</label>
                    <input name="q" class="form-control" value="<?= h($q) ?>" placeholder="nome, usuário, action, entity...">
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Action</label>
                    <select name="action" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (['create', 'finalize', 'edit', 'update', 'delete', 'close_month', 'reopen_month', 'reset_password', 'change_password'] as $a): ?>
                            <option value="<?= h($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= h($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label mb-1">Entity</label>
                    <select name="entity" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (['retirada', 'fechamento', 'mes', 'user'] as $e): ?>
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
                        <th>Resumo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <?php
                        $pid = (int)($l['id'] ?? 0);
                        $payloadArr = payload_array($l['payload_json'] ?? null);
                        ?>
                        <tr>
                            <td><?= $pid ?></td>
                            <td><?= h($l['created_at']) ?></td>
                            <td>
                                <?= h($l['nome'] ?? '—') ?>
                                <br><small class="text-muted">@<?= h($l['usuario'] ?? '—') ?></small>
                            </td>
                            <td><code><?= h($l['action']) ?></code></td>
                            <td><code><?= h($l['entity']) ?></code></td>
                            <td><?= h($l['entity_id'] ?? '—') ?></td>
                            <td><?= h($l['ip'] ?? '—') ?></td>
                            <td class="text-start" style="max-width:520px;">
                                <div class="small"><?= h(resumo_log($l)) ?></div>

                                <?php if (!empty($l['payload_json'])): ?>
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm mt-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#auditModal<?= $pid ?>">
                                        Detalhes
                                    </button>
                                <?php endif; ?>
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

        <!-- Paginação -->
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

    <!-- Modais de detalhes -->
    <?php foreach ($logs as $l): ?>
        <?php if (empty($l['payload_json'])) continue; ?>
        <?php
        $pid = (int)($l['id'] ?? 0);
        $p = payload_array($l['payload_json']);
        ?>
        <div class="modal fade" id="auditModal<?= $pid ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalhes do Log #<?= $pid ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">

                        <?php if (!empty($p['message']) && is_string($p['message'])): ?>
                            <div class="alert alert-light border">
                                <strong>Mensagem:</strong> <?= h($p['message']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($p['changed']) && is_array($p['changed'])): ?>
                            <h6>Alterações</h6>
                            <ul class="small">
                                <?php foreach ($p['changed'] as $field => $chg): ?>
                                    <?php
                                    $from = $chg['from'] ?? null;
                                    $to   = $chg['to'] ?? null;

                                    $fromTxt = (is_scalar($from) || $from === null) ? (string)($from ?? 'null') : json_encode($from, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    $toTxt   = (is_scalar($to) || $to === null) ? (string)($to ?? 'null') : json_encode($to, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                    ?>
                                    <li>
                                        <strong><?= h((string)$field) ?>:</strong>
                                        <?= h($fromTxt) ?> → <?= h($toTxt) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                        <?php endif; ?>

                        <h6>Payload (JSON)</h6>
                        <pre class="bg-light p-2 rounded small" style="white-space:pre-wrap;"><?= h(json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>

                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (() => {
            let lastFocused = null;

            // 1) Antes de abrir o modal, guardamos quem estava com foco
            document.addEventListener('show.bs.modal', (e) => {
                // O "relatedTarget" costuma ser o botão que abriu o modal
                lastFocused = e.relatedTarget || document.activeElement;
            }, true);

            // 2) No momento que começa a fechar, remove o foco de dentro do modal (evita o warning)
            document.addEventListener('hide.bs.modal', () => {
                const a = document.activeElement;
                if (a && typeof a.blur === 'function') a.blur();
            }, true);

            // 3) Depois de fechar, devolve o foco pro elemento anterior (ou fallback)
            document.addEventListener('hidden.bs.modal', () => {
                if (lastFocused && typeof lastFocused.focus === 'function') {
                    lastFocused.focus();
                } else {
                    // fallback seguro: joga o foco no body
                    document.body.setAttribute('tabindex', '-1');
                    document.body.focus();
                    document.body.removeAttribute('tabindex');
                }
                lastFocused = null;
            }, true);
        })();
    </script>
</body>

</html>