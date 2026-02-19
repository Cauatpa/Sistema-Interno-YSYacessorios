// /assets/js/relatorio_lotes.js
(async () => {
  const sel = document.getElementById("selLote");
  if (!sel) return;

  const charts = { status: null, top: null };

  const modalEl = document.getElementById("modalLoteStatus");
  const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

  const elKpiPrevisto = document.getElementById("kpiPrevisto");
  const elKpiRecebido = document.getElementById("kpiRecebido");
  const elKpiOk = document.getElementById("kpiOk");
  const elKpiDiverg = document.getElementById("kpiDiverg");

  const pill = document.getElementById("pillLoteStatus");
  const lblSel = document.getElementById("lblLoteSelecionado");
  const lblComp = document.getElementById("lblCompetencia");
  const lblForn = document.getElementById("lblFornecedor");

  const tbody = document.getElementById("tbodyItens");
  const hint = document.getElementById("tableHint");
  const selStatus = document.getElementById("selStatusFiltro");
  const txtBusca = document.getElementById("txtBusca");

  function fmt(n) {
    try {
      return new Intl.NumberFormat("pt-BR").format(Number(n || 0));
    } catch {
      return String(n || 0);
    }
  }

  function getThemeVars() {
    const isDark =
      (document.documentElement.getAttribute("data-bs-theme") || "") === "dark";

    const chartText = isDark ? "rgba(240,245,255,.92)" : "rgba(0,0,0,.75)";
    const chartGrid = isDark ? "rgba(240,245,255,.12)" : "rgba(0,0,0,.10)";
    const chartBorder = isDark ? "rgba(255,255,255,.25)" : "#ffffff";

    const YSY = isDark
      ? {
          blue: "#4DA3FF",
          blue2: "#7FA6C7",
          pink: "#F5DADE",
          amber: "#f1aeb8",
          barBlue: "rgba(77,163,255,.90)",
          barPink: "rgba(245,218,222,.95)",
        }
      : {
          blue: "#002855",
          blue2: "#7FA6C7",
          pink: "#F5DADE",
          amber: "#f1aeb8",
          barBlue: "rgba(0,40,85,.90)",
          barPink: "rgba(245,218,222,.95)",
        };

    return { isDark, chartText, chartGrid, chartBorder, YSY };
  }

  function applyDefaults() {
    const { isDark, chartText, chartGrid } = getThemeVars();
    Chart.defaults.color = chartText;
    Chart.defaults.borderColor = chartGrid;
    Chart.defaults.font.family =
      "'Inter', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, sans-serif";

    Chart.defaults.plugins.legend.labels.color = chartText;
    Chart.defaults.plugins.tooltip.backgroundColor = isDark
      ? "rgba(15,22,32,.96)"
      : "rgba(255,255,255,.96)";
    Chart.defaults.plugins.tooltip.titleColor = chartText;
    Chart.defaults.plugins.tooltip.bodyColor = chartText;
    Chart.defaults.plugins.tooltip.borderColor = chartGrid;
    Chart.defaults.plugins.tooltip.borderWidth = 1;
  }

  async function fetchData(loteId) {
    const url = `../actions/relatorio_lotes_data.php?lote_id=${encodeURIComponent(
      String(loteId),
    )}`;
    const res = await fetch(url, { credentials: "same-origin" });
    const j = await res.json().catch(() => null);
    if (!res.ok || !j?.ok) throw new Error(j?.error || "erro_fetch");
    return j;
  }

  function setHeader(d) {
    const lote = d.lote || {};
    if (lblSel) lblSel.textContent = lote.label || "—";
    if (lblComp) lblComp.textContent = lote.competencia || "—";
    if (lblForn) lblForn.textContent = lote.fornecedor || "—";

    if (pill) {
      const st = String(lote.status || "").toLowerCase();
      pill.classList.remove("pill-open", "pill-closed");
      if (st === "fechado") {
        pill.classList.add("pill-closed");
        pill.textContent = "🔒 Lote fechado";
      } else if (st === "conferido") {
        pill.classList.add("pill-open");
        pill.textContent = "✅ Conferido";
      } else {
        pill.classList.add("pill-open");
        pill.textContent = "🟢 Aberto";
      }
    }
  }

  function setKPIs(d) {
    const k = d.kpis || {};
    if (elKpiPrevisto) elKpiPrevisto.textContent = fmt(k.total_previsto);
    if (elKpiRecebido) elKpiRecebido.textContent = fmt(k.total_conferido);
    if (elKpiOk) elKpiOk.textContent = fmt(k.total_ok);
    if (elKpiDiverg) elKpiDiverg.textContent = fmt(k.skus_diverg);
  }

  function fillStatusFilter(itens) {
    if (!selStatus) return;

    const set = new Set();
    (itens || []).forEach((r) => set.add(String(r.situacao || "Outro")));
    const statuses = Array.from(set).sort((a, b) => a.localeCompare(b));

    selStatus.innerHTML =
      `<option value="">Todos os status</option>` +
      statuses
        .map(
          (s) => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`,
        )
        .join("");
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function renderTable(itens) {
    if (!tbody) return;

    const statusFiltro = (selStatus?.value || "").trim();
    const q = (txtBusca?.value || "").trim().toLowerCase();

    const rows = (itens || []).filter((r) => {
      const st = String(r.situacao || "Outro");
      const okStatus = !statusFiltro || st === statusFiltro;

      const texto =
        `${r.produto || ""} ${r.variacao || ""} ${r.situacao || ""}`.toLowerCase();
      const okBusca = !q || texto.includes(q);

      return okStatus && okBusca;
    });

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-muted small">Nenhum item encontrado.</td></tr>`;
      if (hint) hint.textContent = "";
      return;
    }

    tbody.innerHTML = rows
      .map((r) => {
        const dif = Number(r.diferenca || 0);
        const difTxt = dif === 0 ? "0" : dif > 0 ? `+${fmt(dif)}` : fmt(dif);

        const badge =
          String(r.situacao || "Outro").toLowerCase() === "ok"
            ? "text-bg-success"
            : "text-bg-secondary";

        return `
          <tr>
            <td>${escapeHtml(r.produto || "")}</td>
            <td class="text-muted">${escapeHtml(r.variacao || "")}</td>
            <td>${fmt(r.previsto)}</td>
            <td><strong>${fmt(r.conferido)}</strong></td>
            <td>${difTxt}</td>
            <td><span class="badge ${badge}">${escapeHtml(r.situacao || "Outro")}</span></td>
          </tr>
        `;
      })
      .join("");

    if (hint)
      hint.textContent = `Mostrando ${rows.length} de ${(itens || []).length} itens`;
  }

  function renderStatusChart(d, loteId) {
    const el = document.getElementById("chartStatusLote");
    if (!el) return;

    const { chartBorder, YSY } = getThemeVars();

    if (charts.status) charts.status.destroy();

    const labels = d.status?.labels || [];
    const values = d.status?.values || [];

    // Paleta com prioridade para OK / Faltando / A mais / etc.
    const colors = labels.map((s, i) => {
      const key = String(s).toLowerCase().trim();
      if (key === "ok") return YSY.blue;
      if (key === "faltando") return YSY.pink;
      if (key === "a mais" || key === "a+mais") return YSY.amber;
      return i % 2 ? YSY.barBlue : YSY.barPink;
    });

    charts.status = new Chart(el, {
      type: "doughnut",
      data: {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: colors,
            borderColor: chartBorder,
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "65%",
        plugins: {
          legend: {
            position: "bottom",
            labels: { usePointStyle: true, boxWidth: 10, padding: 14 },
          },
          tooltip: {
            callbacks: { label: (ctx) => ` ${ctx.label}: ${fmt(ctx.raw)}` },
          },
        },
        onHover: (evt, active) => {
          const canvas = evt?.chart?.canvas;
          if (canvas)
            canvas.style.cursor = active?.length ? "pointer" : "default";
        },
        onClick: async (evt, active) => {
          if (!active?.length) return;
          const idx = active[0].index;
          const status = labels?.[idx];
          if (!status) return;

          await abrirModalStatus(
            loteId,
            String(status),
            d.lote?.label || "Lote",
          );
        },
      },
    });

    const hintEl = document.getElementById("statusHint");
    if (hintEl)
      hintEl.textContent =
        "Clique em um status para ver os itens desse status.";
  }

  function renderTopDiverg(d) {
    const el = document.getElementById("chartTopDiverg");
    if (!el) return;

    const { chartText, chartGrid, YSY } = getThemeVars();

    if (charts.top) charts.top.destroy();

    const labels = (d.top_diverg || []).map((r) => String(r.produto || "—"));
    const values = (d.top_diverg || []).map((r) => Number(r.diff_abs || 0));

    charts.top = new Chart(el, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: YSY.barBlue,
            borderRadius: 8,
            maxBarThickness: 36,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { left: 6, right: 18, top: 6, bottom: 6 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: { label: (ctx) => ` Diferença: ${fmt(ctx.raw)}` },
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: { color: chartGrid },
            ticks: { color: chartText, precision: 0 },
          },
          y: {
            grid: { display: false },
            ticks: { color: chartText, autoSkip: true },
          },
        },
      },
    });

    const hintEl = document.getElementById("topHint");
    if (hintEl) hintEl.textContent = "Top 10 itens com maior diferença (abs).";
  }

  async function abrirModalStatus(loteId, status, loteLabel) {
    if (!bsModal) return;

    const title = document.getElementById("modalLoteStatusTitle");
    const sub = document.getElementById("modalLoteStatusSub");
    const loading = document.getElementById("modalLoteStatusLoading");
    const content = document.getElementById("modalLoteStatusContent");
    const erro = document.getElementById("modalLoteStatusErro");
    const total = document.getElementById("modalLoteStatusTotal");
    const tbodyModal = document.getElementById("modalLoteStatusTbody");

    title.textContent = `Status: ${status}`;
    sub.textContent = `${loteLabel}`;
    loading.style.display = "block";
    content.style.display = "none";
    erro.style.display = "none";
    erro.textContent = "";
    total.textContent = "0";
    tbodyModal.innerHTML = "";

    bsModal.show();

    try {
      const url = `../pages/api/lote_itens_por_status.php?lote_id=${encodeURIComponent(
        String(loteId),
      )}&status=${encodeURIComponent(status)}`;

      const res = await fetch(url, {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });

      const j = await res.json().catch(() => null);
      if (!res.ok || !j?.ok) throw new Error(j?.error || "erro_api");

      total.textContent = fmt(j.total);

      if (!Array.isArray(j.itens) || !j.itens.length) {
        tbodyModal.innerHTML = `<tr><td colspan="5" class="text-muted small">Nenhum item encontrado.</td></tr>`;
      } else {
        tbodyModal.innerHTML = j.itens
          .map((r) => {
            const dif = Number(r.diferenca || 0);
            const difTxt =
              dif === 0 ? "0" : dif > 0 ? `+${fmt(dif)}` : fmt(dif);

            return `
              <tr>
                <td>${escapeHtml(r.produto || "")}</td>
                <td class="text-muted">${escapeHtml(r.variacao || "")}</td>
                <td>${fmt(r.previsto)}</td>
                <td><strong>${fmt(r.conferido)}</strong></td>
                <td>${difTxt}</td>
              </tr>
            `;
          })
          .join("");
      }

      loading.style.display = "none";
      content.style.display = "block";
    } catch (e) {
      loading.style.display = "none";
      erro.style.display = "block";
      erro.textContent = "Não foi possível carregar os itens desse status.";
      console.error(e);
    }
  }

  let lastData = null;
  let lastLoteId = null;

  function bindFiltersOnce() {
    if (!selStatus || !txtBusca) return;
    if (selStatus.dataset.bound) return;
    selStatus.dataset.bound = "1";

    selStatus.addEventListener("change", () => {
      if (lastData?.itens) renderTable(lastData.itens);
    });

    txtBusca.addEventListener("input", () => {
      if (lastData?.itens) renderTable(lastData.itens);
    });
  }

  async function renderAll(d, loteId) {
    applyDefaults();

    setHeader(d);
    setKPIs(d);

    fillStatusFilter(d.itens || []);
    bindFiltersOnce();

    renderStatusChart(d, loteId);
    renderTopDiverg(d);
    renderTable(d.itens || []);

    const resumo = document.getElementById("loteResumo");
    if (resumo) {
      const k = d.kpis || {};
      resumo.textContent = `${d.lote?.label || "Lote"} • Conferido: ${fmt(k.total_conferido)} • Conformidade: ${Number(k.ok_pct || 0).toFixed(1)}%`;
    }
  }

  async function loadLote(loteId) {
    if (!loteId) return;
    const d = await fetchData(loteId);
    lastData = d;
    lastLoteId = loteId;
    await renderAll(d, loteId);
  }

  sel.addEventListener("change", () => loadLote(sel.value));

  // Rebuild no dark mode
  function rebuild() {
    if (!lastData || !lastLoteId) return;
    renderAll(lastData, lastLoteId);
  }

  document.addEventListener("theme:changed", rebuild);

  const obs = new MutationObserver(rebuild);
  obs.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-bs-theme"],
  });
})();
