<?php
require __DIR__ . '/includes/layout.php';
$u = user();
$n = fn(string $sql) => (int)q($sql)->fetchColumn();
$nPasien  = $n('SELECT COUNT(*) FROM pasien');
$nPesanan = $n('SELECT COUNT(*) FROM pesanan_diet');
$nMend    = $n('SELECT COUNT(*) FROM pesanan_diet WHERE diet_mendadak=1');
$nAntre   = $n("SELECT COUNT(*) FROM pekerjaan_cetak WHERE status IN ('antre','diproses') AND id_uji IS NULL");
$nStiker  = $n("SELECT COUNT(*) FROM etiket WHERE status_cetak='dibuat'");
$nSiap    = $n("SELECT COUNT(*) FROM pesanan_diet WHERE status IN ('baru','diperbarui','siap_cetak')");

// stiker selesai per jam (hari ini): normal vs prioritas tinggi
$jam = []; for ($h = 6; $h <= 20; $h++) $jam[$h] = [0, 0];
foreach (q("SELECT HOUR(waktu_selesai) h, prioritas p, COUNT(*) c FROM pekerjaan_cetak WHERE status='selesai' AND id_uji IS NULL AND DATE(waktu_selesai)=CURDATE() GROUP BY h, p")->fetchAll() as $r) {
    $h = (int)$r['h']; if (!isset($jam[$h])) continue; $jam[$h][(int)$r['p'] === 1 ? 1 : 0] += (int)$r['c'];
}
$diet = q('SELECT jenis_diet, COUNT(*) c FROM pesanan_diet GROUP BY jenis_diet ORDER BY c DESC')->fetchAll();
$dist = q("SELECT COALESCE(t.tahap,'belum') tahap, COUNT(*) c FROM pesanan_diet o
           LEFT JOIN (SELECT s.id_pesanan, s.tahap FROM status_distribusi s JOIN (SELECT id_pesanan, MAX(id_status) m FROM status_distribusi GROUP BY id_pesanan) x ON x.m = s.id_status) t ON t.id_pesanan = o.id_pesanan
           GROUP BY tahap")->fetchAll(PDO::FETCH_KEY_PAIR);
$distv = [(int)($dist['belum'] ?? 0), (int)($dist['diporsi'] ?? 0), (int)($dist['dalam_pengiriman'] ?? 0), (int)($dist['diterima'] ?? 0)];
$persen = $nPesanan ? round($distv[3] / $nPesanan * 100) : 0;
$mendList = q("SELECT o.id_pesanan, p.nama, p.kamar, o.jenis_diet, o.status, o.waktu_ubah FROM pesanan_diet o JOIN pasien p ON p.id_pasien=o.id_pasien WHERE o.diet_mendadak=1 ORDER BY o.waktu_ubah DESC, o.id_pesanan LIMIT 5")->fetchAll();
$logs = q('SELECT l.waktu, l.aksi, l.detail, u.nama FROM log_aktivitas l LEFT JOIN pengguna u ON u.id_pengguna=l.id_pengguna ORDER BY l.id_log DESC LIMIT 7')->fetchAll();
$AKSI = ['login' => ['Masuk ke sistem', 'user', 'gray'], 'logout' => ['Keluar dari sistem', 'logout', 'gray'], 'ambil_simrs' => ['Mengambil data SIMRS', 'refresh', 'blue'],
         'perbarui_diet' => ['Memperbarui diet pasien', 'edit', 'orange'], 'stiker_spooling' => ['Stiker masuk antrean spooling', 'printer', 'green'], 'stiker_langsung' => ['Stiker dibuat langsung', 'tag', 'green'],
         'status_distribusi' => ['Status distribusi dicatat', 'truck', 'teal'], 'bersihkan_antrean' => ['Daftar antrean dibersihkan', 'trash', 'gray'], 'hapus_hasil_uji' => ['Hasil uji dihapus', 'trash', 'gray']];
$aksi_head = $u['peran'] === 'ahli_gizi' ? '<button class="btn btn-primary" data-simrs>' . icon('refresh', 17) . ' Ambil Data SIMRS</button>' : '';
page_start('dashboard', 'Dashboard', 'Ringkasan aktivitas pembuatan etiket gizi hari ini', ['actions' => '<span class="date-chip">' . icon('clock', 16) . e(hari_id()) . '</span>' . $aksi_head]);
$DASH = ['jam' => array_map(fn($h) => sprintf('%02d:00', $h), array_keys($jam)), 'normal' => array_column($jam, 0), 'tinggi' => array_column($jam, 1),
         'diet' => array_column($diet, 'jenis_diet'), 'dietn' => array_map('intval', array_column($diet, 'c')), 'dist' => $distv];
?>
<?php if ($nPesanan === 0): ?>
<div class="card reveal" style="--d:1"><div class="empty">
  <div class="em-ico"><?= icon('refresh', 34) ?></div><b>Belum ada data pasien</b>
  <p><?= $u['peran'] === 'ahli_gizi' ? 'Mulai dengan mengambil data pasien aktif dari SIMRS (tiruan).' : 'Minta Ahli Gizi mengambil data dari SIMRS terlebih dahulu.' ?></p>
  <?php if ($u['peran'] === 'ahli_gizi'): ?><p style="margin-top:16px"><button class="btn btn-primary btn-lg" data-simrs><?= icon('refresh', 18) ?> Ambil Data SIMRS</button></p><?php endif; ?>
</div></div>
<?php else: ?>
<div class="grid g4">
  <div class="card stat hover reveal" style="--d:1"><div class="tile"><?= icon('users', 28) ?></div><label>Total Pasien</label><div class="val" data-count="<?= $nPasien ?>">0</div><div class="sub"><?= $nMend ?> diet mendadak</div></div>
  <div class="card stat blue hover reveal" style="--d:2"><div class="tile"><?= icon('utensils', 28) ?></div><label>Pesanan Diet</label><div class="val" data-count="<?= $nPesanan ?>">0</div><div class="sub"><?= $nSiap ?> siap dibuat stikernya</div></div>
  <div class="card stat warn hover reveal" style="--d:3"><div class="tile"><?= icon('printer', 28) ?></div><label>Antrean Cetak</label><div class="val" data-count="<?= $nAntre ?>">0</div><div class="sub"><?= $nAntre ? 'sedang dikerjakan pekerja' : 'antrean kosong' ?></div></div>
  <div class="card stat violet hover reveal" style="--d:4"><div class="tile"><?= icon('check', 28) ?></div><label>Stiker Selesai</label><div class="val" data-count="<?= $nStiker ?>">0</div><div class="sub"><?= $nPesanan ? round($nStiker / $nPesanan * 100) : 0 ?>% dari pesanan</div></div>
</div>

<div class="grid g-2-1 mt">
  <div class="card reveal" style="--d:5"><div class="card-head"><div><h2>Stiker Dibuat Hari Ini</h2><small>Jumlah stiker selesai per jam</small></div><span class="chip">Normal vs Prioritas Tinggi</span></div><div class="chart-box"><canvas id="cJam"></canvas></div></div>
  <div class="card reveal" style="--d:6"><div class="card-head"><div><h2>Status Distribusi</h2><small>Tahap terakhir tiap pesanan</small></div></div>
    <div class="ring"><div style="width:190px;height:190px"><canvas id="cDist"></canvas></div><div class="center"><b data-count="<?= $persen ?>">0</b><small>% diterima</small></div></div>
    <div class="legend">
      <span><i style="background:#CBD7D3"></i>Belum didistribusi<em><?= $distv[0] ?></em></span>
      <span><i style="background:#6FD6B2"></i>Diporsi<em><?= $distv[1] ?></em></span>
      <span><i style="background:#F2A93B"></i>Dalam pengiriman<em><?= $distv[2] ?></em></span>
      <span><i style="background:#10966F"></i>Diterima pasien<em><?= $distv[3] ?></em></span>
    </div></div>
</div>

<div class="grid g3 mt">
  <div class="card reveal" style="--d:7"><div class="card-head"><div><h2>Komposisi Jenis Diet</h2><small>Jumlah pesanan per diet</small></div></div><div class="chart-box" style="height:240px"><canvas id="cDiet"></canvas></div></div>
  <div class="card reveal" style="--d:8"><div class="card-head"><div><h2>Diet Mendadak</h2><small>Dicetak dengan prioritas tinggi</small></div><?php if (boleh('pesanan')): ?><a class="btn btn-soft btn-sm" href="<?= e(url('pesanan.php?filter=mendadak')) ?>">Lihat</a><?php endif; ?></div>
    <?php if (!$mendList): ?><div class="empty" style="padding:24px 0"><div class="em-ico" style="width:56px;height:56px;border-radius:18px"><?= icon('check', 26) ?></div>Tidak ada diet mendadak.</div>
    <?php else: ?><ul class="act"><?php foreach ($mendList as $m): ?>
      <li><span class="tdot" style="background:var(--red-bg);color:var(--red)"><?= icon('bolt', 17) ?></span><div style="flex:1"><b><?= e($m['nama']) ?></b><small><?= e($m['kamar']) ?> · <?= e($m['jenis_diet']) ?></small></div><?= pill_status_pesanan($m['status'], true) ?></li>
    <?php endforeach; ?></ul><?php endif; ?></div>
  <div class="card reveal" style="--d:9"><div class="card-head"><div><h2>Aktivitas Terbaru</h2><small>Catatan tindakan pengguna</small></div></div>
    <ul class="act"><?php foreach ($logs as $l): $a = $AKSI[$l['aksi']] ?? [$l['aksi'], 'info', 'gray']; ?>
      <li><span class="tdot" style="background:var(--<?= $a[2] ?>-bg);color:var(--<?= $a[2] ?>)"><?= icon($a[1], 17) ?></span><div><b><?= e($a[0]) ?></b><small><?= e($l['nama'] ?? 'Sistem') ?> · <?= e(date('H:i', strtotime($l['waktu']))) ?><?= $l['detail'] && !in_array($l['aksi'], ['login', 'logout'], true) ? ' · ' . e(mb_strimwidth($l['detail'], 0, 42, '…')) : '' ?></small></div></li>
    <?php endforeach; if (!$logs): ?><li>Belum ada aktivitas.</li><?php endif; ?></ul></div>
</div>
<script>window.DASH = <?= json_encode($DASH) ?>;</script>
<?php endif;
page_end($nPesanan ? ['dashboard.js'] : [], ['chart.umd.js']);
