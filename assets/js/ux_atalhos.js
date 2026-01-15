(() => {
  const getPendentes = () => {
    return Array.from(
      document.querySelectorAll('tr[data-retirada-id][data-pendente="1"]')
    ).map((tr) => ({
      id: tr.getAttribute("data-retirada-id"),
      el: tr,
    }));
  };

  const openFinalizarModal = (id) => {
    const modalEl = document.getElementById(`modalFinalizar${id}`);
    if (!modalEl) return false;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    return true;
  };

  // 1) Foco automático ao abrir modal de finalizar
  document.addEventListener("shown.bs.modal", (e) => {
    const modalEl = e.target;
    if (!modalEl?.id?.startsWith("modalFinalizar")) return;

    const id = modalEl.id.replace("modalFinalizar", "");
    const input = document.getElementById(`qtdRetirada${id}`);
    if (input) setTimeout(() => input.focus(), 50);
  });

  // 2) Abrir automaticamente próximo pedido (?open_finalizar_id)
  const url = new URL(window.location.href);
  const nextId = url.searchParams.get("open_finalizar_id");

  if (nextId) {
    url.searchParams.delete("open_finalizar_id");
    window.history.replaceState({}, "", url.toString());

    openFinalizarModal(nextId);

    const row = document.querySelector(`tr[data-id="${nextId}"]`);
    if (row) row.classList.add("highlight-next");
  }

  // 3) Enter / Shift+Enter no campo quantidade_retirada
  document.addEventListener("keydown", (e) => {
    const active = document.activeElement;
    if (!active || active.tagName !== "INPUT") return;
    if (active.name !== "quantidade_retirada") return;

    const form = active.closest("form");
    if (!form) return;

    if (e.key === "Enter") {
      e.preventDefault();

      if (e.shiftKey) {
        const nextBtn = form.querySelector('button[name="next"]');
        if (nextBtn) nextBtn.click();
      } else {
        form.submit();
      }
    }
  });

  // 4) Corrigir paddingRight do body (bug Bootstrap)
  document.addEventListener("hide.bs.modal", () => {
    document.body.style.paddingRight = "";
  });

  (() => {
    const openNovoPedido = () => {
      // 1) tenta clicar no botão, se existir
      const btn = document.getElementById("btnNovoPedido");
      if (btn && !btn.disabled) {
        btn.click();
        return true;
      }

      // 2) fallback: abre o modal direto
      const modalEl = document.getElementById("modalNovoPedido");
      if (!modalEl) return false;

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
      return true;
    };

    document.addEventListener("keydown", (e) => {
      // não dispara se estiver digitando
      const tag = document.activeElement?.tagName;
      if (
        tag === "INPUT" ||
        tag === "TEXTAREA" ||
        document.activeElement?.isContentEditable
      )
        return;

      // Atalho alternativo: Alt + N
      if (e.altKey && e.key.toLowerCase() === "n") {
        e.preventDefault();
        openNovoPedido();
      }
    });
  })();
  (() => {
    const modalId = "modalNovoPedido";

    // Antes de esconder: tira foco de qualquer coisa dentro do modal
    document.addEventListener("hide.bs.modal", (e) => {
      const modalEl = e.target;
      if (!modalEl || modalEl.id !== modalId) return;

      if (modalEl.contains(document.activeElement)) {
        document.activeElement.blur();
      }
    });

    // Depois de esconder: devolve foco para um elemento fora do modal
    document.addEventListener("hidden.bs.modal", (e) => {
      const modalEl = e.target;
      if (!modalEl || modalEl.id !== modalId) return;

      const btn = document.getElementById("btnNovoPedido");
      if (btn && !btn.disabled) btn.focus();
    });
  })();

  // Corrigir paddingRight do body (bug Bootstrap)
  document.addEventListener("hide.bs.modal", () => {
    document.body.style.paddingRight = "";
  });
})();
