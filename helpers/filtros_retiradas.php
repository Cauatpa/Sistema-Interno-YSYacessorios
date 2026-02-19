<?php

declare(strict_types=1);

/**
 * Normaliza data no formato YYYY-MM-DD (input type="date").
 */
function normaliza_data_ymd($v): string
{
    $v = trim((string)$v);
    if ($v === '') return '';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return '';

    [$y, $m, $d] = array_map('intval', explode('-', $v));
    if (!checkdate($m, $d, $y)) return '';

    return $v;
}

function normaliza_filtros(array $get): array
{
    $filtro = (string)($get['filtro'] ?? 'todos');
    $permitidos = ['todos', 'pendentes', 'finalizados', 'balanco', 'sem_estoque'];
    if (!in_array($filtro, $permitidos, true)) $filtro = 'todos';

    $busca = trim((string)($get['q'] ?? ''));

    $tipo = (string)($get['tipo'] ?? 'todos');
    $tipoPermitidos = ['todos', 'prata', 'ouro'];
    if (!in_array($tipo, $tipoPermitidos, true)) $tipo = 'todos';

    // ✅ Status do select (inclui os novos)
    $statusFiltro = (string)($get['status'] ?? 'todos');
    $statusPermitidos = ['todos', 'pendentes', 'finalizados', 'sem_estoque', 'estoque_preenchido', 'balanco_feito'];
    if (!in_array($statusFiltro, $statusPermitidos, true)) $statusFiltro = 'todos';

    $soBalanco = (int)($get['balanco'] ?? 0);
    $soSemEstoque = (int)($get['sem_estoque'] ?? 0);

    // Datas (YYYY-MM-DD)
    $dataIni = normaliza_data_ymd($get['data_ini'] ?? '');
    $dataFim = normaliza_data_ymd($get['data_fim'] ?? '');

    // Se inverter, troca automaticamente
    if ($dataIni !== '' && $dataFim !== '' && $dataFim < $dataIni) {
        [$dataIni, $dataFim] = [$dataFim, $dataIni];
    }

    return [
        'filtro' => $filtro,
        'busca' => $busca,
        'tipo' => $tipo,
        'statusFiltro' => $statusFiltro,
        'soBalanco' => $soBalanco,
        'soSemEstoque' => $soSemEstoque,
        'dataIni' => $dataIni,
        'dataFim' => $dataFim,
    ];
}

function montar_where_retiradas(string $competencia, array $f): array
{
    $where = " WHERE competencia = ? AND deleted_at IS NULL ";
    $params = [$competencia];

    // Dashboard (cards de cima)
    $dash = (string)($f['filtro'] ?? 'todos');

    if ($dash === 'pendentes') {
        // ✅ pendentes do card NÃO inclui sem_estoque
        $where .= " AND status <> 'finalizado' AND COALESCE(sem_estoque,0) = 0 ";
    } elseif ($dash === 'finalizados') {
        $where .= " AND status = 'finalizado' ";
    } elseif ($dash === 'balanco') {
        $where .= " AND precisa_balanco = 1 AND sem_estoque = 0 AND COALESCE(balanco_feito,0) = 0 ";
    } elseif ($dash === 'sem_estoque') {
        $where .= " AND sem_estoque = 1 ";
    }

    // Busca
    $busca = (string)($f['busca'] ?? '');
    if ($busca !== '') {
        $where .= " AND (produto LIKE ? OR solicitante LIKE ? OR responsavel_estoque LIKE ?) ";
        $like = "%{$busca}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Tipo
    $tipo = (string)($f['tipo'] ?? 'todos');
    if ($tipo !== 'todos') {
        $where .= " AND tipo = ? ";
        $params[] = $tipo;
    }

    // Status dropdown (select Status)
    $statusFiltro = (string)($f['statusFiltro'] ?? 'todos');

    if ($statusFiltro === 'pendentes') {
        $where .= " AND status <> 'finalizado' AND COALESCE(sem_estoque,0) = 0 ";
    } elseif ($statusFiltro === 'finalizados') {
        $where .= " AND status = 'finalizado' ";
    } elseif ($statusFiltro === 'sem_estoque') {
        $where .= " AND COALESCE(sem_estoque,0) = 1 ";
    } elseif ($statusFiltro === 'estoque_preenchido') {
        $where .= " AND COALESCE(estoque_preenchido,0) = 1 ";
    } elseif ($statusFiltro === 'balanco_feito') {
        $where .= " AND COALESCE(balanco_feito,0) = 1 ";
    }

    // Checkboxes
    if ((int)($f['soBalanco'] ?? 0) === 1) {
        $where .= " AND precisa_balanco = 1 AND sem_estoque = 0 AND COALESCE(balanco_feito,0) = 0 ";
    }
    if ((int)($f['soSemEstoque'] ?? 0) === 1) {
        $where .= " AND sem_estoque = 1 ";
    }

    // Datas
    $dataIni = (string)($f['dataIni'] ?? '');
    $dataFim = (string)($f['dataFim'] ?? '');

    if ($dataIni !== '' && $dataFim !== '') {
        $where .= " AND data_pedido >= ? AND data_pedido < DATE_ADD(?, INTERVAL 1 DAY) ";
        $params[] = $dataIni . " 00:00:00";
        $params[] = $dataFim . " 00:00:00";
    } elseif ($dataIni !== '') {
        $where .= " AND data_pedido >= ? ";
        $params[] = $dataIni . " 00:00:00";
    } elseif ($dataFim !== '') {
        $where .= " AND data_pedido < DATE_ADD(?, INTERVAL 1 DAY) ";
        $params[] = $dataFim . " 00:00:00";
    }

    // ✅ garante array posicional certinha (evita HY093)
    $params = array_values($params);

    return [$where, $params];
}
