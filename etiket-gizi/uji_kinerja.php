<?php
require __DIR__ . '/includes/layout.php';
$nPes = (int)q('SELECT COUNT(*) FROM pesanan_diet')->fetchColumn();
$act = '<a class="btn btn-ghost" href="' . e(url('uji_export.php')) . '">' . icon('download', 16) . ' Ekspor CSV</a><button class="btn btn-danger" id="btnHapus">' . icon('trash', 16) . ' Hapus Hasil</button>';
page_start('uji', 'Uji Kinerja Spooling', 'Bandingkan pembuatan stiker langsung dengan antrean spooling (data untuk bagian hasil jurnal)', ['actions' => $act]);
?>
<div class="grid g-1-2">
  <div class="card reveal" style="--d:1">
    <div class="card-head"><div><h2>Pengaturan Uji</h2><small><?= $nPes ?> pesanan tersedia di basis data</small></div></div>
    <?php if ($nPes < 40): ?><div class="callout warn" style="margin-bottom:16px"><?= icon('alert', 19) ?><div>Pesanan belum cukup (minimal 40). <a href="<?= e(url('pasien.php')) ?>" style="font-weight:800;text-decoration:underline">Ambil data SIMRS</a> terlebih dahulu.</div></div><?php endif; ?>
    <div class="field"><label for="jml">Jumlah stiker per uji</label><select class="select" id="jml"><option>40</option><option selected>45</option></select></div>
    <div class="field"><label for="ulang">Jumlah ulangan tiap skenario</label><input class="input" id="ulang" type="number" min="1" max="30" value="5"></div>
    <div class="field"><label>Skenario</label>
      <label style="display:flex;gap:10px;align-items:flex-start;font-weight:600;margin-bottom:8px"><input class="check" type="checkbox" value="langsung" checked><span>Langsung <small style="display:block;color:var(--mut);font-weight:500">Halaman menunggu semua stiker selesai (pembanding)</small></span></label>
      <label style="display:flex;gap:10px;align-items:flex-start;font-weight:600;margin-bottom:8px"><input class="check" type="checkbox" value="spooling_fcfs" checked><span>Spooling FCFS <small style="display:block;color:var(--mut);font-weight:500">Antrean tanpa prioritas</small></span></label>
      <label style="display:flex;gap:10px;align-items:flex-start;font-weight:600"><input class="check" type="checkbox" value="spooling_prioritas" checked><span>Spooling Prioritas <small style="display:block;color:var(--mut);font-weight:500">Diet mendadak dikerjakan lebih dahulu</small></span></label></div>
    <button class="btn btn-primary btn-lg" id="btnUji" style="width:100%" <?= $nPes < 40 ? 'disabled' : '' ?>><?= icon('play', 17) ?> <span>Jalankan Uji</span></button>
    <div id="prog" style="display:none;margin-top:18px"><div style="display:flex;justify-content:space-between;font-weight:700;font-size:13px;margin-bottom:8px"><span id="progTxt">Menyiapkan...</span><span id="progPct">0%</span></div><div class="progress"><i id="progBar"></i></div></div>
    <div class="callout blue" style="margin-top:18px"><?= icon('info', 19) ?><div>Uji berjalan di server lokal dengan data dummy dan tidak mencetak ke printer. Hasil menggambarkan kinerja rancangan, bukan kinerja di rumah sakit.</div></div>
  </div>
  <div class="card reveal" style="--d:2">
    <div class="card-head"><div><h2>Ringkasan Hasil</h2><small>Rata-rata per skenario (milidetik)</small></div><span class="chip" id="nRun">0 data</span></div>
    <div class="chart-box" style="height:230px"><canvas id="cUji"></canvas></div>
    <div id="verdict" class="callout" style="margin-top:14px;display:none"></div>
  </div>
</div>
<div class="card reveal mt" style="--d:3"><div class="card-head"><div><h2>Rata-rata per Skenario</h2><small>Dasar tabel hasil uji kinerja pada jurnal</small></div></div>
  <div class="table-wrap"><table class="tbl" style="min-width:760px"><thead><tr><th>Skenario</th><th>Jumlah stiker</th><th>Ulangan</th><th>Waktu respons halaman (ms)</th><th>Waktu total sampai selesai (ms)</th><th>Waktu tunggu stiker mendadak (ms)</th></tr></thead><tbody id="tAgg"><tr><td colspan="6" class="sub" style="text-align:center;padding:26px">Belum ada hasil uji.</td></tr></tbody></table></div></div>
<div class="card reveal mt" style="--d:4"><div class="card-head"><div><h2>Data per Percobaan</h2><small>Seluruh ulangan (juga tersedia sebagai CSV)</small></div></div>
  <div class="table-wrap" style="max-height:420px;overflow:auto"><table class="tbl" style="min-width:760px"><thead><tr><th>#</th><th>Kelompok</th><th>Skenario</th><th>Stiker</th><th>Ulangan</th><th>Respons (ms)</th><th>Total (ms)</th><th>Tunggu mendadak (ms)</th></tr></thead><tbody id="tRaw"></tbody></table></div></div>
<?php page_end(['uji.js'], ['chart.umd.js']);
