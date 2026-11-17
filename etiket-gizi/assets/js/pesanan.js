(function () {
  if (!$('#tb')) return;
  const tp = tablePager({ tbody: '#tb', search: '#q', filters: [['#fSt', 'st']], per: 12, info: '#info', pager: '#pg', empty: '#kosong' });
  const picks = () => $$('.pick', $('#tb')), chosen = () => picks().filter(c => c.checked).map(c => c.value);
  function upd() {
    const n = chosen().length, b = $('#btnKirim');
    if ($('#selInfo')) $('#selInfo').textContent = n ? n + ' pesanan dipilih' : '';
    if (b) { b.disabled = n === 0; $('span', b).textContent = n ? 'Kirim ' + n + ' ke Antrean Cetak' : 'Kirim ke Antrean Cetak'; }
  }
  document.addEventListener('change', e => {
    if (e.target.id === 'all') { tp.visible().forEach(r => { const c = $('.pick', r); if (c) c.checked = e.target.checked; }); }
    if (e.target.classList.contains('pick') || e.target.id === 'all') upd();
  });
  const kirim = $('#btnKirim');
  if (kirim) kirim.onclick = async () => {
    busy(kirim, true, 'Mengirim...');
    const j = await api('kirim_antrean.php', { ids: chosen(), mode: 'spooling' });
    if (j.ok) { flash(j.pesan); location.href = APP.base + '/antrean.php'; } else { busy(kirim, false); toast(j.pesan || 'Gagal mengirim.', 'err'); }
  };
  document.addEventListener('click', e => {
    const b = e.target.closest('.btn-lihat'); if (!b) return;
    const d = b.dataset; $('#stkBox').innerHTML = stikerHTML({ id: d.id, nama: d.nama, rm: d.rm, kamar: d.kamar, diet: d.diet, jadwal: d.jadwal, mend: d.mend === '1' });
    const a = $('#stkPdf'); if (d.pdf === '1') { a.href = APP.base + '/unduh_stiker.php?id=' + encodeURIComponent(d.id); a.style.display = ''; } else a.style.display = 'none';
    openModal('#mStk');
  });
})();
