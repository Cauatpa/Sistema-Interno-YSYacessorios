<?php
// pages/relatorios_controller.php

require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';

$user = auth_require_login();
csrf_session_start();

$cards = [
    [
        'title' => 'Estoque',
        'desc'  => 'Indicadores, retiradas, balanço e status mensal',
        'icon'  => '📦',
        'href'  => '../relatorio.php',
        'enabled' => true,
    ],
    [
        'title' => 'Lotes de recebimento',
        'desc'  => 'Relatório do controle de lotes (em breve)',
        'icon'  => '🚚',
        'href'  => '#',
        'enabled' => false,
    ],
];

require __DIR__ . '/relatorios_view.php';
