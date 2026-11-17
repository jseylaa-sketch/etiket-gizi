<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi']);
$b = api_body();
$ids = array_values(array_unique(array_filter((array)($b['ids'] ?? []), 'is_string')));
$mode = ($b['mode'] ?? 'spooling') === 'langsung' ? 'langsung' : 'spooling';
if (!$ids) json_out(['ok' => false, 'pesan' => 'Pilih minimal satu pesanan.'], 422);

if ($mode === 'langsung') {
    // Pembuatan langsung: halaman menunggu sampai semua stiker selesai.
    $n = 0;
    foreach ($ids as $id) {
        if (!pesanan_lengkap($id)) continue;
        $t = now_ms(); $m = microtime(true);
        hasilkan_etiket($id);
        q("INSERT INTO pekerjaan_cetak (id_pesanan,prioritas,mode,status,waktu_masuk,waktu_mulai,waktu_selesai) VALUES (?,?,?,?,?,?,?)",
          [$id, 2, 'langsung', 'selesai', $t, $t, now_ms()]);
        q("UPDATE pesanan_diet SET status='selesai' WHERE id_pesanan=?", [$id]);
        $n++;
    }
    $ms = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1);
    log_aksi('stiker_langsung', "$n stiker, $ms ms");
    json_out(['ok' => true, 'mode' => 'langsung', 'jumlah' => $n, 'ms' => $ms, 'pesan' => "$n stiker selesai dibuat langsung dalam $ms ms (halaman menunggu)."]);
}
$jobs = antrean_tambah($ids, 'spooling', true);
$spawn = spawn_worker();
$ms = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1);
log_aksi('stiker_spooling', count($jobs) . " pekerjaan masuk antrean, respons $ms ms");
json_out(['ok' => true, 'mode' => 'spooling', 'jumlah' => count($jobs), 'ms' => $ms, 'worker' => cfg('worker_mode'), 'spawn' => $spawn,
          'pesan' => count($jobs) . " pekerjaan masuk antrean dalam $ms ms. Stiker dibuat di latar belakang."]);
