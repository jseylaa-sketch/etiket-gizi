<?php
require __DIR__ . '/includes/layout.php';
$chip = '<span class="chip" id="wchip" style="display:inline-flex;gap:8px;align-items:center;padding:9px 14px;font-size:13px"><span id="wdot" style="width:9px;height:9px;border-radius:50%;background:#B6C6C0"></span><span id="wtxt">Memeriksa pekerja...</span></span>';
page_start('antrean', 'Antrean Cetak (Spooling)', 'Pekerjaan pembuatan stiker diurutkan menurut prioritas, lalu urutan masuk (FCFS)', ['actions' => $chip . '<button class="btn btn-ghost" id="btnBersih">' . icon('trash', 16) . ' Bersihkan yang Selesai</button>']);
?>
<div class="card reveal" style="--d:1">
  <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:12px">
    <h2 id="progTitle">Memuat antrean...</h2><b id="progPct" style="font-size:20px;color:var(--g600)">0%</b></div>
  <div class="progress" id="prog"><i></i></div>
  <div style="display:flex;gap:22px;flex-wrap:wrap;margin-top:14px;color:var(--mut);font-weight:600;font-size:13px">
    <span>Menunggu: <b id="cAntre" style="color:var(--orange)">0</b></span><span>Diproses: <b id="cProses" style="color:var(--blue)">0</b></span><span>Selesai: <b id="cSelesai" style="color:var(--green)">0</b></span><span>Gagal: <b id="cGagal" style="color:var(--red)">0</b></span></div>
</div>
<div class="card reveal mt" style="--d:2">
  <div class="callout" style="margin-bottom:18px"><?= icon('info', 19) ?><div>Halaman ini <b>tetap dapat digunakan</b> selama stiker dibuat di latar belakang. Pekerjaan berprioritas tinggi (diet mendadak) dikerjakan lebih dahulu.</div></div>
  <div id="kosong" class="empty" style="display:none"><div class="em-ico"><?= icon('printer', 34) ?></div><b>Antrean kosong</b>Kirim pesanan dari halaman <a href="<?= e(url('pesanan.php')) ?>" style="color:var(--g600);font-weight:700">Pesanan Diet</a> atau <a href="<?= e(url('etiket.php')) ?>" style="color:var(--g600);font-weight:700">Pembuatan Etiket</a>.</div>
  <div class="table-wrap" id="tw"><table class="tbl"><thead><tr><th style="width:60px">Urutan</th><th>ID Pesanan</th><th>Pasien</th><th>Jenis Diet</th><th>Prioritas</th><th>Mode</th><th>Masuk</th><th>Status</th><th style="width:90px">Stiker</th></tr></thead><tbody id="tb"></tbody></table></div>
</div>
<?php page_end(['antrean.js']);
