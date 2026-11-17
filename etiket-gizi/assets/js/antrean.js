(function () {
  const tb = $('#tb'); let ada = 0, sibuk = false, timer = null;
  const PILL = { antre: ['Menunggu', 'orange', ''], diproses: ['Diproses', 'blue', ' pulse'], selesai: ['Selesai', 'green', ''], gagal: ['Gagal', 'red', ''] };
  const pill = (t, k, p) => `<span class="pill pill-${k}${p || ''}">${esc(t)}</span>`;
  function render(j) {
    const h = j.hitung, total = h.total || 0, sel = (h.selesai || 0) + (h.gagal || 0);
    ada = (h.antre || 0) + (h.diproses || 0);
    $('#kosong').style.display = total ? 'none' : ''; $('#tw').style.display = total ? '' : 'none';
    const pct = total ? Math.round(sel / total * 100) : 0;
    $('#progTitle').textContent = total ? `Memproses ${sel} dari ${total} stiker` : 'Tidak ada pekerjaan';
    $('#progPct').textContent = pct + '%';
    $('#prog i').style.width = pct + '%'; $('#prog').classList.toggle('done', ada === 0);
    $('#cAntre').textContent = h.antre || 0; $('#cProses').textContent = h.diproses || 0; $('#cSelesai').textContent = h.selesai || 0; $('#cGagal').textContent = h.gagal || 0;
    const w = $('#wdot'), t = $('#wtxt');
    if (j.worker_aktif) { w.style.background = '#10966F'; w.style.animation = 'ping 1.4s infinite'; t.textContent = 'Pekerja aktif'; }
    else if (ada > 0) { w.style.background = '#F2A93B'; w.style.animation = 'none'; t.textContent = j.mode_worker === 'poll' ? 'Mode polling' : 'Menunggu pekerja'; }
    else { w.style.background = '#B6C6C0'; w.style.animation = 'none'; t.textContent = 'Pekerja siaga'; }
    let n = 0;
    tb.innerHTML = j.rows.map(r => {
      const wait = r.status === 'antre' || r.status === 'diproses'; if (wait) n++;
      const p = PILL[r.status] || [r.status, 'gray', ''];
      return `<tr class="${r.prioritas == 1 && wait ? 'row-hot' : ''}"><td>${wait ? n : '–'}</td><td class="mono"><b>${esc(r.id_pesanan)}</b></td>
        <td><div class="name">${esc(r.nama)}</div><div class="sub">${esc(r.kamar)}</div></td><td><span class="chip">${esc(r.jenis_diet)}</span></td>
        <td>${r.prioritas == 1 ? pill('Tinggi', 'red') : pill('Normal', 'gray')}</td><td>${pill(r.mode === 'langsung' ? 'Langsung' : 'Spooling', 'gray')}</td>
        <td class="mono">${esc(r.waktu_masuk_fmt)}</td><td>${pill(p[0], p[1], p[2])}</td>
        <td>${r.status === 'selesai' ? `<a class="btn btn-soft btn-sm" target="_blank" href="${APP.base}/unduh_stiker.php?id=${encodeURIComponent(r.id_pesanan)}">PDF</a>` : ''}</td></tr>`;
    }).join('');
  }
  async function tick() {
    if (sibuk) return; sibuk = true;
    if (APP.workerMode === 'poll' && ada > 0) await api('worker_tick.php', {});
    const j = await api('status_antrean.php', null, 'GET'); sibuk = false;
    if (j.ok) render(j); else if (j.pesan) { toast(j.pesan, 'err'); clearInterval(timer); }
  }
  tick(); timer = setInterval(() => { if (!document.hidden) tick(); }, 1000);
  $('#btnBersih').onclick = async function () { busy(this, true, 'Membersihkan...'); const j = await api('clear_antrean.php', {}); busy(this, false); toast(j.pesan, j.ok ? 'ok' : 'err'); tick(); };
})();
