(async () => {
  try {
    const root = document.documentElement;
    const competencia = root?.dataset?.competencia;
    if (!competencia) return;

    const url = `./actions/relatorio_data.php?competencia=${encodeURIComponent(
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

    // 1) Status (doughnut)
    // ✅ ÚNICA MUDANÇA: trocar "Pendentes" por "Balanço feito"
    // ✅ e usar data.status.balanco_feito no lugar de data.status.pendentes
    const elStatus = document.getElementById("chartStatus");
    if (elStatus && data?.status) {
      new Chart(elStatus, {
        type: "doughnut",
        data: {
          labels: ["Finalizados", "Balanço feito"],
          datasets: [
            { data: [data.status.finalizados, data.status.balanco_feito] },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: "bottom" } },
          cutout: "65%",
        },
      });
    }

    // 2) Alertas (bar)
    const elAlertas = document.getElementById("chartAlertas");
    if (elAlertas && data?.alertas) {
      new Chart(elAlertas, {
        type: "bar",
        data: {
          labels: ["Sem estoque", "Precisa balanço"],
          datasets: [
            { data: [data.alertas.sem_estoque, data.alertas.balanco] },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
      });
    }

    // 3) Pedidos por dia (line)
    const elDias = document.getElementById("chartDias");
    if (elDias && data?.dias?.labels && data?.dias?.values) {
      new Chart(elDias, {
        type: "line",
        data: {
          labels: data.dias.labels,
          datasets: [
            {
              label: "Pedidos",
              data: data.dias.values,
              tension: 0.3,
              pointRadius: 2,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
      });
    }

    // 4) Top produtos (bar horizontal) - Mostrar mais (+10)
    // ✅ Mantido 100% igual ao seu
    const elTop = document.getElementById("chartTopProdutos");
    let chartTop = null;

    let currentLimit = 10;
    const step = 10;
    const maxLimit = 200; // teto no front (ajuste se quiser)

    function renderTop(labels, values) {
      if (!elTop) return;

      // se não tiver dados, limpa e não tenta desenhar
      if (!labels.length) {
        if (chartTop) chartTop.destroy();
        chartTop = null;
        return;
      }

      // dá altura pro CONTAINER do canvas (evita “branco”)
      const base = 260;
      const perItem = 22;
      const target = Math.min(1200, Math.max(base, labels.length * perItem));

      const wrapper = elTop.parentElement; // o card body onde está o canvas
      if (wrapper) wrapper.style.height = `${target}px`;
      elTop.style.display = "block";

      if (chartTop) chartTop.destroy();

      chartTop = new Chart(elTop, {
        type: "bar",
        data: {
          labels,
          datasets: [{ label: "Qtd retirada total", data: values }],
        },
        options: {
          indexAxis: "y",
          responsive: true,
          maintainAspectRatio: false, // precisa do wrapper com height (acima)
          plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (ctx) => ` ${ctx.raw} itens` } },
          },
          scales: {
            x: { beginAtZero: true, ticks: { precision: 0 } },
            y: { ticks: { autoSkip: false } },
          },
        },
      });
    }

    async function fetchTopByLimit(limit) {
      const url2 = `./actions/relatorio_data.php?competencia=${encodeURIComponent(
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

      // antes do "mês selecionado"
      topHeader.insertBefore(wrap, topHeader.lastElementChild);
    }

    // inicializa botões e render inicial com os dados já carregados
    if (elTop) {
      ensureTopButtons();

      const topLabels = data.top_produtos?.labels || [];
      const topValues = data.top_produtos?.values || [];
      renderTop(topLabels, topValues);
    }

    // 5) Solicitantes (bar) + seletor
    const elSol = document.getElementById("chartSolicitantes");
    const sel = document.getElementById("selSolicitante");
    const boxResumo = document.getElementById("solicitanteResumo");

    const solLabels = data.por_solicitante?.labels || [];
    const solPedidos = data.por_solicitante?.pedidos || [];
    const solItens = data.por_solicitante?.itens || [];

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
      }

      let chartSolicitantes = null;

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

        if (chartSolicitantes) chartSolicitantes.destroy();

        chartSolicitantes = new Chart(elSol, {
          type: "bar",
          data: {
            labels,
            datasets: [
              { label: "Pedidos", data: pedidos },
              { label: "Itens entregues", data: itens },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              legend: { position: "bottom" },
              tooltip: {
                callbacks: {
                  label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw}`,
                },
              },
            },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
          },
        });

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

      renderSolicitantes("");
      if (sel)
        sel.addEventListener("change", () => renderSolicitantes(sel.value));
    }
  } catch (e) {
    console.error("Erro no relatorio.js:", e);
  }
})();
