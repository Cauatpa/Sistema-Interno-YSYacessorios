<?php
// modals/lotes/modal_add_item.php
// Dependências esperadas no include:
// $editMode, $lote, $recebimentoAtualId, $produtos, csrf_token(), $_GET['open_item'] (opcional)

if (empty($editMode)) return;

$openItem = ((int)($_GET['open_item'] ?? 0) === 1);
?>

<div class="modal fade" id="modalAddItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="actions/lote_item_add.php" class="modal-content" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('lote_item_add')) ?>">
            <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">
            <input type="hidden" name="recebimento_id" value="<?= (int)$recebimentoAtualId ?>">
            <input type="hidden" name="next" id="loteItemNext" value="0">

            <div class="modal-header">
                <h5 class="modal-title">➕ Adicionar item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Produto</label>
                        <select id="produtoSelectAdd" name="produto_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-1">Variações</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="vPrata" name="tem_prata" value="1" checked>
                                <label class="form-check-label" for="vPrata">Prata</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="vOuro" name="tem_ouro" value="1">
                                <label class="form-check-label" for="vOuro">Ouro</label>
                            </div>
                        </div>
                        <div class="text-muted small mt-1">
                            Atalhos: <strong>P</strong> Prata • <strong>O</strong> Ouro • <strong>Enter</strong> adiciona • <strong>Shift+Enter</strong> próximo
                        </div>
                    </div>

                    <div class="col-12" id="boxPrata">
                        <div class="card p-2">
                            <div class="fw-bold mb-2">Prata</div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Qtd prevista (Prata)</label>
                                    <input type="number" name="qtd_prevista_prata" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Qtd conferida (Prata)</label>
                                    <input type="number" name="qtd_conferida_prata" class="form-control" placeholder="—">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" id="boxOuro" style="display:none;">
                        <div class="card p-2">
                            <div class="fw-bold mb-2">Ouro</div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Qtd prevista (Ouro)</label>
                                    <input type="number" name="qtd_prevista_ouro" class="form-control" value="0" min="0">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Qtd conferida (Ouro)</label>
                                    <input type="number" name="qtd_conferida_ouro" class="form-control" placeholder="—">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Situação (igual para ambas)</label>
                        <select name="situacao" class="form-select">
                            <option value="ok">OK</option>
                            <option value="faltando">Faltando</option>
                            <option value="a_mais">A mais</option>
                            <option value="banho_trocado">Banho trocado</option>
                            <option value="quebra">Quebra</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Nota (igual para ambas)</label>
                        <input name="nota" class="form-control" placeholder="(opcional)">
                    </div>

                    <div class="alert alert-light border py-2 small mb-0">
                        Atalhos: <strong>Enter</strong> salva • <strong>Shift + Enter</strong> salva e abre o próximo
                    </div>
                </div>
            </div>

            <div class="modal-footer d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success flex-fill" id="btnLoteSalvar">Adicionar</button>
                <button type="button" class="btn btn-outline-primary flex-fill" id="btnLoteProximo">→ Próximo</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const vPrata = document.getElementById('vPrata');
        const vOuro = document.getElementById('vOuro');
        const boxPrata = document.getElementById('boxPrata');
        const boxOuro = document.getElementById('boxOuro');

        const sync = () => {
            if (boxPrata) boxPrata.style.display = vPrata && vPrata.checked ? '' : 'none';
            if (boxOuro) boxOuro.style.display = vOuro && vOuro.checked ? '' : 'none';
        };

        vPrata && vPrata.addEventListener('change', sync);
        vOuro && vOuro.addEventListener('change', sync);
        sync();
    });
</script>

<script>
    (() => {
        const modal = document.getElementById("modalAddItem");
        if (!modal) return;

        const form = modal.querySelector("form");
        const inpNext = modal.querySelector("#loteItemNext");
        const btnSalvar = modal.querySelector("#btnLoteSalvar");
        const btnProximo = modal.querySelector("#btnLoteProximo");

        const chkPrata = modal.querySelector("#vPrata");
        const chkOuro = modal.querySelector("#vOuro");

        if (!form || !inpNext || !btnSalvar || !btnProximo || !chkPrata || !chkOuro) return;

        function submitWith(next) {
            inpNext.value = next ? "1" : "0";
            form.submit();
        }

        btnSalvar.addEventListener("click", () => submitWith(false));
        btnProximo.addEventListener("click", () => submitWith(true));

        modal.addEventListener("keydown", (e) => {
            const tag = (e.target?.tagName || "").toLowerCase();
            const type = (e.target?.type || "").toLowerCase();

            // não interferir enquanto digita
            if (tag === "textarea" || (tag === "input" && (type === "text" || type === "number"))) return;

            const k = (e.key || "").toLowerCase();

            if (k === "enter") {
                e.preventDefault();
                submitWith(e.shiftKey);
                return;
            }

            if (k === "p") {
                e.preventDefault();
                chkPrata.checked = !chkPrata.checked;
                chkPrata.dispatchEvent(new Event("change", {
                    bubbles: true
                }));
                chkPrata.focus();
                return;
            }

            if (k === "o") {
                e.preventDefault();
                chkOuro.checked = !chkOuro.checked;
                chkOuro.dispatchEvent(new Event("change", {
                    bubbles: true
                }));
                chkOuro.focus();
                return;
            }
        });
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const shouldOpen = <?= $openItem ? 'true' : 'false' ?>;
        if (!shouldOpen || !window.bootstrap) return;

        const el = document.getElementById("modalAddItem");
        if (!el) return;

        new bootstrap.Modal(el).show();
    });
</script>