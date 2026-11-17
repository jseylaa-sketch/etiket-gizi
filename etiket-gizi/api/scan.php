<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
$u = api_guard(['ahli_gizi', 'petugas_distribusi']);
$b = api_body();
$id = strtoupper(trim((string)($b['id_pesanan'] ?? ''))); $aksi = (string)($b['aksi'] ?? 'cari');
$o = $id !== '' ? pesanan_lengkap($id) : null;
if (!$o) json_out(['ok' => false, 'pesan' => 'QR Code tidak dikenali: pesanan tidak ditemukan.'], 404);
$e = q('SELECT status_cetak FROM etiket WHERE id_pesanan = ?', [$id])->fetch();
$riw = q('SELECT s.tahap, s.waktu, u.nama petugas FROM status_distribusi s LEFT JOIN pengguna u ON u.id_pengguna=s.id_pengguna WHERE s.id_pesanan=? ORDER BY s.id_status', [$id])->fetchAll();
$last = $riw ? end($riw)['tahap'] : null; $next = tahap_berikut($last);
if ($aksi === 'catat') {
    if (!$e || $e['status_cetak'] !== 'dibuat') json_out(['ok' => false, 'pesan' => 'Stiker pesanan ini belum dibuat atau sudah usang (diet berubah).'], 409);
    if ($next === null) json_out(['ok' => false, 'pesan' => 'Pesanan sudah berstatus Diterima Pasien.'], 409);
    q('INSERT INTO status_distribusi (id_pesanan, tahap, waktu, id_pengguna) VALUES (?,?,?,?)', [$id, $next, date('Y-m-d H:i:s'), $u['id']]);
    log_aksi('status_distribusi', "$id -> $next");
    $riw[] = ['tahap' => $next, 'waktu' => date('Y-m-d H:i:s'), 'petugas' => $u['nama']]; $last = $next; $next = tahap_berikut($last);
}
foreach ($riw as &$r) { $r['label'] = TAHAP[$r['tahap']]; $r['jam'] = date('H:i', strtotime($r['waktu'])); } unset($r);
json_out(['ok' => true, 'pesan' => $aksi === 'catat' ? 'Status dicatat: ' . TAHAP[$last] : 'Pesanan ditemukan.',
    'pesanan' => ['id' => $o['id_pesanan'], 'nama' => $o['nama'], 'no_rm' => $o['no_rm'], 'kamar' => $o['kamar'], 'diet' => $o['jenis_diet'], 'jadwal' => $o['jadwal_makan'], 'mendadak' => (bool)$o['diet_mendadak'], 'stiker_siap' => $e && $e['status_cetak'] === 'dibuat'],
    'tahap_terakhir' => $last, 'tahap_berikut' => $next, 'tahap_berikut_label' => $next ? TAHAP[$next] : null, 'riwayat' => $riw]);
