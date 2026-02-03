<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/user_password.php';

function bootstrap_initial_admin(PDO $pdo): void
{
    bootstrap_app();

    try {
        $pdo->query('SELECT 1 FROM users LIMIT 1');
    } catch (Throwable $e) {
        return;
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND ativo = 1");
    $hasAdmin = (int)$stmt->fetchColumn() > 0;
    if ($hasAdmin) {
        return;
    }

    $usuario = getenv('INITIAL_ADMIN_USER') ?: '';
    $senha = getenv('INITIAL_ADMIN_PASS') ?: '';
    $nome = getenv('INITIAL_ADMIN_NAME') ?: 'Administrador';

    $usuario = trim(mb_strtolower($usuario));
    $senha = trim($senha);

    if ($usuario === '' || $senha === '') {
        return;
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE usuario = ? LIMIT 1');
    $check->execute([$usuario]);
    if ($check->fetchColumn()) {
        return;
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('
        INSERT INTO users (nome, usuario, senha_hash, role, ativo, created_at)
        VALUES (?, ?, ?, "admin", 1, NOW())
    ');
    $stmt->execute([$nome, $usuario, $hash]);
}
