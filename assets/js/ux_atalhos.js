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
            'input[name="quantidade_solicitada"]'
          );
          const selTipo = modalEl.querySelector('select[name="tipo"]');

          if (inpSolic && keepSolic) inpSolic.value = keepSolic;

          // limpa pra próxima criação
          if (inpProd) inpProd.value = "";
          if (selTipo) selTipo.value = "";
          if (inpQtd) inpQtd.value = "1";

          focusLater(inpProd);
        },
        { once: true }
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
      document.querySelectorAll('tr[data-retirada-id][data-pendente="1"]')
    );
    if (!rows.length) return null;

    const idx = rows.findIndex(
      (tr) => String(tr.getAttribute("data-retirada-id")) === String(currentId)
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
  //    (captura antes do submit)
  // ======================================================
  document.addEventListener(
    "click",
    (e) => {
      const btn = e.target?.closest?.(
        '.modal[id^="modalFinalizar"] button[type="submit"][name="next"][value="1"]'
      );
      if (!btn) return;

      const form = btn.closest("form");
      if (!form) return;

      const currentId = form.querySelector('input[name="id"]')?.value;
      if (!currentId) return;

      const nextId = getNextPendingIdInPage(currentId);

      // garante hidden next + next_target_id
      const hiddenNext =
        form.querySelector('input[type="hidden"][name="next"]') ||
        form.querySelector('input[name="next"]');
      const nextTarget = form.querySelector('input[name="next_target_id"]');

      if (hiddenNext) hiddenNext.value = "1";
      if (nextTarget) nextTarget.value = nextId ? String(nextId) : "0";
    },
    true
  );

  // ======================================================
  // 3) FINALIZAR: Enter / Shift+Enter (captura)
  //    Shift+Enter => Próximo
  //    Enter       => Finalizar
  // ======================================================
  document.addEventListener(
    "keydown",
    (e) => {
      if (e.key !== "Enter") return;

      const openModal = document.querySelector(
        '.modal.show[id^="modalFinalizar"]'
      );
      if (!openModal) return;

      const active = document.activeElement;

      // não mexe com textarea
      if (active && active.tagName === "TEXTAREA") return;
      // só se foco estiver dentro do modal
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
        // Próximo
        const nextId = currentId ? getNextPendingIdInPage(currentId) : null;
        if (hiddenNext) hiddenNext.value = "1";
        if (nextTarget) nextTarget.value = nextId ? String(nextId) : "0";

        const btnProximo = form.querySelector(
          'button[type="submit"][name="next"][value="1"]'
        );

        if (btnProximo && typeof form.requestSubmit === "function") {
          form.requestSubmit(btnProximo);
        } else if (btnProximo) {
          btnProximo.click();
        } else {
          form.submit();
        }
      } else {
        // Finalizar normal
        if (hiddenNext) hiddenNext.value = "0";
        if (nextTarget) nextTarget.value = "0";

        // preferir o botão "Finalizar" se existir
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
    true
  );

  // ======================================================
  // 4) NOVO PEDIDO: Enter / Shift+Enter (captura)
  //    (funciona MESMO se o navegador não enviar o value do botão)
  // ======================================================
  document.addEventListener(
    "keydown",
    (e) => {
      if (e.key !== "Enter") return;

      const modalEl = document.getElementById("modalNovoPedido");
      if (!modalEl) return;

      const isOpen = modalEl.classList.contains("show");
      if (!isOpen) return;

      const active = document.activeElement;
      if (!active || !modalEl.contains(active)) return;

      if (active.tagName === "TEXTAREA") return;

      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      const form = modalEl.querySelector("form");
      if (!form) return;

      // ✅ garante envio do next via hidden
      const hiddenNext =
        form.querySelector('input[type="hidden"][name="next"]') ||
        form.querySelector('input[name="next"]');

      const goNext = e.shiftKey === true;

      if (hiddenNext) hiddenNext.value = goNext ? "1" : "0";

      // tenta submeter como se fosse o botão correto (se existir)
      const btnProximo = form.querySelector(
        'button[type="submit"][name="next"][value="1"]'
      );
      const btnSalvar =
        form.querySelector('button[type="submit"][name="next"][value="0"]') ||
        form.querySelector('button[type="submit"]:not([name="next"])');

      if (goNext) {
        if (btnProximo && typeof form.requestSubmit === "function")
          form.requestSubmit(btnProximo);
        else if (btnProximo) btnProximo.click();
        else if (typeof form.requestSubmit === "function") form.requestSubmit();
        else form.submit();
      } else {
        if (btnSalvar && typeof form.requestSubmit === "function")
          form.requestSubmit(btnSalvar);
        else if (btnSalvar) btnSalvar.click();
        else if (typeof form.requestSubmit === "function") form.requestSubmit();
        else form.submit();
      }
    },
    true
  );

  // ======================================================
  // 5) NOVO PEDIDO: clique no botão "Próximo" também garante hidden next=1
  //    (se seu modal ainda usa submit normal)
  // ======================================================
  document.addEventListener(
    "click",
    (e) => {
      const btn = e.target?.closest?.(
        '#modalNovoPedido button[type="submit"][name="next"][value="1"]'
      );
      if (!btn) return;

      const form = btn.closest("form");
      if (!form) return;

      const hiddenNext =
        form.querySelector('input[type="hidden"][name="next"]') ||
        form.querySelector('input[name="next"]');

      if (hiddenNext) hiddenNext.value = "1";
    },
    true
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
})();
