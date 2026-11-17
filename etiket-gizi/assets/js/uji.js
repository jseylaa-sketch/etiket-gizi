(function () {
  const NAMA = { langsung: 'Langsung', spooling_fcfs: 'Spooling FCFS', spooling_prioritas: 'Spooling Prioritas' };
  const WARNA = { langsung: '#F2A93B', spooling_fcfs: '#25B788', spooling_prioritas: '#0E7C63' };
  const fmt = v => (v === null || v === undefined) ? '–' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 1 });
  let chart = null, jalan = false;
  async function muat() {
    const j = await api('uji_data.php', null, 'GET'); if (!j.ok) return;
    $('#nRun').textContent = j.rows.length + ' data';
    $('#tAgg').innerHTML = j.agg.length ? j.agg.map(a => `<tr><td><b>${NAMA[a.skenario]}</b></td><td>${a.jumlah_stiker}</td><td>${a.n}</td><td><b>${fmt(a.respons)}</b> <span class="sub">(${fmt(a.rmin)}–${fmt(a.rmax)})</span></td><td>${fmt(a.total)}</td><td>${fmt(a.tunggu)}</td></tr>`).join('')
      : '<tr><td colspan="6" class="sub" style="text-align:center;padding:26px">Belum ada hasil uji.</td></tr>';
    $('#tRaw').innerHTML = j.rows.map(r => `<tr><td>${r.id_uji}</td><td class="mono">${esc(r.kelompok)}</td><td>${NAMA[r.skenario]}</td><td>${r.jumlah_stiker}</td><td>${r.ulangan_ke}</td><td>${fmt(r.waktu_respons_ms)}</td><td>${fmt(r.waktu_total_ms)}</td><td>${fmt(r.waktu_tunggu_mendadak_ms)}</td></tr>`).join('');
    grafik(j.agg);
  }
  function grafik(agg) {
    if (!window.Chart) return;
    const maks = agg.length ? Math.max(...agg.map(a => +a.jumlah_stiker)) : 0, g = agg.filter(a => +a.jumlah_stiker === maks);
    const labels = g.map(a => NAMA[a.skenario]);
    const data = { labels, datasets: [
      { label: 'Respons halaman', data: g.map(a => +(+a.respons).toFixed(1)), backgroundColor: g.map(a => WARNA[a.skenario]), borderRadius: 10 },
      { label: 'Total sampai selesai', data: g.map(a => a.total === null ? 0 : +(+a.total).toFixed(1)), backgroundColor: g.map(a => WARNA[a.skenario] + '55'), borderRadius: 10 } ] };
    if (chart) { chart.data = data; chart.update(); } else {
      Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif"; Chart.defaults.font.weight = 600; Chart.defaults.color = '#68807A';
      chart = new Chart($('#cUji'), { type: 'bar', data, options: { maintainAspectRatio: false, animation: { duration: 900 }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }, scales: { y: { beginAtZero: true, grid: { color: '#EEF3F1' }, border: { display: false }, title: { display: true, text: 'ms' } }, x: { grid: { display: false } } } } });
    }
    const L = g.find(a => a.skenario === 'langsung'), S = g.find(a => a.skenario === 'spooling_prioritas') || g.find(a => a.skenario === 'spooling_fcfs'), v = $('#verdict');
    if (L && S && +L.respons > 0) {
      const d = (1 - S.respons / L.respons) * 100;
      v.style.display = ''; v.className = 'callout' + (d >= 0 ? '' : ' warn');
      v.innerHTML = `<div>Pada uji ini (${maks} stiker), waktu respons halaman mode spooling <b>${fmt(S.respons)} ms</b> dibanding <b>${fmt(L.respons)} ms</b> pada mode langsung (${d >= 0 ? 'lebih singkat' : 'lebih lama'} <b>${fmt(Math.abs(d))}%</b>).</div>`;
    } else v.style.display = 'none';
  }
  const sleep = ms => new Promise(r => setTimeout(r, ms));
  $('#btnUji').onclick = async function () {
    if (jalan) return;
    const sk = $$('input.check[type=checkbox]').filter(c => c.checked && NAMA[c.value]).map(c => c.value);
    const N = +$('#jml').value, R = Math.max(1, Math.min(30, +$('#ulang').value || 1));
    if (!sk.length) { toast('Pilih minimal satu skenario.', 'err'); return; }
    jalan = true; busy(this, true, 'Menjalankan uji...'); $('#prog').style.display = '';
    const total = sk.length * R, kel = new Date().toISOString().slice(2, 19).replace(/[-:T]/g, '').replace(/^(\d{6})(\d{6})$/, '$1-$2');
    let k = 0, gagal = false;
    for (let u = 1; u <= R && !gagal; u++) for (const s of sk) {
      $('#progTxt').textContent = `Ulangan ${u}/${R} · ${NAMA[s]}`;
      const j = await api('uji_jalankan.php', { skenario: s, jumlah: N, ulangan_ke: u, kelompok: kel });
      if (!j.ok) { toast(j.pesan || 'Uji gagal.', 'err'); gagal = true; break; }
      if (!j.selesai) { for (let t = 0; t < 600; t++) { const st = await api('uji_status.php?id=' + j.id, null, 'GET'); if (!st.ok) { gagal = true; toast(st.pesan, 'err'); break; } if (st.selesai) break; await sleep(200); } }
      if (gagal) break;
      k++; const pct = Math.round(k / total * 100); $('#progBar').style.width = pct + '%'; $('#progPct').textContent = pct + '%';
      await muat();
    }
    jalan = false; busy(this, false);
    if (!gagal) toast(`Uji selesai: ${k} percobaan tercatat.`);
  };
  $('#btnHapus').onclick = async function () { if (!confirm('Hapus seluruh hasil uji?')) return; const j = await api('uji_hapus.php', {}); toast(j.pesan, j.ok ? 'ok' : 'err'); muat(); };
  muat();
})();
