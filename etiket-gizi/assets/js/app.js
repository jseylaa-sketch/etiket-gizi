/* Utilitas bersama: toast, panggilan API (CSRF), modal, animasi angka, ripple, pager tabel */
(function () {
  const APP = window.APP || {};
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  window.$ = $; window.$$ = $$;

  const ICON = {
    ok: '<svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l3 3 5-6"/></svg>',
    err: '<svg class="ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16v.2"/></svg>'
  };
  window.toast = function (msg, type) {
    type = type === 'err' ? 'err' : 'ok';
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = ICON[type] + '<div></div>';
    t.lastChild.textContent = msg;
    $('#toasts').appendChild(t);
    const kill = () => { t.classList.add('out'); setTimeout(() => t.remove(), 350); };
    t.onclick = kill; setTimeout(kill, type === 'err' ? 7000 : 4500);
  };
  window.flash = function (msg, type) { sessionStorage.setItem('flash', JSON.stringify([msg, type || 'ok'])); };
  const f = sessionStorage.getItem('flash');
  if (f) { sessionStorage.removeItem('flash'); try { const [m, t] = JSON.parse(f); setTimeout(() => toast(m, t), 250); } catch (e) {} }

  window.api = async function (path, body, method) {
    method = method || 'POST';
    const opt = { method, headers: { 'X-CSRF-Token': APP.csrf, 'Accept': 'application/json' } };
    if (method !== 'GET') { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body || {}); }
    let r, j;
    try { r = await fetch(APP.base + '/api/' + path, opt); } catch (e) { return { ok: false, pesan: 'Tidak dapat terhubung ke server.' }; }
    try { j = await r.json(); } catch (e) { j = { ok: false, pesan: 'Respons server tidak valid (kode ' + r.status + ').' }; }
    if (r.status === 401) setTimeout(() => location.href = APP.base + '/login.php', 1400);
    return j;
  };
  window.busy = function (btn, on, label) {
    if (!btn) return;
    if (on) { btn._h = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spin"></span><span>' + (label || 'Memproses...') + '</span>'; }
    else { btn.disabled = false; if (btn._h) btn.innerHTML = btn._h; }
  };
  window.esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  window.rupiahMs = v => (v === null || v === undefined || v === '') ? '-' : Number(v).toLocaleString('id-ID', { maximumFractionDigits: 1 });

  /* ambil data SIMRS (dipakai beberapa halaman) */
  window.ambilSimrs = async function (btn) {
    busy(btn, true, 'Mengambil...');
    const j = await api('ambil_simrs.php', {});
    busy(btn, false);
    if (j.ok) { flash(j.pesan + ' (' + j.ms_simrs + ' ms)'); location.reload(); } else toast(j.pesan || 'Gagal mengambil data.', 'err');
  };
  document.addEventListener('click', e => { const b = e.target.closest('[data-simrs]'); if (b) ambilSimrs(b); });

  /* ripple */
  document.addEventListener('pointerdown', e => {
    const b = e.target.closest('.btn'); if (!b || b.disabled) return;
    const r = b.getBoundingClientRect(), d = Math.max(r.width, r.height), s = document.createElement('span');
    s.className = 'ripple'; s.style.cssText = `width:${d}px;height:${d}px;left:${e.clientX - r.left - d / 2}px;top:${e.clientY - r.top - d / 2}px`;
    b.appendChild(s); setTimeout(() => s.remove(), 650);
  });

  /* modal */
  window.openModal = id => { const o = typeof id === 'string' ? $(id) : id; o.classList.add('open'); o.setAttribute('aria-hidden', 'false'); const f = $('select,input:not([type=hidden]),textarea', o); if (f) setTimeout(() => f.focus(), 250); };
  window.closeModal = o => { (o.classList ? o : $(o)).classList.remove('open'); };
  document.addEventListener('click', e => {
    const op = e.target.closest('[data-open]'); if (op) { e.preventDefault(); openModal(op.dataset.open); }
    if (e.target.closest('[data-close]')) closeModal(e.target.closest('.overlay'));
    if (e.target.classList.contains('overlay')) closeModal(e.target);
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') $$('.overlay.open').forEach(closeModal); });

  /* sidebar mobile */
  const bg = $('#burger'), sb = $('#sidebar');
  if (bg) { bg.onclick = () => sb.classList.toggle('open'); document.addEventListener('click', e => { if (sb.classList.contains('open') && !e.target.closest('#sidebar,#burger')) sb.classList.remove('open'); }); }

  /* animasi angka */
  window.countUp = function (el, to, dur) {
    to = Number(to) || 0; dur = dur || 1100;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches || to === 0) { el.textContent = to.toLocaleString('id-ID'); return; }
    const t0 = performance.now();
    const tick = t => { const p = Math.min(1, (t - t0) / dur), v = Math.round(to * (1 - Math.pow(1 - p, 3))); el.textContent = v.toLocaleString('id-ID'); if (p < 1) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
  };
  $$('[data-count]').forEach(el => countUp(el, el.dataset.count));

  /* kontrol segmen */
  window.initSeg = function (seg, onChange) {
    const thumb = $('.thumb', seg), btns = $$('button', seg);
    const place = () => { const on = $('button.on', seg) || btns[0]; thumb.style.left = on.offsetLeft + 'px'; thumb.style.width = on.offsetWidth + 'px'; };
    btns.forEach(b => b.onclick = () => { btns.forEach(x => x.classList.remove('on')); b.classList.add('on'); place(); onChange && onChange(b.dataset.v); });
    place(); window.addEventListener('resize', place); setTimeout(place, 150);
    return { get: () => ($('button.on', seg) || btns[0]).dataset.v };
  };

  /* penyaring + penomoran halaman tabel (sisi klien) */
  window.tablePager = function (o) {
    const tbody = $(o.tbody), rows = $$('tr[data-q]', tbody), per = o.per || 10;
    let page = 1, vis = rows;
    const empty = o.empty && $(o.empty);
    function apply() {
      const q = (o.search && $(o.search) ? $(o.search).value : '').trim().toLowerCase();
      vis = rows.filter(r => {
        if (q && !r.dataset.q.includes(q)) return false;
        for (const [sel, key] of (o.filters || [])) { const v = $(sel).value; if (v && r.dataset[key] !== v) return false; }
        return true;
      });
      page = Math.min(page, Math.max(1, Math.ceil(vis.length / per)));
      render();
    }
    function render() {
      const s = (page - 1) * per, e = s + per;
      rows.forEach(r => r.style.display = 'none');
      vis.slice(s, e).forEach((r, i) => { r.style.display = ''; r.style.animation = 'none'; r.offsetHeight; r.style.animation = `fadeUp .45s var(--ease) ${i * 25}ms both`; });
      if (empty) empty.style.display = vis.length ? 'none' : '';
      const info = o.info && $(o.info); if (info) info.textContent = vis.length ? `Menampilkan ${s + 1}–${Math.min(e, vis.length)} dari ${vis.length} data` : 'Tidak ada data yang cocok';
      const pg = o.pager && $(o.pager); if (!pg) return;
      const n = Math.ceil(vis.length / per); let h = '';
      if (n > 1) {
        h += `<button data-p="${page - 1}" ${page === 1 ? 'disabled' : ''}>‹</button>`;
        for (let i = 1; i <= n; i++) { if (n > 7 && i !== 1 && i !== n && Math.abs(i - page) > 1) { if (i === 2 || i === n - 1) h += '<button disabled>…</button>'; continue; } h += `<button data-p="${i}" class="${i === page ? 'on' : ''}">${i}</button>`; }
        h += `<button data-p="${page + 1}" ${page === n ? 'disabled' : ''}>›</button>`;
      }
      pg.innerHTML = h; $$('button[data-p]', pg).forEach(b => b.onclick = () => { page = +b.dataset.p; render(); });
    }
    if (o.search && $(o.search)) $(o.search).addEventListener('input', () => { page = 1; apply(); });
    (o.filters || []).forEach(([sel]) => $(sel).addEventListener('change', () => { page = 1; apply(); }));
    apply();
    return { apply, rows: () => rows, visible: () => vis };
  };
})();

/* pratinjau stiker (HTML) - isi QR hanya ID pesanan */
window.stikerHTML = function (o) {
  const rs = (window.APP && APP.rs) || '';
  return '<div class="stk"><div class="hd">INSTALASI GIZI | ' + esc((o.rs || rs).toUpperCase()) + '</div>' +
    '<dl><dt>Nama</dt><dd>' + esc(o.nama) + '</dd><dt>No. RM</dt><dd>' + esc(o.rm) + '</dd><dt>Kamar</dt><dd>' + esc(o.kamar) + '</dd><dt>Diet</dt><dd>' + esc(o.diet) + '</dd><dt>Jadwal</dt><dd>' + esc(o.jadwal) + '</dd></dl>' +
    '<div class="qr"><img alt="QR ' + esc(o.id) + '" src="' + APP.base + '/qr.php?id=' + encodeURIComponent(o.id) + '&px=240"><div>' + esc(o.id) + '</div></div>' +
    (o.mend ? '<div class="badge">DIET MENDADAK</div>' : '<span></span>') + '<div class="ft">Pratinjau stiker</div></div>';
};
