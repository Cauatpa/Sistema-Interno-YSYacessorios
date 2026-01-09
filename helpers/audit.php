<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Registra uma ação no audit_logs.
 */
function audit_log(PDO $pdo, string $action, string $entity, ?int $entityId = null, array $payload = []): void
{
    // garante sessão para pegar usuário (se houver)
    $u = auth_user();
    $userId = $u ? (int)($u['id'] ?? 0) : null;
    if ($userId === 0) $userId = null;

    // IP (com fallback seguro)
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (is_string($ip)) {
        $ip = trim($ip);
        if ($ip === '') $ip = null;
        if (strlen($ip) > 45) $ip = substr($ip, 0, 45);
    } else {
        $ip = null;
    }

    // payload em JSON (sem estourar)
    $payloadJson = null;
    if (!empty($payload)) {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) $payloadJson = $json;
    }

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity, entity_id, payload_json, ip, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $action,
        $entity,
        $entityId,
        $payloadJson,
        $ip
    ]);
}
