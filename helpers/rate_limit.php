<?php

declare(strict_types=1);

/**
 * Rate limit simples para login:
 * - bloqueia por IP + usuario após X falhas numa janela de Y minutos
 * - loga tentativas em login_attempts
 *
 * Tabela esperada:
 *   login_attempts(id PK AI, ip VARCHAR, usuario VARCHAR, success TINYINT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)
 */

function rl_client_ip(): string
{
    // Por padrão (XAMPP/local), REMOTE_ADDR é o certo.
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    // Se um dia estiver atrás de proxy reverso CONTROLADO por você,
    // aí sim você pode usar HTTP_X_FORWARDED_FOR / HTTP_CF_CONNECTING_IP etc.
    // Por enquanto, não confiar nessas headers evita spoof.
    return $ip !== '' ? $ip : '0.0.0.0';
}

/**
 * Normaliza o "usuario" para não ter duplicação por caixa/espaco.
 */
function rl_norm_user(string $usuario): string
{
    $u = mb_strtolower(trim($usuario));
    return $u === '' ? '(vazio)' : $u;
}

/**
 * Limpeza leve (para não crescer infinito).
 * Ex: mantém tentativas por 30 dias.
 *
 * Chame esporadicamente (por chance) para não gerar custo fixo.
 */
function rl_gc(PDO $pdo, int $keepDays = 30): void
{
    // 2% de chance por request de login (leve e suficiente)
    if (random_int(1, 100) > 2) return;

    $stmt = $pdo->prepare("
        DELETE FROM login_attempts
        WHERE created_at < (NOW() - INTERVAL ? DAY)
    ");
    $stmt->execute([$keepDays]);
}

/**
 * Retorna true se estiver bloqueado.
 */
function rl_is_blocked(PDO $pdo, string $ip, string $usuario, int $maxFails = 6, int $windowMinutes = 10): bool
{
    $usuario = rl_norm_user($usuario);

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE ip = ?
          AND usuario = ?
          AND success = 0
          AND created_at >= (NOW() - INTERVAL ? MINUTE)
    ");
    $stmt->execute([$ip, $usuario, $windowMinutes]);
    $fails = (int)$stmt->fetchColumn();

    return $fails >= $maxFails;
}

/**
 * Quantos segundos faltam para liberar.
 * Se não estiver bloqueado, retorna 0.
 *
 * Estratégia:
 * - pega a falha que "atingiu" o limite (a N-ésima falha dentro da janela)
 * - unblock = created_at dessa falha + windowMinutes
 */
function rl_seconds_until_unblock(PDO $pdo, string $ip, string $usuario, int $maxFails = 6, int $windowMinutes = 10): int
{
    $usuario = rl_norm_user($usuario);

    // Busca a falha "limite": a (maxFails)-ésima falha mais recente dentro da janela
    // Para isso: ordena DESC e OFFSET maxFails-1, assim pegamos a falha mais antiga dentre as N últimas falhas
    $stmt = $pdo->prepare("
        SELECT created_at
        FROM login_attempts
        WHERE ip = ?
          AND usuario = ?
          AND success = 0
          AND created_at >= (NOW() - INTERVAL ? MINUTE)
        ORDER BY created_at DESC
        LIMIT 1 OFFSET ?
    ");
    $stmt->execute([$ip, $usuario, $windowMinutes, $maxFails - 1]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['created_at'])) return 0;

    $t = strtotime((string)$row['created_at']);
    if ($t === false) return 0;

    $unblockAt = $t + ($windowMinutes * 60);
    $left = $unblockAt - time();

    return max(0, $left);
}

/**
 * Registra tentativa (sucesso ou falha) e faz GC leve.
 */
function rl_log_attempt(PDO $pdo, string $ip, string $usuario, bool $success): void
{
    $usuario = rl_norm_user($usuario);

    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (ip, usuario, success)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$ip, $usuario, $success ? 1 : 0]);

    // limpeza leve para evitar tabela infinita
    rl_gc($pdo, 30);
}
