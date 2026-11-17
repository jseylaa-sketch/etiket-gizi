<?php
require __DIR__ . '/includes/layout.php';
$rows = q("SELECT o.id_pesanan, o.jenis_diet, o.jadwal_makan, o.diet_mendadak, o.status, p.nama, p.no_rm, p.kamar, e.status_cetak
           FROM pesanan_diet o JOIN pasien p ON p.id_pasien=o.id_pasien LEFT JOIN etiket e ON e.id_pesanan=o.id_pesanan
           WHERE o.status <> 'dalam_antrean' ORDER BY o.diet_mendadak DESC, o.id_pesanan")->fetchAll();
$siap = array_values(array_filter($rows, fn($r) => in_array($r['status'], ['baru', 'diperbarui', 'siap_cetak'], true)));
page_start('etiket', 'Pembuatan Etiket', 'Buat stiker ber-QR Code (PDF siap cetak) untuk satu pasien atau seluruh pesanan');
?>
<?php if (!$rows): ?>
<div class="card reveal"><div class="empty"><div class="em-ico"><?= icon('tag', 34) ?></div><b>Belum ada pesanan diet</b><p>Ambil data pasien dari SIMRS terlebih dahulu.</p><p style="margin-top:16px"><button class="btn btn-primary btn-lg" data-simrs><?= icon('refresh', 18) ?> Ambil Data SIMRS</button></p></div></div>
<?php else: ?>
<div class="grid g2">
  <div class="card reveal" style="--d:1">
    <div class="card-head"><div><h2>Stiker Satu Pasien</h2><small>Pilih pesanan, periksa pratinjau, lalu buat PDF</small></div></div>
    <div class="field"><label for="sel">Pilih pesanan</label>
      <select class="select" id="sel"><?php foreach ($rows as $r): ?><option value="<?= e($r['id_pesanan']) ?>" data-nama="<?= e($r['nama']) ?>" data-rm="<?= e($r['no_rm']) ?>" data-kamar="<?= e($r['kamar']) ?>" data-diet="<?= e($r['jenis_diet']) ?>" data-jadwal="<?= e($r['jadwal_makan']) ?>" data-mend="<?= (int)$r['diet_mendadak'] ?>"><?= e($r['nama'] . ' (' . $r['no_rm'] . ')' . ((int)$r['diet_mendadak'] ? ' - mendadak' : '')) ?></option><?php endforeach; ?></select></div>
    <div class="callout" style="margin-bottom:18px"><?= icon('shield', 19) ?><div>QR Code hanya berisi <b>ID pesanan</b>. Data identitas pasien tidak ikut tersimpan di dalam kode.</div></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-primary" id="btnSatu"><?= icon('tag', 17) ?> Buat Stiker PDF</button><a class="btn btn-soft" id="lnkPdf" target="_blank" style="display:none"><?= icon('file', 17) ?> Buka PDF</a></div>
  </div>
  <div class="card reveal" style="--d:2"><div class="card-head"><div><h2>Pratinjau Stiker</h2><small>Ukuran <?= (int)cfg('stiker', 'lebar_mm') ?> × <?= (int)cfg('stiker', 'tinggi_mm') ?> mm (dapat disesuaikan di config.php)</small></div></div>
    <div id="prev" style="display:grid;place-items:center;padding:8px 0 4px"></div></div>
</div>

<div class="card reveal mt" style="--d:3">
  <div class="card-head"><div><h2>Pembuatan Massal</h2><small><?= count($siap) ?> pesanan siap dibuat stikernya · <?= count(array_filter($siap, fn($r) => (int)$r['diet_mendadak'])) ?> di antaranya diet mendadak</small></div>
    <div class="seg" id="seg"><span class="thumb"></span><button class="on" data-v="spooling">Spooling</button><button data-v="langsung">Langsung</button></div></div>
  <div class="grid g-1-2" style="align-items:stretch">
    <div>
      <div class="callout blue" id="modeNote"></div>
      <div style="margin-top:18px"><button class="btn btn-primary btn-lg" id="btnMassal" <?= $siap ? '' : 'disabled' ?>><?= icon('play', 17) ?> <span>Buat <?= count($siap) ?> Stiker Sekarang</span></button></div>
    </div>
    <div style="background:var(--g50);border-radius:18px;padding:22px;display:flex;flex-direction:column;justify-content:center;min-height:150px" id="hasil">
      <div style="color:var(--mut);font-weight:600">Hasil akan tampil di sini setelah stiker dibuat.</div>
    </div>
  </div>
</div>
<script>window.SIAP = <?= json_encode(array_column($siap, 'id_pesanan')) ?>;</script>
<?php endif;
page_end($rows ? ['etiket.js'] : []);
