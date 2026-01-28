<?php
require_once __DIR__ . '/../helpers/auth.php';

$user = auth_user();

$cards = [];

// Estoque / Retiradas
if (auth_has_role('leitura')) {
    $cards[] = [
        'title'   => 'Estoque / Retiradas',
        'desc'    => 'Pedidos, retiradas e movimentações',
        'href'    => 'index.php?page=retiradas', // ✅ atualizado
        'icon'    => '📦',
        'enabled' => true,
    ];
}

// Lotes
if (auth_has_role('leitura')) {
    $cards[] = [
        'title'   => 'Lotes (Recebimento)',
        'desc'    => 'Criação, conferência e controle',
        'href'    => 'index.php?page=lotes',
        'icon'    => '🧾',
        'enabled' => true,
    ];
}

// Relatórios
if (auth_has_role('leitura')) {
    $cards[] = [
        'title'   => 'Relatórios',
        'desc'    => 'Indicadores e análises',
        'href'    => 'pages/relatorios_controller.php',
        'icon'    => '📊',
        'enabled' => true,
    ];
}

// Usuários (admin)
if (auth_has_role('admin')) {
    $cards[] = [
        'title'   => 'Usuários',
        'desc'    => 'Gerenciar acessos e permissões',
        'href'    => 'pages/usuarios.php',
        'icon'    => '👥',
        'enabled' => true,
    ];

    // ✅ Auditoria (admin)
    $cards[] = [
        'title'   => 'Auditoria',
        'desc'    => 'Logs do sistema e ações dos usuários',
        'href'    => 'pages/auditoria.php',
        'icon'    => '🕵️',
        'enabled' => true,
    ];
}

// Atendimento (futuro)
if (auth_has_role('operador')) {
    $cards[] = [
        'title'   => 'Atendimento',
        'desc'    => 'Devoluções, reembolsos e rastreios',
        'href'    => '#',
        'icon'    => '💬',
        'enabled' => false,
    ];
}
