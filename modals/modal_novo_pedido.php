<div class="modal fade" id="modalNovoPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form id="formNovoPedido" action="actions/novo_pedido.php" method="POST" autocomplete="off">
                <?php require_once __DIR__ . '/../helpers/csrf.php'; ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('novo_pedido')) ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl ?? $_SERVER['REQUEST_URI'] ?? 'index.php') ?>">



                <!-- ✅ ÚNICA fonte da verdade -->
                <input type="hidden" name="next" id="novoPedidoNext" value="0">

                <div class="modal-header">
                    <h5 class="modal-title">📦 Novo Pedido de Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">

                    <!-- ✅ Produto com autocomplete -->
                    <div class="mb-3 position-relative">
                        <label class="form-label">Produto</label>
                        <input
                            type="text"
                            name="produto"
                            id="inpProduto"
                            class="form-control"
                            placeholder="Ex: Anel Coração"
                            required
                            autocomplete="off"
                            list="listaProdutos">

                        <datalist id="listaProdutos">
                            <?php foreach (($produtosSugestoes ?? []) as $p): ?>
                                <option value="<?= htmlspecialchars((string)$p) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <div id="produtoSugestoes"
                            class="list-group position-absolute w-100"
                            style="z-index: 2000; display:none; max-height: 220px; overflow:auto;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select" required>
                            <option value="">Selecione</option>
                            <option value="prata">Prata</option>
                            <option value="ouro">Ouro</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantidade solicitada (peças)</label>
                        <input
                            type="number"
                            name="quantidade_solicitada"
                            class="form-control"
                            min="1"
                            value="1"
                            required>
                        <div class="form-text">Quantas peças a pessoa pediu.</div>
                    </div>

                    <div class="mb-2 position-relative">
                        <label class="form-label">Solicitante</label>
                        <input
                            type="text"
                            name="solicitante"
                            id="inpSolicitante"
                            class="form-control"
                            placeholder="Ex: Josi"
                            required
                            autocomplete="off"
                            list="listaSolicitantes">

                        <!-- datalist (fallback nativo) -->
                        <datalist id="listaSolicitantes">
                            <?php foreach (($solicitantesSugestoes ?? []) as $nome): ?>
                                <option value="<?= htmlspecialchars((string)$nome) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <!-- sugestão “esperta” p/ múltiplos nomes -->
                        <div id="solicitanteSugestoes"
                            class="list-group position-absolute w-100"
                            style="z-index: 2000; display:none; max-height: 220px; overflow:auto;">
                        </div>

                        <div class="form-text">
                            Dica: para pedidos juntos, separe por vírgula. Ex: <strong>Josi, Iza</strong>
                        </div>
                    </div>

                    <div class="alert alert-light border py-2 small mb-0">
                        Atalhos: <strong>Enter</strong> salva • <strong>Shift + Enter</strong> salva e abre o próximo
                    </div>
                </div>

                <div class="modal-footer d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm flex-fill" id="btnNovoSalvar">
                        💾 Salvar
                    </button>

                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="btnNovoProximo">
                        → Próximo
                    </button>
                </div>
            </form>

            <script>
                // Sugestão p/ múltiplos nomes: completa SOMENTE o último nome após a última vírgula.
                (() => {
                    const modal = document.getElementById("modalNovoPedido");
                    if (!modal) return;

                    const input = modal.querySelector("#inpSolicitante");
                    const box = modal.querySelector("#solicitanteSugestoes");
                    const datalist = modal.querySelector("#listaSolicitantes");
                    if (!input || !box || !datalist) return;

                    const names = Array.from(datalist.querySelectorAll("option"))
                        .map(o => (o.value || "").trim())
                        .filter(Boolean);

                    const getLastToken = (v) => {
                        const parts = v.split(",");
                        const last = (parts[parts.length - 1] || "").trim();
                        return last;
                    };

                    const setLastToken = (v, chosen) => {
                        const parts = v.split(",");
                        parts[parts.length - 1] = " " + chosen;
                        return parts.map(p => p.trim()).join(", ");
                    };

                    const hide = () => {
                        box.style.display = "none";
                        box.innerHTML = "";
                    };

                    const render = (items) => {
                        box.innerHTML = "";
                        items.slice(0, 8).forEach((n) => {
                            const btn = document.createElement("button");
                            btn.type = "button";
                            btn.className = "list-group-item list-group-item-action";
                            btn.textContent = n;
                            btn.addEventListener("click", () => {
                                input.value = setLastToken(input.value, n);
                                hide();
                                input.focus();
                            });
                            box.appendChild(btn);
                        });
                        box.style.display = items.length ? "block" : "none";
                    };

                    input.addEventListener("input", () => {
                        const token = getLastToken(input.value);
                        if (!token || token.length < 1) {
                            hide();
                            return;
                        }

                        const q = token.toLowerCase();
                        const matches = names.filter(n => n.toLowerCase().startsWith(q));
                        render(matches);
                    });

                    document.addEventListener("click", (e) => {
                        if (e.target === input || box.contains(e.target)) return;
                        hide();
                    });

                    input.addEventListener("keydown", (e) => {
                        if (e.key === "Escape") hide();
                    });
                })();
            </script>

            <script>
                // Sugestões para Produto (1 nome só)
                (() => {
                    const modal = document.getElementById("modalNovoPedido");
                    if (!modal) return;

                    const input = modal.querySelector("#inpProduto");
                    const box = modal.querySelector("#produtoSugestoes");
                    const datalist = modal.querySelector("#listaProdutos");
                    if (!input || !box || !datalist) return;

                    const items = Array.from(datalist.querySelectorAll("option"))
                        .map(o => (o.value || "").trim())
                        .filter(Boolean);

                    const hide = () => {
                        box.style.display = "none";
                        box.innerHTML = "";
                    };

                    const render = (list) => {
                        box.innerHTML = "";
                        list.slice(0, 10).forEach((txt) => {
                            const btn = document.createElement("button");
                            btn.type = "button";
                            btn.className = "list-group-item list-group-item-action";
                            btn.textContent = txt;
                            btn.addEventListener("click", () => {
                                input.value = txt;
                                hide();
                                input.focus();
                            });
                            box.appendChild(btn);
                        });
                        box.style.display = list.length ? "block" : "none";
                    };

                    input.addEventListener("input", () => {
                        const q = (input.value || "").trim().toLowerCase();
                        if (!q) {
                            hide();
                            return;
                        }

                        const matches = items.filter(n => n.toLowerCase().startsWith(q));
                        render(matches);
                    });

                    document.addEventListener("click", (e) => {
                        if (e.target === input || box.contains(e.target)) return;
                        hide();
                    });

                    input.addEventListener("keydown", (e) => {
                        if (e.key === "Escape") hide();
                    });
                })();
            </script>

        </div>
    </div>
</div>