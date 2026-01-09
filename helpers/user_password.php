<?php
// helpers/user_password.php
declare(strict_types=1);

function users_password_column(PDO $pdo): string
{
    // ✅ Coluna oficial do sistema (fixa)
    return 'senha_hash';
}

function users_password_hash_field_select(PDO $pdo): string
{
    return users_password_column($pdo);
}

function user_set_password(PDO $pdo, int $id, string $plain): bool
{
    $col = users_password_column($pdo);
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET {$col} = ? WHERE id = ?");
    return $stmt->execute([$hash, $id]);
}

function user_verify_password(array $userRow, PDO $pdo, string $plain): bool
{
    $col = users_password_column($pdo);
    $hash = $userRow[$col] ?? '';

    if (!is_string($hash) || $hash === '') return false;
    return password_verify($plain, $hash);
}
