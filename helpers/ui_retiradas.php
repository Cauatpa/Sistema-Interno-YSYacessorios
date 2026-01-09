<?php

declare(strict_types=1);

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
 * - override[k] = null remove o parâmetro
 * - remove automaticamente toast/highlight_id (para não "grudar" nos links)
 * - remove parâmetros vazios ('') para evitar reset/confusão em filtros
 */
function url_com_query(string $base, array $currentGet, array $override = []): string
{
    // Se $base já tiver query (?a=b), separa
    $baseParts = explode('?', $base, 2);
    $basePath = $baseParts[0];
    $baseQuery = $baseParts[1] ?? '';

    $qBase = [];
    if ($baseQuery !== '') {
        parse_str($baseQuery, $qBase);
        if (!is_array($qBase)) $qBase = [];
    }

    // Começa com baseQuery + currentGet
    $q = array_merge($qBase, $currentGet);

    // Remove parâmetros que não devem persistir nos links
    unset($q['toast'], $q['highlight_id']);

    // Aplica override
    foreach ($override as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }

    // Limpa valores vazios (ex: data_ini='', data_fim='')
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }

    // Se não tiver query, retorna só o basePath
    if (empty($q)) {
        return $basePath;
    }

    return $basePath . '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
}
