<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/simrs.php';
api_guard(['ahli_gizi']);
$res = simrs_ambil();
if (!$res['ok']) json_out(['ok' => false, 'pesan' => $res['pesan']], 502);
$s = simrs_sinkron($res['data']);
log_aksi('ambil_simrs', "{$s['baru']} baru, {$s['diperbarui']} diperbarui");
json_out(['ok' => true, 'pesan' => "Data SIMRS diambil: {$s['baru']} pesanan baru, {$s['diperbarui']} diperbarui, {$s['tetap']} tidak berubah.", 'ringkasan' => $s, 'jumlah' => count($res['data']), 'ms_simrs' => $res['ms']]);
