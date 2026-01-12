<?php
require 'config/database.php';

require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/csrf.php';

auth_session_start();
auth_require_role('admin');

$toast = $_GET['toast'] ?? '';
$senhaGerada = $_GET['senha'] ?? '';

$stmt = $pdo->query("
    SELECT id, nome, usuario, role, ativo, created_at
    FROM users
    ORDER BY id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Define classe do badge por role do banco.
 */
function badgeRole(string $role): string
{
    $role = trim($role);

    return match ($role) {
        'admin'        => 'bg-danger',
        'operador'     => 'bg-primary',
        'visualizador' => 'bg-secondary',
        'leitura'      => 'bg-secondary', // compat retro
        default        => 'bg-secondary',
    };
}

/**
 * Label amigável para UI (sem mudar o valor do banco).
 */
function labelRole(string $role): string
{
    $role = trim($role);

    return match ($role) {
        'admin'        => 'admin',
        'operador'     => 'operador',
        'visualizador' => 'leitor',
        'leitura'      => 'leitor', // compat retro
        default        => $role !== '' ? $role : '—', // mostra o que veio pra facilitar debug
    };
}
?>
<!DOCTYPE html>
<html lang="pt-br" data-toast="<?= htmlspecialchars((string)$toast) ?>">

<head>
    <meta charset="UTF-8">
    <title>Usuários - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-3">
    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">👤 Gestão de Usuários</h3>

            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar</a>

                <form method="POST" action="logout.php" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('logout')) ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
                </form>
            </div>
        </div>

        <?php if ($senhaGerada !== ''): ?>
            <div class="alert alert-warning">
                <strong>Senha temporária:</strong> <code><?= htmlspecialchars((string)$senhaGerada) ?></code><br>
                <small class="text-muted">Copie e guarde agora (por segurança, não fica salva em nenhum lugar).</small>
            </div>
        <?php endif; ?>

        <!-- Criar usuário -->
        <div class="card p-3 mb-3">
            <h5 class="mb-3">➕ Criar novo usuário</h5>

            <form method="POST" action="actions/usuarios_criar.php" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('usuarios_criar')) ?>">

                <div class="col-12 col-md-4">
                    <label class="form-label mb-1">Nome</label>
                    <input name="nome" class="form-control" required>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Usuário (login)</label>
                    <input name="usuario" class="form-control" placeholder="ex: caua" required>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label mb-1">Permissão</label>
                    <select name="role" class="form-select" required>
                        <option value="operador">Operador</option>
                        <option value="visualizador">Leitor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Criar</button>
                </div>

                <div class="col-12">
                    <small class="text-muted">O sistema vai gerar uma senha temporária automaticamente.</small>
                </div>
            </form>
        </div>

        <!-- Listagem -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>Permissão</th>
                        <th>Status</th>
                        <th>Criado</th>
                        <th class="text-nowrap">Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $id = (int)($u['id'] ?? 0);
                        $role = (string)($u['role'] ?? '');
                        ?>
                        <tr>
                            <td><?= $id ?></td>
                            <td><?= htmlspecialchars((string)($u['nome'] ?? '')) ?></td>
                            <td><code><?= htmlspecialchars((string)($u['usuario'] ?? '')) ?></code></td>

                            <td>
                                <span class="badge <?= badgeRole($role) ?>">
                                    <?= htmlspecialchars(labelRole($role)) ?>
                                </span>
                            </td>

                            <td>
                                <?php if ((int)($u['ativo'] ?? 0) === 1): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-nowrap"><?= htmlspecialchars((string)($u['created_at'] ?? '')) ?></td>

                            <td class="text-nowrap">
                                <!-- Reset senha (gera senha temporária) -->
                                <form method="POST" action="actions/usuarios_reset_senha.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('usuarios_reset')) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button class="btn btn-outline-warning btn-sm"
                                        onclick="return confirm('Resetar a senha deste usuário?');">
                                        🔁 Reset senha
                                    </button>
                                </form>

                                <!-- Trocar senha (admin define uma senha) -->
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalSenha<?= $id ?>">
                                    🔑 Trocar senha
                                </button>

                                <!-- Ativar/Desativar -->
                                <form method="POST" action="actions/usuarios_toggle.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('usuarios_toggle')) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Alterar status (ativar/desativar)?');">
                                        <?= ((int)($u['ativo'] ?? 0) === 1) ? '⛔ Desativar' : '✅ Ativar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>

    </div>

    <!-- ✅ Modais de trocar senha (um por usuário) -->
    <?php foreach ($users as $u): ?>
        <?php require __DIR__ . '/modals/modal_trocar_senha_usuario.php'; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>