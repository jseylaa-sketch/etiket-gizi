(function () {
  const TAHAP = ['diporsi', 'dalam_pengiriman', 'diterima'], LABEL = { diporsi: 'Diporsi', dalam_pengiriman: 'Dalam Pengiriman', diterima: 'Diterima Pasien' };
  let cur = null, stream = null, raf = null, lastCode = '', lastAt = 0;
  async function lookup(id, catat) {
    const j = await api('scan.php', { id_pesanan: id, aksi: catat ? 'catat' : 'cari' });
    if (!j.ok) { toast(j.pesan || 'Gagal.', 'err'); return; }
    cur = j; draw(j); if (catat) toast(j.pesan);
  }
  function draw(j) {
    const p = j.pesanan;
    $('#stEmpty').style.display = 'none'; $('#stBody').style.display = '';
    $('#kId').textContent = p.id; $('#kNama').textContent = p.nama; $('#kKamar').textContent = p.kamar; $('#kDiet').textContent = p.diet;
    $('#stSub').textContent = p.no_rm + ' · ' + p.jadwal;
    $('#stPill').innerHTML = p.mendadak ? '<span class="pill pill-red pulse">Mendadak</span>' : '<span class="pill pill-gray">Normal</span>';
    const last = j.tahap_terakhir ? TAHAP.indexOf(j.tahap_terakhir) : -1;
    const wk = {}; (j.riwayat || []).forEach(r => wk[r.tahap] = r.jam);
    $('#stepper').innerHTML = TAHAP.map((t, i) => `<div class="step ${i <= last ? 'done' : (i === last + 1 ? 'now' : '')}"><div class="dot">${i <= last ? '<svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>' : (i + 1)}</div>${LABEL[t]}<small>${wk[t] ? wk[t] + ' WIB' : '–'}</small></div>`).join('');
    $('#warnStk').style.display = p.stiker_siap ? 'none' : '';
    const b = $('#btnCatat'); b.disabled = !p.stiker_siap || !j.tahap_berikut; $('span', b).textContent = j.tahap_berikut ? 'Catat: ' + j.tahap_berikut_label : 'Sudah diterima pasien';
    $('#tl').innerHTML = (j.riwayat || []).slice().reverse().map(r => `<li><span class="tdot"><svg class="ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></span><div><b>${esc(r.label)}</b><small>${esc(r.jam)} · ${esc(r.petugas || '-')}</small></div></li>`).join('');
    $('#stBody').animate([{ opacity: .4, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }], { duration: 400, easing: 'ease-out' });
  }
  $('#btnCatat').onclick = async function () { busy(this, true, 'Mencatat...'); await lookup(cur.pesanan.id, true); busy(this, false); if (cur) draw(cur); setTimeout(() => location.reload(), 1600); };
  $('#btnCari').onclick = () => { const v = $('#manual').value.trim(); if (v) lookup(v, false); };
  $('#manual').addEventListener('keydown', e => { if (e.key === 'Enter') $('#btnCari').click(); });
  document.addEventListener('click', e => { const b = e.target.closest('.pilih'); if (b) { $('#manual').value = b.dataset.id; lookup(b.dataset.id, false); window.scrollTo({ top: 0, behavior: 'smooth' }); } });

  /* kamera + jsQR */
  const vid = $('#vid'), cv = $('#cv'), ctx = cv.getContext('2d', { willReadFrequently: true });
  function loop() {
    if (!stream) return;
    if (vid.readyState >= 2) {
      cv.width = vid.videoWidth; cv.height = vid.videoHeight; ctx.drawImage(vid, 0, 0);
      const img = ctx.getImageData(0, 0, cv.width, cv.height), c = window.jsQR && jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
      if (c && c.data) { const now = Date.now(); if (c.data !== lastCode || now - lastAt > 3500) { lastCode = c.data; lastAt = now; $('#manual').value = c.data; lookup(c.data, false); } }
    }
    raf = setTimeout(() => requestAnimationFrame(loop), 120);
  }
  function stopCam() { if (stream) stream.getTracks().forEach(t => t.stop()); stream = null; clearTimeout(raf); $('#scan').classList.remove('live'); $('#btnCam').innerHTML = $('#btnCam')._h; }
  $('#btnCam').onclick = async function () {
    const b = this; b._h = b._h || b.innerHTML;
    if (stream) { stopCam(); return; }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      vid.srcObject = stream; await vid.play(); $('#scan').classList.add('live'); b.textContent = 'Hentikan Kamera'; loop();
    } catch (err) { stream = null; toast('Kamera tidak dapat dibuka. Izinkan akses kamera (gunakan localhost/HTTPS) atau pakai input manual.', 'err'); }
  };
  window.addEventListener('beforeunload', stopCam);
})();
