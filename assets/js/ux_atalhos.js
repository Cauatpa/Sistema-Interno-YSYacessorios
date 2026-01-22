(() => {
  // =========================
  // Helpers
  // =========================
  const hasBootstrap = () => !!window.bootstrap?.Modal;

  const openModalById = (modalId) => {
    const modalEl = document.getElementById(modalId);
    if (!modalEl || !hasBootstrap()) return false;
    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
    return true;
  };

  const focusLater = (el) => {
    if (!el) return;
    setTimeout(() => {
      try {
        el.focus();
      } catch (_) {}
    }, 50);
  };

  // ======================================================
  // 0) Lê URL UMA vez e abre o que precisar (sem conflitos)
  //    prioridade: FINALIZAR > NOVO
  // ======================================================
  (() => {
    const url = new URL(window.location.href);

    const openFinalizarId = url.searchParams.get("open_finalizar_id");
    const openNovo = url.searchParams.get("open_novo");
    const keepSolic = url.searchParams.get("keep_solicitante") || "";

    // FINALIZAR
    if (openFinalizarId) {
      url.searchParams.delete("open_finalizar_id");
      window.history.replaceState({}, "", url.toString());
      setTimeout(() => openModalById(`modalFinalizar${openFinalizarId}`), 50);
      return;
    }

    // NOVO PEDIDO
    if (openNovo === "1") {
      url.searchParams.delete("open_novo");
      url.searchParams.delete("keep_solicitante");
      window.history.replaceState({}, "", url.toString());

      const modalEl = document.getElementById("modalNovoPedido");
      if (!modalEl || !hasBootstrap()) return;

      window.bootstrap.Modal.getOrCreateInstance(modalEl).show();

      modalEl.addEventListener(
        "shown.bs.modal",
        () => {
          const inpSolic = modalEl.querySelector('input[name="solicitante"]');
          const inpProd = modalEl.querySelector('input[name="produto"]');
          const inpQtd = modalEl.querySelector(
            'input[name="quantidade_solicitada"]',
          );
          const selTipo = modalEl.querySelector('select[name="tipo"]');

          if (inpSolic && keepSolic) inpSolic.value = keepSolic;

          // limpa pra próxima criação
          if (inpProd) inpProd.value = "";
          if (selTipo) selTipo.value = "";
          if (inpQtd) inpQtd.value = "1";

          focusLater(inpProd);
        },
        { once: true },
      );
    }
  })();

  // ======================================================
  // 1) Foco automático quando abrir qualquer modalFinalizar*
  // ======================================================
  document.addEventListener("shown.bs.modal", (e) => {
    const modalEl = e.target;
    if (!modalEl?.id?.startsWith("modalFinalizar")) return;

    const input = modalEl.querySelector('input[name="quantidade_retirada"]');
    focusLater(input);
  });

  // ======================================================
  // Helper: pega o próximo pendente EXISTENTE NA PÁGINA
  // ======================================================
  const getNextPendingIdInPage = (currentId) => {
    const rows = Array.from(
      document.querySelectorAll('tr[data-retirada-id][data-pendente="1"]'),
    );
    if (!rows.length) return null;

    const idx = rows.findIndex(
      (tr) => String(tr.getAttribute("data-retirada-id")) === String(currentId),
    );

    // próximo abaixo
    for (let i = idx + 1; i < rows.length; i++) {
      const id = rows[i].getAttribute("data-retirada-id");
      if (id && id !== String(currentId)) return id;
    }

    // fallback: primeiro pendente
    const firstId = rows[0].getAttribute("data-retirada-id");
    if (firstId && firstId !== String(currentId)) return firstId;

    return null;
  };

  // ======================================================
  // 2) FINALIZAR: garantir next_target_id ao clicar em "Próximo"
  // ======================================================
  document.addEventListener(
    "click",
    (e) => {
      const btn = e.target?.closest?.(
        '.modal[id^="modalFinalizar"] button[type="submit"][name="next"][value="1"]',
      );
      if (!btn) return;

      const form = btn.closest("form");
      if (!form) return;

      const currentId = form.querySelector('input[name="id"]')?.value;
      if (!currentId) return;

      const nextId = getNextPendingIdInPage(currentId);

      const hiddenNext =
        form.querySelector('input[type="hidden"][name="next"]') ||
        form.querySelector('input[name="next"]');
      const nextTarget = form.querySelector('input[name="next_target_id"]');

      if (hiddenNext) hiddenNext.value = "1";
      if (nextTarget) nextTarget.value = nextId ? String(nextId) : "0";
    },
    true,
  );

  // ======================================================
  // 3) FINALIZAR: Enter / Shift+Enter
  //    Shift+Enter => Próximo
  //    Enter       => Finalizar
  // ======================================================
  document.addEventListener(
    "keydown",
    (e) => {
      if (e.key !== "Enter") return;

      const openModal = document.querySelector(
        '.modal.show[id^="modalFinalizar"]',
      );
      if (!openModal) return;

      const active = document.activeElement;
      if (active && active.tagName === "TEXTAREA") return;
      if (active && !openModal.contains(active)) return;

      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      const form = openModal.querySelector("form");
      if (!form) return;

      const currentId = form.querySelector('input[name="id"]')?.value || null;

      const hiddenNext =
        form.querySelector('input[type="hidden"][name="next"]') ||
        form.querySelector('input[name="next"]');
      const nextTarget = form.querySelector('input[name="next_target_id"]');

      if (e.shiftKey) {
        const nextId = currentId ? getNextPendingIdInPage(currentId) : null;
        if (hiddenNext) hiddenNext.value = "1";
        if (nextTarget) nextTarget.value = nextId ? String(nextId) : "0";

        const btnProximo = form.querySelector(
          'button[type="submit"][name="next"][value="1"]',
        );

        if (btnProximo && typeof form.requestSubmit === "function") {
          form.requestSubmit(btnProximo);
        } else if (btnProximo) {
          btnProximo.click();
        } else {
          form.submit();
        }
      } else {
        if (hiddenNext) hiddenNext.value = "0";
        if (nextTarget) nextTarget.value = "0";

        const btnFinalizar =
          form.querySelector('button[type="submit"]:not([name="next"])') ||
          form.querySelector('button[type="submit"][name="next"][value="0"]');

        if (btnFinalizar && typeof form.requestSubmit === "function") {
          form.requestSubmit(btnFinalizar);
        } else {
          form.submit();
        }
      }
    },
    true,
  );

  // ======================================================
  // 4) NOVO PEDIDO: Botões (SEU MODAL USA type="button")
  //    #btnNovoSalvar / #btnNovoProximo
  // ======================================================
  const submitNovoPedido = (goNext) => {
    const modalEl = document.getElementById("modalNovoPedido");
    if (!modalEl) return;

    const form = modalEl.querySelector("form");
    if (!form) return;

    const hiddenNext =
      form.querySelector('input[type="hidden"][name="next"]') ||
      form.querySelector('input[name="next"]');

    if (hiddenNext) hiddenNext.value = goNext ? "1" : "0";

    // submit padrão
    if (typeof form.requestSubmit === "function") form.requestSubmit();
    else form.submit();
  };

  document.addEventListener(
    "click",
    (e) => {
      const salvar = e.target?.closest?.("#btnNovoSalvar");
      if (salvar) {
        e.preventDefault();
        submitNovoPedido(false);
        return;
      }

      const proximo = e.target?.closest?.("#btnNovoProximo");
      if (proximo) {
        e.preventDefault();
        submitNovoPedido(true);
        return;
      }
    },
    true,
  );

  // ======================================================
  // 5) NOVO PEDIDO: Enter / Shift+Enter
  //    Enter       => salvar
  //    Shift+Enter => salvar + próximo
  //
  // IMPORTANTE: se o usuário estiver clicando/selecionando
  // na lista de sugestões do solicitante, não submeter.
  // ======================================================
  document.addEventListener(
    "keydown",
    (e) => {
      if (e.key !== "Enter") return;

      const modalEl = document.getElementById("modalNovoPedido");
      if (!modalEl) return;
      if (!modalEl.classList.contains("show")) return;

      const active = document.activeElement;
      if (!active || !modalEl.contains(active)) return;
      if (active.tagName === "TEXTAREA") return;

      // Se o foco/ação estiver dentro da lista de sugestões do solicitante, não submeter
      const sugestBox = modalEl.querySelector("#solicitanteSugestoes");
      if (sugestBox && sugestBox.style.display !== "none") {
        // se o Enter veio de um botão/option da listinha
        if (
          document.activeElement &&
          sugestBox.contains(document.activeElement)
        ) {
          return; // deixa o click do item acontecer
        }
      }

      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      submitNovoPedido(e.shiftKey === true);
    },
    true,
  );

  // ======================================================
  // 6) Atalho global: Alt + N abre Novo Pedido
  // ======================================================
  document.addEventListener("keydown", (e) => {
    if (!(e.altKey && e.key.toLowerCase() === "n")) return;

    const tag = document.activeElement?.tagName;
    if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") return;

    const btn = document.getElementById("btnNovoPedido");
    if (btn && !btn.disabled) {
      e.preventDefault();
      btn.click();
    }
  });

  // ======================================================
  // 7) Bootstrap às vezes deixa paddingRight; limpa
  // ======================================================
  document.addEventListener("hidden.bs.modal", () => {
    document.body.style.paddingRight = "";
  });

  // ======================================================
  // 8) Lógica de mostrar campos de quantidade conforme tipo
  // ======================================================
  (function () {
    const prata = document.getElementById("tipoPrata");
    const ouro = document.getElementById("tipoOuro");
    const qtdUnica = document.getElementById("qtdUnicaWrap");
    const qtdDupla = document.getElementById("qtdDuplaWrap");

    if (!prata || !ouro || !qtdUnica || !qtdDupla) return;

    function refresh() {
      const p = prata.checked;
      const o = ouro.checked;

      if (p && o) {
        qtdDupla.classList.remove("d-none");
        qtdUnica.classList.add("d-none");
      } else if (p || o) {
        qtdUnica.classList.remove("d-none");
        qtdDupla.classList.add("d-none");
      } else {
        qtdUnica.classList.add("d-none");
        qtdDupla.classList.add("d-none");
      }
    }

    prata.addEventListener("change", refresh);
    ouro.addEventListener("change", refresh);
    refresh();
  })();
})();
