<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Remove campos sensíveis do payload.
 */
function audit_sanitize(array $data): array
{
    $deny = ['senha', 'password', 'password_hash', 'senha_hash', 'csrf_token', 'token'];

    $out = [];
    foreach ($data as $k => $v) {
        $key = (string)$k;

        if (in_array($key, $deny, true)) {
            continue;
        }

        if (is_array($v)) {
            $out[$key] = audit_sanitize($v);
        } else {
            $out[$key] = $v;
        }
    }
    return $out;
}

/**
 * Calcula diff simples {campo: {from,to}} entre before e after.
 */
function audit_diff(?array $before, ?array $after): array
{
    $before = $before ?? [];
    $after  = $after ?? [];

    $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
    $changed = [];

    foreach ($keys as $k) {
        $b = $before[$k] ?? null;
        $a = $after[$k]  ?? null;

        // compara de forma segura
        if ($b !== $a) {
            $changed[$k] = ['from' => $b, 'to' => $a];
        }
    }

    return $changed;
}

/**
 * Gera um "request_id" simples (pra juntar ações do mesmo request).
 */
function audit_request_id(): string
{
    // 16 bytes => 32 hex chars
    return bin2hex(random_bytes(16));
}

/**
 * Registra uma ação no audit_logs.
 *
 * Padrão recomendado:
 * action: create|update|delete|finalize|close_month|reopen_month|login|logout|password_change|password_reset
 * entity: retirada|fechamento|user|auth
 */
function audit_log(
    PDO $pdo,
    string $action,
    string $entity,
    ?int $entityId = null,
    array $payload = [],
    ?array $before = null,
    ?array $after  = null,
    bool $success = true,
    ?string $errorCode = null,
    ?string $errorMessage = null,
    ?string $message = null
): void {
    // usuário logado (se houver)
    $u = auth_user();
    $userId = $u ? (int)($u['id'] ?? 0) : 0;
    $userId = $userId > 0 ? $userId : null;

    // request info
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ip = is_string($ip) ? trim($ip) : null;
    if ($ip === '') $ip = null;
    if (is_string($ip) && strlen($ip) > 45) $ip = substr($ip, 0, 45);

    $path = (string)($_SERVER['REQUEST_URI'] ?? '');
    $method = (string)($_SERVER['REQUEST_METHOD'] ?? '');
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

    // monta pacote final
    $before = $before ? audit_sanitize($before) : null;
    $after  = $after  ? audit_sanitize($after)  : null;

    $basePayload = audit_sanitize($payload);
    $changed = ($before !== null || $after !== null) ? audit_diff($before, $after) : [];

    $pack = [
        'request_id' => audit_request_id(),
        'success' => $success,
        'error_code' => $errorCode,
        'error_message' => $errorMessage,
        'path' => $path,
        'method' => $method,
        'user_agent' => $ua,
        'message' => $message,
        'payload' => $basePayload,
        'before' => $before,
        'after' => $after,
        'changed' => $changed,
    ];

    $json = json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity, entity_id, payload_json, ip, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $action,
        $entity,
        $entityId,
        ($json !== false ? $json : null),
        $ip,
    ]);
}
