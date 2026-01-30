(() => {
  const STORAGE_KEY = "theme";
  const root = document.documentElement;
  const btn = document.getElementById("btnTheme");

  // 1) Tema salvo ou padrão
  const savedTheme = localStorage.getItem(STORAGE_KEY);
  if (savedTheme === "dark" || savedTheme === "light") {
    root.setAttribute("data-bs-theme", savedTheme);
  }
  document.dispatchEvent(
    new CustomEvent("theme:changed", {
      detail: { theme: root.getAttribute("data-bs-theme") },
    }),
  );

  // 2) Atualiza texto do botão
  function updateButton(theme) {
    if (!btn) return;
    btn.textContent = theme === "dark" ? "☀️ Tema claro" : "🌙 Tema escuro";
  }

  updateButton(root.getAttribute("data-bs-theme"));

  // 3) Clique
  if (btn) {
    btn.addEventListener("click", () => {
      const current = root.getAttribute("data-bs-theme") || "light";
      const next = current === "dark" ? "light" : "dark";
      root.setAttribute("data-bs-theme", next);
      localStorage.setItem(STORAGE_KEY, next);
      updateButton(next);
    });
  }
})();
