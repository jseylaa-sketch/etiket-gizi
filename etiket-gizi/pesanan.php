<?php
require __DIR__ . '/includes/layout.php';
$u = user(); $gizi = $u['peran'] === 'ahli_gizi';
$rows = q("SELECT o.id_pesanan, o.jenis_diet, o.jadwal_makan, o.diet_mendadak, o.status, o.waktu_ubah, p.nama, p.no_rm, p.kamar, e.status_cetak
           FROM pesanan_diet o JOIN pasien p ON p.id_pasien=o.id_pasien LEFT JOIN etiket e ON e.id_pesanan=o.id_pesanan
           ORDER BY o.diet_mendadak DESC, o.id_pesanan")->fetchAll();
$awal = ($_GET['filter'] ?? '') === 'mendadak' ? 'mendadak' : '';
$kirim = $gizi ? '<button class="btn btn-primary" id="btnKirim" disabled>' . icon('printer', 17) . ' <span>Kirim ke Antrean Cetak</span></button>' : '';
page_start('pesanan', 'Pesanan Diet', 'Kelola pesanan makanan pasien dan kirim ke antrean pembuatan stiker', ['actions' => $kirim]);
function st_p(array $r): string { return ((int)$r['diet_mendadak'] && $r['status'] !== 'selesai') ? 'mendadak' : $r['status']; }
?>
<div class="card reveal" style="--d:1">
<?php if (!$rows): ?>
  <div class="empty"><div class="em-ico"><?= icon('utensils', 34) ?></div><b>Belum ada pesanan diet</b><p>Ambil data pasien dari SIMRS terlebih dahulu.</p><?php if ($gizi): ?><p style="margin-top:16px"><button class="btn btn-primary btn-lg" data-simrs><?= icon('refresh', 18) ?> Ambil Data SIMRS</button></p><?php endif; ?></div>
<?php else: ?>
  <div class="toolbar">
    <div class="search"><?= icon('search', 18) ?><input class="input" id="q" placeholder="Cari ID pesanan atau pasien..."></div>
    <select class="select" id="fSt"><option value="">Semua status</option>
      <?php foreach (['mendadak' => 'Mendadak', 'siap_cetak' => 'Siap Cetak', 'diperbarui' => 'Diperbarui', 'dalam_antrean' => 'Dalam Antrean', 'selesai' => 'Stiker Selesai'] as $v => $l): ?><option value="<?= $v ?>" <?= $awal === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <span class="spacer"></span><span class="tabs-note" id="selInfo"></span>
  </div>
  <div class="table-wrap"><table class="tbl"><thead><tr>
    <?php if ($gizi): ?><th style="width:44px"><input type="checkbox" class="check" id="all" title="Pilih semua yang siap dicetak"></th><?php endif; ?>
    <th>ID Pesanan</th><th>Pasien</th><th>Jenis Diet</th><th>Jadwal</th><th>Prioritas</th><th>Status</th><th style="width:120px">Stiker</th></tr></thead>
  <tbody id="tb">
  <?php foreach ($rows as $r): $k = st_p($r); $bisa = in_array($r['status'], ['baru', 'diperbarui', 'siap_cetak'], true); ?>
    <tr data-q="<?= e(strtolower($r['id_pesanan'] . ' ' . $r['nama'] . ' ' . $r['no_rm'] . ' ' . $r['kamar'])) ?>" data-st="<?= e($k) ?>" data-bisa="<?= $bisa ? 1 : 0 ?>" class="<?= $k === 'mendadak' ? 'row-hot' : '' ?>">
      <?php if ($gizi): ?><td><?php if ($bisa): ?><input type="checkbox" class="check pick" value="<?= e($r['id_pesanan']) ?>"><?php endif; ?></td><?php endif; ?>
      <td class="mono"><b><?= e($r['id_pesanan']) ?></b></td>
      <td><div class="name"><?= e($r['nama']) ?></div><div class="sub"><?= e($r['no_rm']) ?> · <?= e($r['kamar']) ?></div></td>
      <td><span class="chip"><?= e($r['jenis_diet']) ?></span></td><td><?= e($r['jadwal_makan']) ?></td>
      <td><?= pill_prioritas((int)$r['diet_mendadak'] ? 1 : 2) ?></td><td><?= pill_status_pesanan($r['status'], (bool)$r['diet_mendadak']) ?></td>
      <td><button class="btn btn-soft btn-sm btn-lihat" data-id="<?= e($r['id_pesanan']) ?>" data-nama="<?= e($r['nama']) ?>" data-rm="<?= e($r['no_rm']) ?>" data-kamar="<?= e($r['kamar']) ?>" data-diet="<?= e($r['jenis_diet']) ?>" data-jadwal="<?= e($r['jadwal_makan']) ?>" data-mend="<?= (int)$r['diet_mendadak'] ?>" data-pdf="<?= $r['status_cetak'] === 'dibuat' ? 1 : 0 ?>"><?= icon('qr', 15) ?> Lihat</button></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <div class="empty" id="kosong" style="display:none"><b>Tidak ada data yang cocok</b>Ubah kata kunci atau filter.</div>
  <div class="pager"><span id="info"></span><div class="pages" id="pg"></div></div>
<?php endif; ?>
</div>
<div class="overlay" id="mStk" aria-hidden="true"><div class="modal-wrap" style="max-width:540px"><div class="modal">
  <button class="close" data-close aria-label="Tutup"><?= icon('x', 18) ?></button>
  <h3>Pratinjau Stiker</h3><p style="color:var(--mut);margin:2px 0 18px">QR Code hanya berisi ID pesanan.</p>
  <div id="stkBox" style="display:grid;place-items:center"></div>
  <div class="modal-foot" style="margin-top:20px"><a class="btn btn-soft" id="stkPdf" target="_blank" style="display:none"><?= icon('file', 17) ?> Buka PDF</a><button class="btn btn-ghost" data-close>Tutup</button></div>
</div></div></div>
<?php page_end($rows ? ['pesanan.js'] : []);
