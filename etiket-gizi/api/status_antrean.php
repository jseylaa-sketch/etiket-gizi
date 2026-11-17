<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi'], false);
pastikan_pekerja();
$rows = q("SELECT j.id_pekerjaan, j.id_pesanan, j.prioritas, j.mode, j.status, j.waktu_masuk, j.waktu_selesai, o.jenis_diet, p.nama, p.kamar
           FROM pekerjaan_cetak j JOIN pesanan_diet o ON o.id_pesanan=j.id_pesanan JOIN pasien p ON p.id_pasien=o.id_pasien
           WHERE j.id_uji IS NULL
           ORDER BY FIELD(j.status,'diproses','antre','gagal','selesai'), j.prioritas, j.waktu_masuk, j.id_pekerjaan LIMIT 300")->fetchAll();
$c = q("SELECT COUNT(*) total, SUM(status='selesai') selesai, SUM(status='antre') antre, SUM(status='diproses') diproses, SUM(status='gagal') gagal FROM pekerjaan_cetak WHERE id_uji IS NULL")->fetch();
foreach ($rows as &$r) { $r['waktu_masuk_fmt'] = date('H:i:s', strtotime($r['waktu_masuk'])); $r['berkas'] = $r['status'] === 'selesai'; } unset($r);
json_out(['ok' => true, 'rows' => $rows, 'hitung' => array_map('intval', $c), 'worker_aktif' => worker_aktif(), 'mode_worker' => cfg('worker_mode')]);
