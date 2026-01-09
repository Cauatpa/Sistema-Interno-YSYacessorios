<?php
// helpers/auth.php

declare(strict_types=1);

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/user_password.php';

function auth_session_start(): void
{
    csrf_session_start();
}

function auth_user(): ?array
{
    auth_session_start();
    return $_SESSION['user'] ?? null;
}

function auth_require_login(): array
{
    $u = auth_user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function auth_has_role(string $role): bool
{
    $u = auth_user();
    if (!$u) return false;

    $map = [
        'leitura'   => 1,
        'operador'  => 2,
        'admin'     => 3,
        // compat se tiver usado "visualizador" em algum lugar
        'visualizador' => 1,
    ];

    $userLevel = $map[$u['role'] ?? 'leitura'] ?? 1;
    $needLevel = $map[$role] ?? 3;

    return $userLevel >= $needLevel;
}

function auth_require_role(string $role): void
{
    if (!auth_has_role($role)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}

require_once __DIR__ . '/user_password.php';

function auth_login(PDO $pdo, string $usuario, string $senha): bool
{
    auth_session_start();

    $passCol = users_password_hash_field_select($pdo);

    $stmt = $pdo->prepare("SELECT id, nome, usuario, {$passCol} AS senha_db, role, ativo FROM users WHERE usuario = ? LIMIT 1");
    $stmt->execute([$usuario]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u || (int)$u['ativo'] !== 1) return false;

    if (!password_verify($senha, (string)$u['senha_db'])) return false;

    $_SESSION['user'] = [
        'id' => (int)$u['id'],
        'nome' => $u['nome'],
        'usuario' => $u['usuario'],
        'role' => $u['role'],
    ];

    session_regenerate_id(true);
    return true;
}

function auth_logout(): void
{
    auth_session_start();
    unset($_SESSION['user']);
}
