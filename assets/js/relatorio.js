(async () => {
  try {
    const root = document.documentElement;
    const competencia = root?.dataset?.competencia;
    if (!competencia) return;

    // ✅ nome correto do arquivo
    const url = `actions/relatorio_dados.php?competencia=${encodeURIComponent(
      competencia
    )}`;

    const res = await fetch(url, { credentials: "same-origin" });

    // ✅ se der 403/404/500, mostra no console e para
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
    const elStatus = document.getElementById("chartStatus");
    if (elStatus && data?.status) {
      new Chart(elStatus, {
        type: "doughnut",
        data: {
          labels: ["Finalizados", "Pendentes"],
          datasets: [
            { data: [data.status.finalizados, data.status.pendentes] },
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

    // 4) Top 10 produtos (bar horizontal)
    const elTop = document.getElementById("chartTopProdutos");
    const topLabels = data.top_produtos?.labels || [];
    const topValues = data.top_produtos?.values || [];

    if (elTop && topLabels.length) {
      new Chart(elTop, {
        type: "bar",
        data: {
          labels: topLabels,
          datasets: [{ label: "Qtd solicitada", data: topValues }],
        },
        options: {
          indexAxis: "y",
          responsive: true,
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
      return;
    }

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
            { label: "Itens solicitados", data: itens },
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
          } pedidos | ${solItens[idx] || 0} itens solicitados`;
        }
      }
    }

    renderSolicitantes("");
    if (sel)
      sel.addEventListener("change", () => renderSolicitantes(sel.value));
  } catch (e) {
    console.error("Erro no relatorio.js:", e);
  }
})();
