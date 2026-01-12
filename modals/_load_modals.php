<?php
require __DIR__ . '/modal_novo_pedido.php';

require __DIR__ . '/modal_minha_senha.php';

foreach ($retiradas as $r) {
    if (($r['status'] ?? '') !== 'finalizado') {
        require __DIR__ . '/modal_finalizar_pedido.php';
    }

    // ✅ excluir só admin
    if ($canAdmin) {
        require __DIR__ . '/modal_excluir_pedido.php';
    }

    // editar só admin (se existir)
    if ($canAdmin) {
        require __DIR__ . '/modal_editar_pedido.php';
    }
}
