<div class="modal fade" id="modalNovoPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <form id="formNovoPedido" action="/InterYSY/actions/novo_pedido.php" method="POST" autocomplete="off">
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

                    <!-- Lógica de sincronia entre checkboxes de status especiais -->
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>

                        <div class="d-flex gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tipos[]" value="prata" id="tipoPrata">
                                <label class="form-check-label" for="tipoPrata">Prata</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tipos[]" value="ouro" id="tipoOuro">
                                <label class="form-check-label" for="tipoOuro">Ouro</label>
                            </div>
                        </div>

                        <div class="alert alert-light border py-2 small mb-0">
                            Atalhos: <strong>P</strong> Marca Prata • <strong>O</strong> Marca Ouro
                        </div>
                    </div>

                    <!-- quantidade única (quando só 1 tipo marcado) -->
                    <div class="mb-3 d-none" id="qtdUnicaWrap">
                        <label class="form-label">Quantidade solicitada (peças)</label>
                        <input
                            type="number"
                            name="quantidade_solicitada"
                            id="qtdUnica"
                            class="form-control"
                            min="1"
                            value="1">
                        <div class="form-text">Quando marcar só um tipo, preencha uma única quantidade.</div>
                    </div>

                    <!-- quantidades separadas (quando Prata + Ouro) -->
                    <div class="mb-3 d-none" id="qtdDuplaWrap">
                        <label class="form-label mb-2">Quantidades por tipo</label>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Qtd (Prata)</label>
                                <input
                                    type="number"
                                    name="quantidade_prata"
                                    id="qtdPrata"
                                    class="form-control"
                                    min="1"
                                    value="1">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Qtd (Ouro)</label>
                                <input
                                    type="number"
                                    name="quantidade_ouro"
                                    id="qtdOuro"
                                    class="form-control"
                                    min="1"
                                    value="1">
                            </div>
                        </div>

                        <div class="form-text">Quando marcar os dois, informe a quantidade de cada.</div>
                    </div>

                    <!-- ✅ Solicitante com autocomplete e sugestão “esperta” p/ múltiplos nomes -->
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

            <!-- Lógica de sugestão para Solicitante (autocomplete customizado) -->
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

            <!-- Lógica de sugestão para Produto (autocomplete customizado) -->
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

            <!-- Lógica de validação e envio do formulário -->
            <script>
                (() => {
                    const modal = document.getElementById("modalNovoPedido");
                    if (!modal) return;

                    const form = modal.querySelector("#formNovoPedido");
                    const btnSalvar = modal.querySelector("#btnNovoSalvar");
                    const btnProximo = modal.querySelector("#btnNovoProximo");
                    const inpNext = modal.querySelector("#novoPedidoNext");

                    const chkPrata = modal.querySelector("#tipoPrata");
                    const chkOuro = modal.querySelector("#tipoOuro");

                    const wrapUnica = modal.querySelector("#qtdUnicaWrap");
                    const wrapDupla = modal.querySelector("#qtdDuplaWrap");

                    const qtdUnica = modal.querySelector("#qtdUnica");
                    const qtdPrata = modal.querySelector("#qtdPrata");
                    const qtdOuro = modal.querySelector("#qtdOuro");

                    if (!form || !chkPrata || !chkOuro || !wrapUnica || !wrapDupla) return;

                    function refreshQtdUI() {
                        const p = chkPrata.checked;
                        const o = chkOuro.checked;

                        // nenhum marcado -> esconde tudo (e deixa a validação barrar)
                        if (!p && !o) {
                            wrapUnica.classList.add("d-none");
                            wrapDupla.classList.add("d-none");
                            return;
                        }

                        // ambos marcados -> mostra dupla
                        if (p && o) {
                            wrapDupla.classList.remove("d-none");
                            wrapUnica.classList.add("d-none");
                            return;
                        }

                        // somente um -> mostra única
                        wrapUnica.classList.remove("d-none");
                        wrapDupla.classList.add("d-none");
                    }

                    chkPrata.addEventListener("change", refreshQtdUI);
                    chkOuro.addEventListener("change", refreshQtdUI);
                    refreshQtdUI();

                    function validarAntesDeEnviar() {
                        const p = chkPrata.checked;
                        const o = chkOuro.checked;

                        if (!p && !o) {
                            alert("Selecione pelo menos um tipo: Prata e/ou Ouro.");
                            return false;
                        }

                        // ambos -> exige qtd de cada
                        if (p && o) {
                            const qp = parseInt(qtdPrata?.value || "0", 10);
                            const qo = parseInt(qtdOuro?.value || "0", 10);
                            if (qp <= 0 || qo <= 0) {
                                alert("Preencha a quantidade de Prata e de Ouro (maior que 0).");
                                return false;
                            }
                            return true;
                        }

                        // só um -> exige qtd única e mapeia para o campo correto (compatível com o backend novo)
                        const qu = parseInt(qtdUnica?.value || "0", 10);
                        if (qu <= 0) {
                            alert("Preencha a quantidade (maior que 0).");
                            return false;
                        }

                        if (p && qtdPrata) qtdPrata.value = String(qu);
                        if (o && qtdOuro) qtdOuro.value = String(qu);

                        return true;
                    }

                    function submitWith(nextValue) {
                        inpNext.value = nextValue ? "1" : "0";
                        if (!validarAntesDeEnviar()) return;
                        form.submit();
                    }

                    // Botões
                    btnSalvar?.addEventListener("click", () => submitWith(false));
                    btnProximo?.addEventListener("click", () => submitWith(true));

                    // Atalhos: Enter salva / Shift+Enter próximo
                    form.addEventListener("keydown", (e) => {
                        // não intercepta se estiver digitando em textarea (se no futuro tiver)
                        const tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : "";
                        if (tag === "textarea") return;

                        if (e.key === "Enter") {
                            e.preventDefault();
                            submitWith(e.shiftKey);
                        }
                    });
                })();
            </script>

            <!-- Lógica de atalhos de teclado para marcar tipos -->
            <script>
                (() => {
                    const modal = document.getElementById("modalNovoPedido");
                    if (!modal) return;

                    const chkPrata = modal.querySelector("#tipoPrata");
                    const chkOuro = modal.querySelector("#tipoOuro");
                    if (!chkPrata || !chkOuro) return;

                    modal.addEventListener("keydown", (e) => {
                        // 🔒 não interfere enquanto digita em campos de texto
                        const tag = (e.target?.tagName || "").toLowerCase();
                        const type = (e.target?.type || "").toLowerCase();
                        if (tag === "input" && (type === "text" || type === "number")) return;

                        const k = e.key.toLowerCase();

                        if (k === "p") {
                            e.preventDefault();
                            chkPrata.checked = !chkPrata.checked;
                            chkPrata.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                            chkPrata.focus();
                        }

                        if (k === "o") {
                            e.preventDefault();
                            chkOuro.checked = !chkOuro.checked;
                            chkOuro.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                            chkOuro.focus();
                        }
                    });
                })();
            </script>

            <!-- Lógica de sincronia entre checkboxes de status especiais -->
            <script>
                (() => {
                    const modal = document.getElementById("modalNovoPedido");
                    if (!modal) return;

                    const chkSem = modal.querySelector("#chkSemEstoque");
                    const chkBal = modal.querySelector("#chkPrecisaBalanco");
                    const chkFeito = modal.querySelector("#chkBalancoFeito");

                    if (!chkSem || !chkBal) return;

                    function syncStatus() {
                        // 🔴 SEM ESTOQUE domina tudo
                        if (chkSem.checked) {
                            chkBal.checked = false;
                            chkBal.disabled = true;

                            if (chkFeito) {
                                chkFeito.checked = false;
                                chkFeito.disabled = true;
                            }
                            return;
                        }

                        // libera quando NÃO é sem estoque
                        chkBal.disabled = false;
                        if (chkFeito) chkFeito.disabled = false;

                        // 🟡 Precisa balanço não pode coexistir com balanço feito
                        if (chkBal.checked && chkFeito) {
                            chkFeito.checked = false;
                        }
                    }

                    chkSem.addEventListener("change", syncStatus);
                    chkBal.addEventListener("change", syncStatus);
                    chkFeito?.addEventListener("change", syncStatus);

                    // aplica ao abrir modal (edição)
                    syncStatus();
                })();
            </script>

        </div>
    </div>
</div>