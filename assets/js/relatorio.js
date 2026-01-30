// /assets/js/relatorio.js
(async () => {
  try {
    const root = document.documentElement;
    const competencia = root?.dataset?.competencia;
    if (!competencia) return;

    const url = `../actions/relatorio_data.php?competencia=${encodeURIComponent(
      competencia,
    )}`;

    const res = await fetch(url, { credentials: "same-origin" });
    if (!res.ok) {
      console.error("Falha ao carregar relatório:", res.status, url);
      return;
    }

    const data = await res.json();
    if (data?.error) {
      console.error("Endpoint retornou erro:", data.error);
      return;
    }

    // =========================================
    // Instâncias (para destruir e recriar no tema)
    // =========================================
    const charts = {
      status: null,
      alertas: null,
      dias: null,
      top: null,
      solicitantes: null,
    };

    // =========================================
    // Tema (SÓ AQUI) — evita duplicar isDark/chartText/etc
    // =========================================
    function getThemeVars() {
      const isDark =
        (document.documentElement.getAttribute("data-bs-theme") || "") ===
        "dark";

      const chartText = isDark ? "rgba(240,245,255,.92)" : "rgba(0,0,0,.75)";
      const chartGrid = isDark ? "rgba(240,245,255,.12)" : "rgba(0,0,0,.10)";
      const chartBorder = isDark ? "rgba(255,255,255,.25)" : "#ffffff";

      const YSY_COLORS = isDark
        ? {
            blue: "#4DA3FF", // azul vivo no dark
            blue2: "#7FA6C7",
            pink: "#F5DADE",
            amber: "#f1aeb8",
            fillBlue: "rgba(77,163,255,.18)",
            barPink: "rgba(245,218,222,.95)",
            barBlue: "rgba(77,163,255,.90)",
          }
        : {
            blue: "#002855", // azul YSY
            blue2: "#7FA6C7",
            pink: "#F5DADE",
            amber: "#f1aeb8",
            fillBlue: "rgba(0,40,85,.15)",
            barPink: "rgba(245,218,222,.95)",
            barBlue: "rgba(0,40,85,.90)",
          };

      return { isDark, chartText, chartGrid, chartBorder, YSY_COLORS };
    }

    function applyChartDefaults() {
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

    // =========================================
    // Helpers
    // =========================================
    const fmt = (n) => {
      try {
        return new Intl.NumberFormat("pt-BR").format(Number(n || 0));
      } catch {
        return String(n || 0);
      }
    };

    const shortDate = (iso) => {
      // "2026-01-15" -> "15/01"
      if (typeof iso !== "string") return String(iso ?? "");
      const p = iso.split("-");
      if (p.length === 3) return `${p[2]}/${p[1]}`;
      return iso;
    };

    // =========================================
    // Modal Solicitante (mantido)
    // =========================================
    const modalEl = document.getElementById("modalSolicitanteItens");
    const bsModalSolicitante = modalEl ? new bootstrap.Modal(modalEl) : null;

    async function abrirDetalheSolicitanteItens(solicitante) {
      if (!bsModalSolicitante) return;

      const sub = document.getElementById("modalSolicitanteSub");
      const loading = document.getElementById("modalSolicitanteLoading");
      const content = document.getElementById("modalSolicitanteContent");
      const erro = document.getElementById("modalSolicitanteErro");
      const total = document.getElementById("modalSolicitanteTotal");
      const tbody = document.getElementById("modalSolicitanteTbody");

      if (!sub || !loading || !content || !erro || !total || !tbody) {
        console.warn(
          "Modal de solicitante não encontrado. Verifique o HTML do modal.",
        );
        return;
      }

      sub.textContent = `${solicitante} • ${competencia}`;
      loading.style.display = "block";
      content.style.display = "none";
      erro.style.display = "none";
      erro.textContent = "";
      tbody.innerHTML = "";
      total.textContent = "";

      bsModalSolicitante.show();

      try {
        const urlApi = `../pages/api/solicitante_itens_entregues.php?competencia=${encodeURIComponent(
          competencia,
        )}&solicitante=${encodeURIComponent(solicitante)}`;

        const r = await fetch(urlApi, {
          credentials: "same-origin",
          headers: { Accept: "application/json" },
        });

        const j = await r.json().catch(() => null);

        if (!r.ok || !j?.ok) {
          throw new Error(j?.error || "erro_api");
        }

        total.textContent = fmt(j.total_itens_entregues);

        if (!Array.isArray(j.itens) || j.itens.length === 0) {
          tbody.innerHTML = `<tr><td colspan="4" class="text-muted small">Nenhum item entregue encontrado.</td></tr>`;
        } else {
          tbody.innerHTML = j.itens
            .map(
              (row) => `
              <tr>
                <td>${String(row.produto || "")}</td>
                <td class="text-muted">${String(row.tipo || "")}</td>
                <td><strong>${fmt(row.itens_entregues)}</strong></td>
                <td class="text-muted">${fmt(row.pedidos)}</td>
              </tr>
            `,
            )
            .join("");
        }

        loading.style.display = "none";
        content.style.display = "block";
      } catch (e) {
        loading.style.display = "none";
        erro.style.display = "block";
        erro.textContent =
          "Não foi possível carregar os itens desse solicitante.";
        console.error(e);
      }
    }

    // =========================================
    // 1) Status (doughnut)
    // =========================================
    function renderStatus() {
      const elStatus = document.getElementById("chartStatus");
      if (!elStatus || !data?.status) return;

      const { chartBorder, YSY_COLORS } = getThemeVars();

      if (charts.status) charts.status.destroy();

      charts.status = new Chart(elStatus, {
        type: "doughnut",
        data: {
          labels: [
            "Finalizados",
            "Balanço feito",
            "Precisa balanço",
            "Sem estoque",
          ],
          datasets: [
            {
              data: [
                Number(data.status.finalizados || 0),
                Number(data.status.balanco_feito || 0),
                Number(data.status.balanco || 0),
                Number(data.status.sem_estoque || 0),
              ],
              backgroundColor: [
                YSY_COLORS.blue, // Finalizados
                YSY_COLORS.blue2, // Balanço feito
                YSY_COLORS.amber, // Precisa balanço
                YSY_COLORS.pink, // Sem estoque
              ],
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
          },
        },
      });
    }

    // =========================================
    // 2) Alertas (bar)
    // =========================================
    function renderAlertas() {
      const elAlertas = document.getElementById("chartAlertas");
      if (!elAlertas || !data?.alertas) return;

      const { chartText, chartGrid, YSY_COLORS } = getThemeVars();

      if (charts.alertas) charts.alertas.destroy();

      charts.alertas = new Chart(elAlertas, {
        type: "bar",
        data: {
          labels: ["Sem estoque", "Precisa balanço"],
          datasets: [
            {
              data: [
                Number(data.alertas.sem_estoque || 0),
                Number(data.alertas.balanco || 0),
              ],
              backgroundColor: [YSY_COLORS.barPink, YSY_COLORS.barBlue],
              borderRadius: 8,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: { legend: { display: false } },
          scales: {
            x: {
              grid: { color: chartGrid },
              ticks: { color: chartText },
            },
            y: {
              beginAtZero: true,
              grid: { color: chartGrid },
              ticks: { color: chartText, precision: 0 },
            },
          },
        },
      });
    }

    // =========================================
    // 3) Pedidos por dia (line) + resumo
    // =========================================
    function renderDias() {
      const elDias = document.getElementById("chartDias");
      if (!elDias || !data?.dias?.labels || !data?.dias?.values) return;

      const { chartText, chartGrid, YSY_COLORS } = getThemeVars();

      const vals = (data.dias.values || []).map((n) => Number(n || 0));
      const total = vals.reduce((a, b) => a + b, 0);
      const dias = vals.length || 1;
      const media = total / dias;
      const pico = vals.length ? Math.max(...vals) : 0;
      const minimo = vals.length ? Math.min(...vals) : 0;

      if (charts.dias) charts.dias.destroy();

      charts.dias = new Chart(elDias, {
        type: "line",
        data: {
          labels: data.dias.labels,
          datasets: [
            {
              label: "Pedidos",
              data: vals,
              tension: 0.35,
              pointRadius: 3,
              borderWidth: 2,
              borderColor: YSY_COLORS.blue,
              backgroundColor: YSY_COLORS.fillBlue,
              fill: true,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: { legend: { display: false } },
          scales: {
            x: {
              grid: { color: chartGrid },
              ticks: {
                color: chartText,
                autoSkip: true,
                maxTicksLimit: 8, // ✅ reduz poluição
                maxRotation: 0, // ✅ não inclina
                minRotation: 0,
                callback: function (value) {
                  const label = this.getLabelForValue(value);
                  return shortDate(label);
                },
              },
            },
            y: {
              beginAtZero: true,
              grid: { color: chartGrid },
              ticks: { color: chartText, precision: 0 },
            },
          },
        },
      });

      const elResumo = document.getElementById("diasResumo");
      if (elResumo) {
        elResumo.innerHTML = `
          Média: <strong>${media.toFixed(1)}</strong> pedidos/dia
          <span class="ms-2">• Total: <strong>${fmt(total)}</strong></span>
          <span class="ms-2">• Pico: <strong>${fmt(pico)}</strong></span>
          <span class="ms-2">• Mín: <strong>${fmt(minimo)}</strong></span>
        `;
      }
    }

    // =========================================
    // 4) Top produtos (bar horizontal) + botões
    // =========================================
    const elTop = document.getElementById("chartTopProdutos");
    let currentLimit = 10;
    const step = 10;
    const maxLimit = 200;

    function renderTop(labels, values) {
      if (!elTop) return;

      if (!labels.length) {
        if (charts.top) charts.top.destroy();
        charts.top = null;
        return;
      }

      // ✅ aumenta a “margem” interna do card (evita número colado / vazando)
      const base = 260;
      const perItem = 22;
      const target = Math.min(1200, Math.max(base, labels.length * perItem));

      const wrapper = elTop.parentElement; // .chart-wrapper
      if (wrapper) wrapper.style.height = `${target}px`;

      if (charts.top) charts.top.destroy();

      const { chartText, chartGrid, YSY_COLORS } = getThemeVars();

      charts.top = new Chart(elTop, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label: "Qtd retirada total",
              data: values,
              backgroundColor: YSY_COLORS.barBlue,
              borderRadius: 8,
            },
          ],
        },
        options: {
          indexAxis: "y",
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: { left: 6, right: 18, top: 6, bottom: 6 } }, // ✅ evita “vazar”
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: { label: (ctx) => ` ${fmt(ctx.raw)} itens` },
            },
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: { color: chartGrid },
              ticks: {
                color: chartText,
                precision: 0,
                padding: 6,
              },
            },
            y: {
              grid: { color: chartGrid },
              ticks: {
                color: chartText,
                autoSkip: false,
              },
            },
          },
        },
      });
    }

    async function fetchTopByLimit(limit) {
      const url2 = `../actions/relatorio_data.php?competencia=${encodeURIComponent(
        competencia,
      )}&limit=${encodeURIComponent(String(limit))}`;

      const res2 = await fetch(url2, { credentials: "same-origin" });
      if (!res2.ok) {
        console.error("Falha ao carregar top produtos:", res2.status, url2);
        return null;
      }
      const d2 = await res2.json();
      if (d2?.error) {
        console.error("Endpoint top produtos retornou erro:", d2.error);
        return null;
      }
      return d2;
    }

    async function loadAndRenderTopLimit(limit) {
      const d = await fetchTopByLimit(limit);
      if (!d) return;

      const labels = d.top_produtos?.labels || [];
      const values = d.top_produtos?.values || [];
      renderTop(labels, values);
    }

    function ensureTopButtons() {
      if (!elTop) return;

      const topCard = elTop.closest(".card");
      const topHeader =
        topCard?.querySelector(
          ".d-flex.justify-content-between.align-items-center.mb-2",
        ) ||
        topCard?.querySelector(
          ".d-flex.justify-content-between.align-items-center",
        ) ||
        topCard?.querySelector(".d-flex.justify-content-between");

      if (!topHeader) return;
      if (topHeader.querySelector("#btnTopMore")) return;

      const wrap = document.createElement("div");
      wrap.className = "d-flex gap-2 align-items-center";

      const btnLess = document.createElement("button");
      btnLess.type = "button";
      btnLess.id = "btnTopLess";
      btnLess.className = "btn btn-outline-secondary btn-sm";
      btnLess.textContent = "Mostrar menos";
      btnLess.disabled = true;

      const btnMore = document.createElement("button");
      btnMore.type = "button";
      btnMore.id = "btnTopMore";
      btnMore.className = "btn btn-outline-secondary btn-sm";
      btnMore.textContent = "Mostrar mais";

      const setLoading = (loading) => {
        btnMore.disabled = loading || currentLimit >= maxLimit;
        btnLess.disabled = loading || currentLimit <= 10;
        btnMore.textContent = loading ? "Carregando..." : "Mostrar mais";
      };

      btnMore.addEventListener("click", async () => {
        const next = Math.min(maxLimit, currentLimit + step);
        if (next === currentLimit) return;

        setLoading(true);
        try {
          currentLimit = next;
          await loadAndRenderTopLimit(currentLimit);
        } finally {
          setLoading(false);
        }
      });

      btnLess.addEventListener("click", async () => {
        setLoading(true);
        try {
          currentLimit = 10;
          await loadAndRenderTopLimit(currentLimit);
        } finally {
          setLoading(false);
        }
      });

      wrap.appendChild(btnLess);
      wrap.appendChild(btnMore);

      topHeader.insertBefore(wrap, topHeader.lastElementChild);
    }

    // =========================================
    // 5) Solicitantes (bar) + filtro + clique rosa
    // =========================================
    const elSol = document.getElementById("chartSolicitantes");
    const sel = document.getElementById("selSolicitante");
    const boxResumo = document.getElementById("solicitanteResumo");

    const solLabels = data.por_solicitante?.labels || [];
    const solPedidos = data.por_solicitante?.pedidos || [];
    const solItens = data.por_solicitante?.itens || [];

    function renderSolicitantes(filterName = "") {
      if (!elSol) return;

      let labels = solLabels;
      let pedidos = solPedidos;
      let itens = solItens;

      if (filterName) {
        const idx = solLabels.indexOf(filterName);
        labels = idx >= 0 ? [solLabels[idx]] : [];
        pedidos = idx >= 0 ? [solPedidos[idx]] : [];
        itens = idx >= 0 ? [solItens[idx]] : [];
      }

      if (charts.solicitantes) charts.solicitantes.destroy();

      const { chartText, chartGrid, YSY_COLORS } = getThemeVars();

      charts.solicitantes = new Chart(elSol, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label: "Pedidos",
              data: pedidos,
              backgroundColor: YSY_COLORS.barBlue,
              borderRadius: 10,
              barPercentage: 0.65,
              categoryPercentage: 0.62,
              maxBarThickness: 36,
            },
            {
              label: "Itens entregues",
              data: itens,
              backgroundColor: YSY_COLORS.barPink,
              borderRadius: 10,
              barPercentage: 0.65,
              categoryPercentage: 0.62,
              maxBarThickness: 36,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: { left: 10, right: 10, top: 6, bottom: 0 } },
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                color: chartText,
                usePointStyle: true,
                boxWidth: 10,
                padding: 14,
              },
            },
            tooltip: {
              titleColor: chartText,
              bodyColor: chartText,
              callbacks: {
                label: (ctx) => ` ${ctx.dataset.label}: ${fmt(ctx.raw)}`,
              },
            },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: chartText, maxRotation: 0, autoSkip: false },
            },
            y: {
              beginAtZero: true,
              grid: { color: chartGrid },
              ticks: { color: chartText, precision: 0 },
            },
          },
        },
      });

      // Clique na barra rosa abre modal
      elSol.onclick = (evt) => {
        const chart = charts.solicitantes;
        if (!chart) return;

        const points = chart.getElementsAtEventForMode(
          evt,
          "nearest",
          { intersect: true },
          true,
        );
        if (!points.length) return;

        const p = points[0];
        const ds = chart.data.datasets?.[p.datasetIndex];
        const dsLabel = (ds?.label || "").toLowerCase();
        const solicitante = chart.data.labels?.[p.index];

        if (!solicitante) return;

        if (dsLabel.includes("itens") && dsLabel.includes("entreg")) {
          abrirDetalheSolicitanteItens(String(solicitante));
        }
      };

      if (boxResumo) {
        if (!filterName)
          boxResumo.textContent = `Total de solicitantes no mês: ${solLabels.length}`;
        else {
          const idx = solLabels.indexOf(filterName);
          boxResumo.textContent = `📌 ${filterName}: ${
            solPedidos[idx] || 0
          } pedidos | ${solItens[idx] || 0} itens entregues`;
        }
      }
    }

    // =========================================
    // Rebuild (tema) — recria TUDO com as cores certas
    // =========================================
    function rebuildAllCharts() {
      applyChartDefaults();

      renderStatus();
      renderAlertas();
      renderDias();

      // Top produtos: recria usando os dados já carregados (sem refetch)
      if (elTop) {
        const topLabels = data.top_produtos?.labels || [];
        const topValues = data.top_produtos?.values || [];
        renderTop(topLabels, topValues);
      }

      // Solicitantes: mantém o filtro atual (se houver)
      if (elSol) {
        const current = sel?.value || "";
        renderSolicitantes(current === "Todos" ? "" : current);
      }
    }

    // =========================================
    // Inicialização
    // =========================================
    applyChartDefaults();

    renderStatus();
    renderAlertas();
    renderDias();

    if (elTop) {
      ensureTopButtons();
      const topLabels = data.top_produtos?.labels || [];
      const topValues = data.top_produtos?.values || [];
      renderTop(topLabels, topValues);
    }

    if (!solLabels.length) {
      if (boxResumo)
        boxResumo.textContent = "Sem dados de solicitantes para este mês.";
    } else {
      if (sel) {
        while (sel.options.length > 1) sel.remove(1);
        solLabels.forEach((name) => {
          const opt = document.createElement("option");
          opt.value = name;
          opt.textContent = name;
          sel.appendChild(opt);
        });

        sel.addEventListener("change", () =>
          renderSolicitantes(sel.value === "Todos" ? "" : sel.value),
        );
      }

      renderSolicitantes("");
    }

    // =========================================
    // Troca de tema sem recarregar
    // =========================================
    document.addEventListener("theme:changed", () => rebuildAllCharts());

    // fallback: se alguém mudar o atributo sem disparar evento
    const obs = new MutationObserver(() => rebuildAllCharts());
    obs.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["data-bs-theme"],
    });
  } catch (e) {
    console.error("Erro no relatorio.js:", e);
  }
})();
