(async () => {
  const root = document.documentElement;
  const competencia = root?.dataset?.competencia;
  if (!competencia) return;

  const res = await fetch(
    `actions/relatorio_data.php?competencia=${encodeURIComponent(competencia)}`
  );
  const data = await res.json();
  if (data?.error) return;

  // 1) Status (doughnut)
  const elStatus = document.getElementById("chartStatus");
  if (elStatus) {
    new Chart(elStatus, {
      type: "doughnut",
      data: {
        labels: ["Finalizados", "Pendentes"],
        datasets: [{ data: [data.status.finalizados, data.status.pendentes] }],
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
  if (elAlertas) {
    new Chart(elAlertas, {
      type: "bar",
      data: {
        labels: ["Sem estoque", "Precisa balanço"],
        datasets: [{ data: [data.alertas.sem_estoque, data.alertas.balanco] }],
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
  if (elDias) {
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
})();
