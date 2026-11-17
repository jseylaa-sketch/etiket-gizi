<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi'], false);
$rows = q("SELECT * FROM hasil_uji WHERE status='selesai' ORDER BY id_uji DESC LIMIT 300")->fetchAll();
$agg  = q("SELECT skenario, jumlah_stiker, COUNT(*) n, AVG(waktu_respons_ms) respons, AVG(waktu_total_ms) total, AVG(waktu_tunggu_mendadak_ms) tunggu,
                  MIN(waktu_respons_ms) rmin, MAX(waktu_respons_ms) rmax
           FROM hasil_uji WHERE status='selesai' GROUP BY skenario, jumlah_stiker
           ORDER BY jumlah_stiker, FIELD(skenario,'langsung','spooling_fcfs','spooling_prioritas')")->fetchAll();
$pesanan = (int)q('SELECT COUNT(*) FROM pesanan_diet')->fetchColumn();
$antre = (int)q("SELECT COUNT(*) FROM pekerjaan_cetak WHERE status IN ('antre','diproses')")->fetchColumn();
json_out(['ok' => true, 'rows' => $rows, 'agg' => $agg, 'pesanan' => $pesanan, 'antre' => $antre]);
