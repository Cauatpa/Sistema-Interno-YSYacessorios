<?php

function retirada_status_info(array $r): array
{
    if (!empty($r['sem_estoque'])) {
        return ['classe' => 'status-balanco', 'texto' => '🔴 Precisa de estoque'];
    }
    if (!empty($r['precisa_balanco'])) {
        return ['classe' => 'status-balanco', 'texto' => '🟡 Precisa de balanço'];
    }
    if (($r['status'] ?? '') === 'finalizado') {
        return ['classe' => 'status-finalizado', 'texto' => '✅ Finalizado'];
    }
    return ['classe' => 'status-pedido', 'texto' => '⏳ Pendente'];
}

function card_class(string $atual, string $meu): string
{
    return $atual === $meu ? 'border border-2 border-primary shadow-sm' : 'shadow-sm';
}

/**
 * Monta URL preservando parâmetros atuais e alterando apenas os informados.
 * Se override[k] = null, remove o parâmetro da URL.
 */
function url_com_query(string $base, array $currentGet, array $override = []): string
{
    $q = $currentGet;

    foreach ($override as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }

    return $base . '?' . http_build_query($q);
}
