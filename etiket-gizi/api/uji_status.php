<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi'], false);
$id = (int)($_GET['id'] ?? 0);
$h = q('SELECT * FROM hasil_uji WHERE id_uji = ?', [$id])->fetch();
if (!$h) json_out(['ok' => false, 'pesan' => 'Data uji tidak ditemukan.'], 404);
if ($h['status'] === 'berjalan') {
    if (cfg('worker_mode') === 'poll') pekerja_jalankan(0, 4); else pastikan_pekerja();
    $p = q("SELECT COUNT(*) total, SUM(status IN ('selesai','gagal')) sel FROM pekerjaan_cetak WHERE id_uji = ?", [$id])->fetch();
    if ((int)$p['total'] > 0 && (int)$p['total'] === (int)$p['sel']) {
        $m = q("SELECT TIMESTAMPDIFF(MICROSECOND, MIN(j.waktu_masuk), MAX(j.waktu_selesai))/1000 AS total,
                       AVG(CASE WHEN o.diet_mendadak=1 THEN TIMESTAMPDIFF(MICROSECOND, j.waktu_masuk, j.waktu_selesai) END)/1000 AS tunggu
                FROM pekerjaan_cetak j JOIN pesanan_diet o ON o.id_pesanan=j.id_pesanan WHERE j.id_uji = ?", [$id])->fetch();
        q("UPDATE hasil_uji SET waktu_total_ms=?, waktu_tunggu_mendadak_ms=?, status='selesai' WHERE id_uji=?",
          [round((float)$m['total'], 1), $m['tunggu'] === null ? null : round((float)$m['tunggu'], 1), $id]);
        $h = q('SELECT * FROM hasil_uji WHERE id_uji = ?', [$id])->fetch();
    }
}
$p ??= q("SELECT COUNT(*) total, SUM(status IN ('selesai','gagal')) sel FROM pekerjaan_cetak WHERE id_uji = ?", [$id])->fetch();
json_out(['ok' => true, 'selesai' => $h['status'] === 'selesai', 'hasil' => $h, 'progres' => ['total' => (int)$p['total'], 'selesai' => (int)$p['sel']]]);
