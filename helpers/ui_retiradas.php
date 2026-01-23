<?php

declare(strict_types=1);

function retirada_status_info(array $r): array
{
    // ✅ Balanço já realizado (prioridade máxima)
    if (!empty($r['balanco_feito'])) {
        return [
            'classe' => 'status-finalizado', // ou crie status-balanco-feito se quiser
            'texto'  => '✅ Balanço feito',
        ];
    }

    // 🔴 Precisa de estoque
    if (!empty($r['sem_estoque'])) {
        return [
            'classe' => 'status-balanco',
            'texto'  => '🔴 Precisa de estoque',
        ];
    }

    // 🟡 Precisa de balanço
    if (!empty($r['precisa_balanco'])) {
        return [
            'classe' => 'status-balanco',
            'texto'  => '🟡 Precisa de balanço',
        ];
    }

    // ✅ Finalizado normal
    if (($r['status'] ?? '') === 'finalizado') {
        return [
            'classe' => 'status-finalizado',
            'texto'  => '✅ Finalizado',
        ];
    }

    // ⏳ Pendente
    return [
        'classe' => 'status-pedido',
        'texto'  => '⏳ Pendente',
    ];
}

function card_class(string $atual, string $meu): string
{
    return $atual === $meu ? 'border border-2 border-primary shadow-sm' : 'shadow-sm';
}
// Gera URL com query string baseada em $base + $currentGet + $override
// - $base: URL base (pode ter query string própria)
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
