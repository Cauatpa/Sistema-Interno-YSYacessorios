<?php
// modals/_load_modals.php

// Novo Pedido: só para operador/admin
if (!empty($canOperate)) {
    require __DIR__ . '/modal_novo_pedido.php';
}

foreach ($retiradas as $r) {

    // Finalizar: só para operador/admin e somente se não estiver finalizado
    if (!empty($canOperate) && (($r['status'] ?? '') !== 'finalizado')) {
        require __DIR__ . '/modal_finalizar_pedido.php';
    }

    // Excluir: só para admin
    if (!empty($canAdmin)) {
        require __DIR__ . '/modal_excluir_pedido.php';
    }
}
