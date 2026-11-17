(function () {
  if (!window.DASH || !window.Chart) return;
  const D = window.DASH, css = getComputedStyle(document.documentElement);
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.font.weight = 600; Chart.defaults.color = '#68807A';
  const grad = (ctx, c1, c2) => { const g = ctx.createLinearGradient(0, 0, 0, 260); g.addColorStop(0, c1); g.addColorStop(1, c2); return g; };

  const el = document.getElementById('cJam');
  if (el) {
    const ctx = el.getContext('2d');
    new Chart(el, { type: 'line', data: { labels: D.jam, datasets: [
      { label: 'Normal', data: D.normal, borderColor: '#10966F', backgroundColor: grad(ctx, 'rgba(16,150,111,.28)', 'rgba(16,150,111,0)'), fill: true, tension: .4, borderWidth: 3, pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderWidth: 2 },
      { label: 'Prioritas tinggi', data: D.tinggi, borderColor: '#E5484D', backgroundColor: grad(ctx, 'rgba(229,72,77,.2)', 'rgba(229,72,77,0)'), fill: true, tension: .4, borderWidth: 3, pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderWidth: 2 }
    ] }, options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, animation: { duration: 1400, easing: 'easeOutQuart' },
      plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EEF3F1' }, border: { display: false } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } } } } });
  }
  const d = document.getElementById('cDist');
  if (d) new Chart(d, { type: 'doughnut', data: { labels: ['Belum didistribusi', 'Diporsi', 'Dalam pengiriman', 'Diterima pasien'], datasets: [{ data: D.dist, backgroundColor: ['#CBD7D3', '#6FD6B2', '#F2A93B', '#10966F'], borderWidth: 4, borderColor: '#fff', hoverOffset: 8 }] },
    options: { cutout: '74%', animation: { animateRotate: true, duration: 1500 }, plugins: { legend: { display: false } } } });
  const b = document.getElementById('cDiet');
  if (b) { const ctx = b.getContext('2d');
    new Chart(b, { type: 'bar', data: { labels: D.diet, datasets: [{ data: D.dietn, borderRadius: 10, borderSkipped: false, backgroundColor: ['#10966F', '#25B788', '#6FD6B2', '#2B63C4', '#F2A93B', '#8C6AE0'] }] },
      options: { indexAxis: 'y', maintainAspectRatio: false, animation: { duration: 1200 }, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EEF3F1' }, border: { display: false } }, y: { grid: { display: false }, border: { display: false } } } } }); }
})();
