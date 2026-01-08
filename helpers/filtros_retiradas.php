<?php

function normaliza_filtros(array $get): array
{
    $filtro = $get['filtro'] ?? 'todos';
    $permitidos = ['todos', 'pendentes', 'finalizados', 'balanco', 'sem_estoque'];
    if (!in_array($filtro, $permitidos, true)) $filtro = 'todos';

    $busca = trim($get['q'] ?? '');

    $tipo = $get['tipo'] ?? 'todos';
    $tipoPermitidos = ['todos', 'prata', 'ouro'];
    if (!in_array($tipo, $tipoPermitidos, true)) $tipo = 'todos';

    $statusFiltro = $get['status'] ?? 'todos';
    $statusPermitidos = ['todos', 'pendentes', 'finalizados'];
    if (!in_array($statusFiltro, $statusPermitidos, true)) $statusFiltro = 'todos';

    $soBalanco = (int)($get['balanco'] ?? 0);
    $soSemEstoque = (int)($get['sem_estoque'] ?? 0);

    return [
        'filtro' => $filtro,
        'busca' => $busca,
        'tipo' => $tipo,
        'statusFiltro' => $statusFiltro,
        'soBalanco' => $soBalanco,
        'soSemEstoque' => $soSemEstoque,
    ];
}

/**
 * Monta WHERE + params da listagem.
 * Regras:
 * - sempre filtra por competencia
 * - sempre filtra deleted_at IS NULL
 * - balanço NÃO inclui sem_estoque
 */
function montar_where_retiradas(string $competencia, array $f): array
{
    $where = " WHERE competencia = ? AND deleted_at IS NULL ";
    $params = [$competencia];

    // Filtro do dashboard
    if ($f['filtro'] === 'pendentes') {
        $where .= " AND status <> 'finalizado' ";
    } elseif ($f['filtro'] === 'finalizados') {
        $where .= " AND status = 'finalizado' ";
    } elseif ($f['filtro'] === 'balanco') {
        $where .= " AND precisa_balanco = 1 AND sem_estoque = 0 ";
    } elseif ($f['filtro'] === 'sem_estoque') {
        $where .= " AND sem_estoque = 1 ";
    }

    // Busca
    if ($f['busca'] !== '') {
        $where .= " AND (produto LIKE ? OR solicitante LIKE ? OR responsavel_estoque LIKE ?) ";
        $like = "%{$f['busca']}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Tipo
    if ($f['tipo'] !== 'todos') {
        $where .= " AND tipo = ? ";
        $params[] = $f['tipo'];
    }

    // Status dropdown
    if ($f['statusFiltro'] === 'pendentes') {
        $where .= " AND status <> 'finalizado' ";
    } elseif ($f['statusFiltro'] === 'finalizados') {
        $where .= " AND status = 'finalizado' ";
    }

    // Flags
    if ((int)$f['soBalanco'] === 1) {
        $where .= " AND precisa_balanco = 1 AND sem_estoque = 0 ";
    }
    if ((int)$f['soSemEstoque'] === 1) {
        $where .= " AND sem_estoque = 1 ";
    }

    return [$where, $params];
}
