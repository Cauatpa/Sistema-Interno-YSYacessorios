<?php
require __DIR__ . '/modal_novo_pedido.php';

foreach ($retiradas as $r) {
    if ($r['status'] !== 'finalizado') {
        require __DIR__ . '/modal_finalizar_pedido.php';
    }

    require __DIR__ . '/modal_excluir_pedido.php';
}
