(function () {
  if (!$('#tb')) return;
  tablePager({ tbody: '#tb', search: '#q', filters: [['#fRuang', 'ruang'], ['#fSt', 'st']], per: 10, info: '#info', pager: '#pg', empty: '#kosong' });
  let cur = null;
  document.addEventListener('click', e => {
    const b = e.target.closest('.btn-edit'); if (!b) return;
    cur = b.dataset;
    $('#mAv').textContent = (cur.nama.match(/\b\w/g) || []).slice(0, 2).join('').toUpperCase();
    $('#mNama').textContent = cur.nama; $('#mSub').textContent = cur.rm + ' · ' + cur.kamar;
    $('#mLama').value = cur.diet; $('#mDietBaru').value = cur.diet; $('#mMend').checked = cur.mend === '1'; $('#mCat').value = '';
    openModal('#mDiet');
  });
  $('#mSimpan').onclick = async function () {
    const btn = this; busy(btn, true, 'Menyimpan...');
    const j = await api('perbarui_diet.php', { id_pesanan: cur.id, jenis_diet: $('#mDietBaru').value, mendadak: $('#mMend').checked, catatan: $('#mCat').value });
    busy(btn, false);
    if (j.ok) { flash(j.pesan); location.reload(); } else toast(j.pesan || 'Gagal menyimpan.', 'err');
  };
})();
