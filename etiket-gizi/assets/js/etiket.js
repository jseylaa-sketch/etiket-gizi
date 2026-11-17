(function () {
  const sel = $('#sel'); if (!sel) return;
  const prev = () => {
    const o = sel.selectedOptions[0], d = o.dataset;
    $('#prev').innerHTML = stikerHTML({ id: o.value, nama: d.nama, rm: d.rm, kamar: d.kamar, diet: d.diet, jadwal: d.jadwal, mend: d.mend === '1' });
    $('#lnkPdf').style.display = 'none';
  };
  sel.onchange = prev; prev();
  $('#btnSatu').onclick = async function () {
    busy(this, true, 'Membuat...');
    const j = await api('kirim_antrean.php', { ids: [sel.value], mode: 'langsung' });
    busy(this, false);
    if (j.ok && j.jumlah) { toast('Stiker ' + sel.value + ' dibuat (' + j.ms + ' ms).'); const a = $('#lnkPdf'); a.href = APP.base + '/unduh_stiker.php?id=' + encodeURIComponent(sel.value) + '&t=' + Date.now(); a.style.display = ''; }
    else toast(j.pesan || 'Gagal membuat stiker.', 'err');
  };
  let mode = 'spooling';
  const note = () => $('#modeNote').innerHTML = mode === 'spooling'
    ? '<svg class="ico" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.2"/></svg><div><b>Spooling.</b> Pekerjaan dimasukkan ke antrean, halaman langsung merespons, dan stiker dibuat di latar belakang menurut prioritas lalu urutan masuk (FCFS).</div>'
    : '<svg class="ico" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.2"/></svg><div><b>Langsung.</b> Halaman menunggu sampai seluruh stiker selesai dibuat. Ini pembanding untuk mode spooling.</div>';
  initSeg($('#seg'), v => { mode = v; note(); }); note();
  $('#btnMassal').onclick = async function () {
    const btn = this; busy(btn, true, mode === 'langsung' ? 'Membuat stiker...' : 'Mengirim ke antrean...');
    const j = await api('kirim_antrean.php', { ids: window.SIAP, mode });
    busy(btn, false);
    const box = $('#hasil');
    if (!j.ok) { toast(j.pesan || 'Gagal.', 'err'); return; }
    box.innerHTML = '<div style="color:var(--mut);font-weight:700;text-transform:uppercase;font-size:11.5px;letter-spacing:.6px">Waktu respons halaman</div><div class="metric"><b id="ms">0</b><span>ms</span></div><div style="color:var(--mut);font-weight:600">' + j.jumlah + ' pekerjaan · mode ' + (j.mode === 'langsung' ? 'langsung (halaman menunggu)' : 'spooling (dikerjakan di latar belakang)') + '</div>' +
      (j.mode === 'spooling' ? '<div style="margin-top:14px"><a class="btn btn-primary btn-sm" href="' + APP.base + '/antrean.php">Lihat Antrean ' + '<svg class="ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>' : '<div style="margin-top:14px"><a class="btn btn-soft btn-sm" href="' + APP.base + '/pesanan.php">Lihat Pesanan</a></div>');
    countUp($('#ms'), j.ms, 900);
    toast(j.pesan);
  };
})();
