<?php
require __DIR__ . '/includes/auth.php';
if (!boleh('uji')) { http_response_code(403); exit('Tidak diizinkan'); }
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="hasil_uji_kinerja_' . date('Ymd_His') . '.csv"');
$o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
fputcsv($o, ['id_uji', 'kelompok', 'skenario', 'jumlah_stiker', 'ulangan_ke', 'waktu_respons_ms', 'waktu_total_ms', 'waktu_tunggu_mendadak_ms', 'status', 'dibuat'], ';');
foreach (q('SELECT * FROM hasil_uji ORDER BY id_uji')->fetchAll() as $r) fputcsv($o, $r, ';');
