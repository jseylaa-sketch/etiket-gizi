<?php
require __DIR__ . '/includes/layout.php';
$siap = q("SELECT o.id_pesanan, o.diet_mendadak, o.jenis_diet, p.nama, p.kamar,
             (SELECT tahap FROM status_distribusi s WHERE s.id_pesanan=o.id_pesanan ORDER BY s.id_status DESC LIMIT 1) tahap
           FROM pesanan_diet o JOIN pasien p ON p.id_pasien=o.id_pasien JOIN etiket e ON e.id_pesanan=o.id_pesanan
           WHERE e.status_cetak='dibuat' HAVING tahap IS NULL OR tahap <> 'diterima' ORDER BY o.diet_mendadak DESC, o.id_pesanan LIMIT 10")->fetchAll();
$riw = q("SELECT s.id_pesanan, s.tahap, s.waktu, u.nama petugas, p.nama pasien FROM status_distribusi s JOIN pesanan_diet o ON o.id_pesanan=s.id_pesanan JOIN pasien p ON p.id_pasien=o.id_pasien LEFT JOIN pengguna u ON u.id_pengguna=s.id_pengguna ORDER BY s.id_status DESC LIMIT 8")->fetchAll();
page_start('distribusi', 'Distribusi dan Pindai QR', 'Pantau status distribusi makanan: diporsi, dalam pengiriman, diterima pasien');
?>
<div class="grid g2">
  <div class="card reveal" style="--d:1"><div class="card-head"><div><h2>Pindai QR Code</h2><small>Arahkan kamera ke QR Code pada stiker</small></div></div>
    <div class="scan-box" id="scan"><video id="vid" playsinline muted></video><canvas id="cv"></canvas><i class="corner c1"></i><i class="corner c2"></i><i class="corner c3"></i><i class="corner c4"></i><span class="line"></span>
      <div class="hint"><?= icon('camera', 42) ?><div style="margin-top:8px">Kamera belum aktif</div></div></div>
    <div style="display:flex;justify-content:center;margin:16px 0 4px"><button class="btn btn-primary" id="btnCam"><?= icon('camera', 17) ?> Mulai Pindai Kamera</button></div>
    <div style="text-align:center;color:var(--mut);font-weight:600;font-size:12.5px;margin:6px 0 10px">atau masukkan ID pesanan secara manual</div>
    <div style="display:flex;gap:10px"><input class="input mono" id="manual" placeholder="ETK-20261022-0001" maxlength="25"><button class="btn btn-soft" id="btnCari"><?= icon('search', 16) ?> Cari</button></div>
  </div>
  <div class="card reveal" style="--d:2"><div class="card-head"><div><h2>Status Distribusi</h2><small id="stSub">Belum ada pesanan dipindai</small></div><span id="stPill"></span></div>
    <div id="stEmpty" class="empty" style="padding:30px 10px"><div class="em-ico"><?= icon('qr', 34) ?></div><b>Menunggu pemindaian</b>Pindai stiker atau pilih pesanan dari daftar di bawah.</div>
    <div id="stBody" style="display:none">
      <div class="kv"><div><label>ID Pesanan</label><b class="mono" id="kId"></b></div><div><label>Pasien</label><b id="kNama"></b></div><div><label>Kamar</label><b id="kKamar"></b></div><div><label>Jenis Diet</label><b id="kDiet"></b></div></div>
      <div class="stepper" id="stepper"></div>
      <div id="warnStk" class="callout warn" style="display:none;margin-top:16px"><?= icon('alert', 19) ?><div>Stiker pesanan ini belum dibuat atau sudah usang karena diet berubah.</div></div>
      <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-primary" id="btnCatat"><?= icon('check', 17) ?> <span>Catat</span></button></div>
      <ul class="timeline" id="tl" style="margin-top:20px"></ul>
    </div></div>
</div>
<div class="grid g-3-2 mt">
  <div class="card reveal" style="--d:3"><div class="card-head"><div><h2>Siap Didistribusikan</h2><small>Stiker sudah dibuat · klik untuk memilih tanpa kamera</small></div></div>
  <?php if (!$siap): ?><div class="empty" style="padding:26px"><b>Belum ada stiker yang dibuat</b>Buat stiker terlebih dahulu di halaman Pembuatan Etiket.</div><?php else: ?>
    <div class="table-wrap"><table class="tbl" style="min-width:520px"><tbody><?php foreach ($siap as $s): ?>
      <tr><td class="mono"><b><?= e($s['id_pesanan']) ?></b></td><td><div class="name"><?= e($s['nama']) ?></div><div class="sub"><?= e($s['kamar']) ?> · <?= e($s['jenis_diet']) ?></div></td>
        <td><?= $s['tahap'] ? pill(TAHAP[$s['tahap']], 'teal') : pill('Belum diporsi', 'gray') ?></td><td><button class="btn btn-soft btn-sm pilih" data-id="<?= e($s['id_pesanan']) ?>">Pilih</button></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?></div>
  <div class="card reveal" style="--d:4"><div class="card-head"><div><h2>Riwayat Terbaru</h2><small>Pencatatan status terakhir</small></div></div>
    <?php if (!$riw): ?><div class="empty" style="padding:26px">Belum ada pencatatan.</div><?php else: ?><ul class="act"><?php foreach ($riw as $r): ?>
      <li><span class="tdot" style="background:var(--teal-bg);color:var(--teal)"><?= icon('truck', 17) ?></span><div><b><?= e($r['pasien']) ?> · <?= e(TAHAP[$r['tahap']]) ?></b><small><?= e(date('H:i', strtotime($r['waktu']))) ?> · <?= e($r['petugas'] ?? '-') ?></small></div></li>
    <?php endforeach; ?></ul><?php endif; ?></div>
</div>
<?php page_end(['distribusi.js'], ['jsQR.js']);
