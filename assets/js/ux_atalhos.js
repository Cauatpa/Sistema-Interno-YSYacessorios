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

  // 1) Foco automático quando abrir modal
  document.addEventListener("shown.bs.modal", (e) => {
    const modalEl = e.target;
    if (!modalEl?.id?.startsWith("modalFinalizar")) return;

    const id = modalEl.id.replace("modalFinalizar", "");
    const input = document.getElementById(`qtdRetirada${id}`);
    if (input) setTimeout(() => input.focus(), 50);
  });

  // 2) Se veio da ação com ?next_id=XXX → abre automaticamente
  const url = new URL(window.location.href);
  const nextId = url.searchParams.get("next_id");
  if (nextId) {
    // limpa da URL pra não reabrir ao dar F5
    url.searchParams.delete("next_id");
    window.history.replaceState({}, "", url.toString());

    openFinalizarModal(nextId);
  }

  // 3) Atalho Enter / Shift+Enter para submeter formulário ou ir para próximo
  document.addEventListener("keydown", (e) => {
    const active = document.activeElement;
    if (!active || active.tagName !== "INPUT") return;
    if (!active.name || active.name !== "quantidade_retirada") return;

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
    // fim do keydown

    // 4) Destacar próxima linha pendente
    const nextId = params.get("open_finalizar_id");
    if (nextId) {
      const row = document.querySelector(`tr[data-id="${nextId}"]`);
      if (row) row.classList.add("highlight-next");
    }
  });

  // 5) Corrigir paddingRight ao abrir modal (Bootstrap bug)
  document.addEventListener("hide.bs.modal", () => {
    document.body.style.paddingRight = "";
  });
})();
