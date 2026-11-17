<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi']);
$b = api_body();
$sk = (string)($b['skenario'] ?? ''); $N = (int)($b['jumlah'] ?? 0); $ul = max(1, (int)($b['ulangan_ke'] ?? 1)); $kel = substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string)($b['kelompok'] ?? date('ymd-His'))), 0, 30);
if (!in_array($sk, ['langsung', 'spooling_fcfs', 'spooling_prioritas'], true)) json_out(['ok' => false, 'pesan' => 'Skenario tidak valid.'], 422);
if ($N < 1 || $N > 200) json_out(['ok' => false, 'pesan' => 'Jumlah stiker harus 1-200.'], 422);

$tersedia = (int)q('SELECT COUNT(*) FROM pesanan_diet')->fetchColumn();
if ($tersedia < $N) json_out(['ok' => false, 'pesan' => "Pesanan di basis data baru $tersedia. Ambil data SIMRS terlebih dahulu."], 422);
// pilih pesanan: semua diet mendadak lebih dulu masuk daftar, lalu pesanan normal sampai jumlah terpenuhi
$pilih = q('SELECT id_pesanan, diet_mendadak FROM pesanan_diet ORDER BY diet_mendadak DESC, id_pesanan LIMIT ' . $N)->fetchAll();
$mend = array_column(array_filter($pilih, fn($r) => (int)$r['diet_mendadak'] === 1), 'id_pesanan');
$norm = array_column(array_filter($pilih, fn($r) => (int)$r['diet_mendadak'] !== 1), 'id_pesanan');
$urutan = array_merge($norm, $mend);   // diet mendadak sengaja masuk paling akhir (perubahan datang belakangan)

if ($sk !== 'langsung' && (int)q("SELECT COUNT(*) FROM pekerjaan_cetak WHERE status IN ('antre','diproses')")->fetchColumn() > 0)
    json_out(['ok' => false, 'pesan' => 'Antrean masih berisi pekerjaan. Tunggu sampai selesai sebelum uji.'], 409);

if ($sk === 'langsung') {
    $t0 = microtime(true); $selMend = [];
    foreach ($urutan as $id) { hasilkan_etiket($id); if (in_array($id, $mend, true)) $selMend[] = microtime(true) - $t0; }
    $tot = (microtime(true) - $t0) * 1000; $resp = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    $tg = $selMend ? array_sum($selMend) / count($selMend) * 1000 : null;
    q('INSERT INTO hasil_uji (kelompok,skenario,jumlah_stiker,ulangan_ke,waktu_respons_ms,waktu_total_ms,waktu_tunggu_mendadak_ms,status,dibuat) VALUES (?,?,?,?,?,?,?,?,?)',
      [$kel, $sk, $N, $ul, round($resp, 1), round($tot, 1), $tg === null ? null : round($tg, 1), 'selesai', date('Y-m-d H:i:s')]);
    $id = (int)db()->lastInsertId();
    json_out(['ok' => true, 'id' => $id, 'selesai' => true, 'waktu_respons_ms' => round($resp, 1), 'waktu_total_ms' => round($tot, 1), 'waktu_tunggu_mendadak_ms' => $tg === null ? null : round($tg, 1)]);
}
q('INSERT INTO hasil_uji (kelompok,skenario,jumlah_stiker,ulangan_ke,waktu_respons_ms,status,dibuat) VALUES (?,?,?,?,?,?,?)', [$kel, $sk, $N, $ul, 0, 'berjalan', date('Y-m-d H:i:s')]);
$id = (int)db()->lastInsertId();
antrean_tambah($urutan, 'spooling', $sk === 'spooling_prioritas', $id);
spawn_worker();
$resp = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 1);
q('UPDATE hasil_uji SET waktu_respons_ms=? WHERE id_uji=?', [$resp, $id]);
json_out(['ok' => true, 'id' => $id, 'selesai' => false, 'waktu_respons_ms' => $resp]);
