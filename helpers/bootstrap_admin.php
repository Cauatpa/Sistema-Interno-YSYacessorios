<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

function bootstrap_initial_admin(PDO $pdo): void
{
    // 1) Já rodou antes?
    $chk = $pdo->prepare("SELECT value FROM app_settings WHERE `key`='init_admin_done' LIMIT 1");
    $chk->execute();
    $done = $chk->fetchColumn();
    if ((string)$done === '1') return;

    // 2) Se já tem usuário, não cria
    $countUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($countUsers > 0) {
        // marca como done para nunca tentar (opcional; eu recomendo)
        $ins = $pdo->prepare("INSERT INTO app_settings (`key`,`value`) VALUES ('init_admin_done','1')
                              ON DUPLICATE KEY UPDATE value='1'");
        $ins->execute();
        return;
    }

    // 3) Lê ENV
    $nome    = env('INIT_ADMIN_NAME');
    $usuario = env('INIT_ADMIN_USER');
    $senha   = env('INIT_ADMIN_PASS');

    if (!$nome || !$usuario || !$senha) {
        // não faz nada se não estiver configurado
        return;
    }

    // 4) Cria admin
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    // Ajuste os nomes das colunas conforme sua tabela users
    $stmt = $pdo->prepare("
        INSERT INTO users (nome, usuario, senha_hash, role, created_at)
        VALUES (?, ?, ?, 'admin', NOW())
    ");
    $stmt->execute([$nome, $usuario, $hash]);

    // 5) Marca como feito
    $ins = $pdo->prepare("INSERT INTO app_settings (`key`,`value`) VALUES ('init_admin_done','1')
                          ON DUPLICATE KEY UPDATE value='1'");
    $ins->execute();
}
