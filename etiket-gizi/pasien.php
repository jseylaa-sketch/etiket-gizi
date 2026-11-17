<?php
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/spooler.php';
$u = user();
$rows = q('SELECT p.id_pasien, p.no_rm, p.nama, p.kamar, o.id_pesanan, o.jenis_diet, o.jadwal_makan, o.diet_mendadak, o.status
           FROM pasien p LEFT JOIN pesanan_diet o ON o.id_pasien = p.id_pasien ORDER BY p.no_rm')->fetchAll();
$ruang = []; foreach ($rows as $r) $ruang[explode(' ', $r['kamar'])[0]] = true; ksort($ruang);
$aksi = $u['peran'] === 'ahli_gizi' ? '<button class="btn btn-primary" data-simrs>' . icon('refresh', 17) . ' Ambil Data SIMRS</button>' : '';
page_start('pasien', 'Data Pasien', 'Informasi pasien rawat inap yang terintegrasi dengan SIMRS', ['actions' => $aksi]);
function st_key(array $r): string { return $r['id_pesanan'] ? (((int)$r['diet_mendadak'] && $r['status'] !== 'selesai') ? 'mendadak' : $r['status']) : 'tanpa'; }
?>
<div class="card reveal" style="--d:1">
<?php if (!$rows): ?>
  <div class="empty"><div class="em-ico"><?= icon('users', 34) ?></div><b>Belum ada data pasien</b><p>Data pasien diambil otomatis dari SIMRS, bukan diketik ulang.</p>
  <?php if ($aksi): ?><p style="margin-top:16px"><button class="btn btn-primary btn-lg" data-simrs><?= icon('refresh', 18) ?> Ambil Data SIMRS</button></p><?php endif; ?></div>
<?php else: ?>
  <div class="toolbar">
    <div class="search"><?= icon('search', 18) ?><input class="input" id="q" placeholder="Cari nama atau No. RM..."></div>
    <select class="select" id="fRuang"><option value="">Semua ruang</option><?php foreach (array_keys($ruang) as $k): ?><option value="<?= e(strtolower($k)) ?>"><?= e($k) ?></option><?php endforeach; ?></select>
    <select class="select" id="fSt"><option value="">Semua status</option><option value="mendadak">Mendadak</option><option value="siap_cetak">Siap Cetak</option><option value="diperbarui">Diperbarui</option><option value="dalam_antrean">Dalam Antrean</option><option value="selesai">Stiker Selesai</option></select>
    <span class="spacer"></span><span class="chip"><?= count($rows) ?> pasien</span>
  </div>
  <div class="table-wrap"><table class="tbl"><thead><tr><th style="width:56px">No</th><th>Nama Pasien</th><th>No. RM</th><th>Kamar</th><th>Jenis Diet</th><th>Jadwal</th><th>Status</th><th style="width:150px">Aksi</th></tr></thead>
  <tbody id="tb">
  <?php foreach ($rows as $i => $r): $k = st_key($r); ?>
    <tr data-q="<?= e(strtolower($r['nama'] . ' ' . $r['no_rm'] . ' ' . $r['kamar'] . ' ' . $r['jenis_diet'])) ?>" data-ruang="<?= e(strtolower(explode(' ', $r['kamar'])[0])) ?>" data-st="<?= e($k) ?>" class="<?= $k === 'mendadak' ? 'row-hot' : '' ?>">
      <td><?= $i + 1 ?></td>
      <td class="name"><?= e($r['nama']) ?></td><td class="mono"><?= e($r['no_rm']) ?></td><td><?= e($r['kamar']) ?></td>
      <td><?= $r['id_pesanan'] ? '<span class="chip">' . e($r['jenis_diet']) . '</span>' : '-' ?></td><td><?= e($r['jadwal_makan'] ?? '-') ?></td>
      <td><?= $r['id_pesanan'] ? pill_status_pesanan($r['status'], (bool)$r['diet_mendadak']) : pill('Tanpa pesanan', 'gray') ?></td>
      <td><?php if ($r['id_pesanan']): ?><button class="btn btn-ghost btn-sm btn-edit" data-id="<?= e($r['id_pesanan']) ?>" data-nama="<?= e($r['nama']) ?>" data-rm="<?= e($r['no_rm']) ?>" data-kamar="<?= e($r['kamar']) ?>" data-diet="<?= e($r['jenis_diet']) ?>" data-mend="<?= (int)$r['diet_mendadak'] ?>"><?= icon('edit', 15) ?> Perbarui Diet</button><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <div class="empty" id="kosong" style="display:none"><b>Tidak ada data yang cocok</b>Ubah kata kunci atau filter.</div>
  <div class="pager"><span id="info"></span><div class="pages" id="pg"></div></div>
<?php endif; ?>
</div>

<div class="overlay" id="mDiet" aria-hidden="true"><div class="modal-wrap"><div class="modal">
  <button class="close" data-close aria-label="Tutup"><?= icon('x', 18) ?></button>
  <h3>Perbarui Diet Pasien</h3>
  <div class="pt-chip"><span class="avatar" id="mAv">PC</span><div><b id="mNama"></b><div class="sub" id="mSub" style="color:var(--mut);font-size:12.5px"></div></div></div>
  <div class="field"><label>Diet saat ini</label><input class="input" id="mLama" disabled></div>
  <div class="field"><label for="mDietBaru">Diet baru</label><select class="select" id="mDietBaru"><?php foreach (DAFTAR_DIET as $d): ?><option><?= e($d) ?></option><?php endforeach; ?></select></div>
  <div class="switch-row"><label class="switch"><input type="checkbox" id="mMend"><span></span></label><div><b>Perubahan mendadak</b><small>Stiker dibuat dengan prioritas tinggi (lebih dahulu dalam antrean)</small></div></div>
  <div class="field"><label for="mCat">Catatan (opsional)</label><textarea class="textarea" id="mCat" maxlength="250" placeholder="Contoh: pasien boleh makan lunak sejak siang"></textarea></div>
  <div class="modal-foot"><button class="btn btn-ghost" data-close>Batal</button><button class="btn btn-primary" id="mSimpan"><?= icon('check', 17) ?> Simpan Perubahan</button></div>
</div></div></div>
<?php page_end($rows ? ['pasien.js'] : []);
