(function () {
  // Lê config do HTML (data-attributes)
  const root = document.documentElement;
  const toastType = (root.dataset.toast || "").trim();
  const highlightId = parseInt(root.dataset.highlightId || "0", 10);

  // Toast (finalizado / excluido / editado / mês reaberto)
  if (toastType) {
    const el = document.getElementById("appToast");
    const body = document.getElementById("appToastBody");

    if (el && body && window.bootstrap) {
      el.classList.remove("text-bg-success", "text-bg-danger");

      if (toastType === "finalizado") {
        el.classList.add("text-bg-success");
        body.textContent = "✅ Pedido finalizado com sucesso!";
      } else if (toastType === "excluido") {
        el.classList.add("text-bg-danger");
        body.textContent = "🗑 Pedido excluído com sucesso!";
      } else if (toastType === "editado") {
        el.classList.add("text-bg-success");
        body.textContent = "✏️ Pedido editado com sucesso!";
      } else if (toastType === "mes_reaberto") {
        el.classList.add("text-bg-success");
        body.textContent = "🔁 Mês reaberto com sucesso!";
      }

      new bootstrap.Toast(el, { delay: 2500 }).show();
    }
  }

  // Highlight da linha
  if (highlightId > 0) {
    const row = document.querySelector('tr[data-id="' + highlightId + '"]');
    if (row) {
      row.classList.add("row-highlight");
      setTimeout(() => row.classList.remove("row-highlight"), 3500);
    }
  }

  // Limpa URL (toast e highlight_id)
  const url = new URL(window.location.href);
  if (url.searchParams.has("toast") || url.searchParams.has("highlight_id")) {
    url.searchParams.delete("toast");
    url.searchParams.delete("highlight_id");
    window.history.replaceState({}, document.title, url.toString());
  }
})();
