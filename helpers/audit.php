<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Auditoria estruturada e legível:
 * - message: resumo curto (pra ler rápido)
 * - before_json / after_json: contexto de mudança
 * - payload_json: detalhes extras (opcional e compacto)
 */
function audit_log(
    PDO $pdo,
    string $action,
    string $entity,
    ?int $entityId = null,
    array $payload = [],
    ?array $before = null,
    ?array $after = null,
    bool $success = true,
    ?string $eventCode = null,
    ?string $message = null
): void {
    $u = auth_user();
    $userId = $u ? (int)($u['id'] ?? 0) : null;
    if ($userId === 0) $userId = null;

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if (is_string($ip)) {
        $ip = trim($ip);
        if ($ip === '') $ip = null;
        if (strlen($ip) > 45) $ip = substr($ip, 0, 45);
    } else {
        $ip = null;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    if (is_string($ua)) {
        $ua = trim($ua);
        if ($ua === '') $ua = null;
        if (strlen($ua) > 255) $ua = substr($ua, 0, 255);
    } else {
        $ua = null;
    }

    // Compacta payload (evita lixo gigante)
    $payload = audit_compact($payload);
    $before  = is_array($before) ? audit_compact($before) : null;
    $after   = is_array($after)  ? audit_compact($after)  : null;

    $payloadJson = !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $beforeJson  = $before !== null ? json_encode($before,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $afterJson   = $after  !== null ? json_encode($after,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    // fallback de message
    if ($message === null || trim($message) === '') {
        $message = audit_default_message($action, $entity, $entityId, $success);
    } else {
        $message = trim($message);
        if (strlen($message) > 255) $message = substr($message, 0, 255);
    }

    if ($eventCode !== null) {
        $eventCode = trim($eventCode);
        if ($eventCode === '') $eventCode = null;
        if ($eventCode !== null && strlen($eventCode) > 80) $eventCode = substr($eventCode, 0, 80);
    }

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs
            (user_id, action, entity, entity_id, success, event_code, message,
             payload_json, before_json, after_json, ip, user_agent, created_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $userId,
        $action,
        $entity,
        $entityId,
        $success ? 1 : 0,
        $eventCode,
        $message,
        $payloadJson,
        $beforeJson,
        $afterJson,
        $ip,
        $ua
    ]);
}

/**
 * Remove lixo, limita tamanho e remove campos vazios.
 */
function audit_compact(array $arr): array
{
    $out = [];

    foreach ($arr as $k => $v) {
        // ignora null e string vazia
        if ($v === null) continue;
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') continue;
            if (strlen($v) > 500) $v = substr($v, 0, 500) . '…';
        }

        // arrays muito grandes → corta
        if (is_array($v) && count($v) > 80) {
            $v = array_slice($v, 0, 80, true);
            $v['_truncated'] = true;
        }

        $out[$k] = $v;
    }

    return $out;
}

function audit_default_message(string $action, string $entity, ?int $entityId, bool $success): string
{
    $ok = $success ? 'OK' : 'FALHA';
    $id = $entityId ? "#{$entityId}" : '';
    return "{$ok}: {$action} {$entity}{$id}";
}

/**
 * Helper para calcular before/after apenas do que mudou.
 * Retorna [before, after] só com chaves alteradas.
 */
function audit_diff(array $before, array $after, array $keys): array
{
    $b = [];
    $a = [];
    foreach ($keys as $k) {
        $vb = $before[$k] ?? null;
        $va = $after[$k] ?? null;
        if ($vb !== $va) {
            $b[$k] = $vb;
            $a[$k] = $va;
        }
    }
    return [$b, $a];
}
