<?php
// SIMRS tiruan: mengembalikan data pasien aktif (dummy) dalam bentuk JSON.
// Contoh: http://localhost/etiket-gizi/simrs_tiruan/pasien_aktif.php?jadwal=Siang
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents(__DIR__ . '/data_pasien.json'), true);

$jadwal = $_GET['jadwal'] ?? null;
if ($jadwal) {
    $data = array_values(array_filter($data, fn($r) => strcasecmp($r['jadwal_makan'], $jadwal) === 0));
}

echo json_encode(['status' => 'ok', 'jumlah' => count($data), 'data' => $data], JSON_UNESCAPED_UNICODE);
