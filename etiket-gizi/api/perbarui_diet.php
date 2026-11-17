<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['dokter_perawat', 'ahli_gizi']);
$b = api_body();
$id = (string)($b['id_pesanan'] ?? ''); $diet = (string)($b['jenis_diet'] ?? '');
$mend = !empty($b['mendadak']) ? 1 : 0; $cat = mb_substr(trim((string)($b['catatan'] ?? '')), 0, 250);
$o = pesanan_lengkap($id);
if (!$o) json_out(['ok' => false, 'pesan' => 'Pesanan tidak ditemukan.'], 404);
if (!in_array($diet, DAFTAR_DIET, true)) json_out(['ok' => false, 'pesan' => 'Jenis diet tidak valid.'], 422);
if ($diet === $o['jenis_diet'] && $mend === (int)$o['diet_mendadak']) json_out(['ok' => false, 'pesan' => 'Tidak ada perubahan yang disimpan.'], 422);

$status = $o['status'] === 'dalam_antrean' ? 'dalam_antrean' : 'diperbarui';
q('UPDATE pesanan_diet SET jenis_diet=?, diet_mendadak=?, waktu_ubah=?, catatan=?, status=? WHERE id_pesanan=?',
  [$diet, $mend, date('Y-m-d H:i:s'), $cat ?: null, $status, $id]);
q("UPDATE etiket SET status_cetak='belum' WHERE id_pesanan=?", [$id]);          // etiket lama usang
if ($mend) q("UPDATE pekerjaan_cetak SET prioritas=1 WHERE id_pesanan=? AND status='antre' AND id_uji IS NULL", [$id]); // extend: prioritaskan diet mendadak
log_aksi('perbarui_diet', "$id: {$o['jenis_diet']} -> $diet" . ($mend ? ' (mendadak)' : ''));
json_out(['ok' => true, 'pesan' => 'Diet ' . $o['nama'] . ' diperbarui menjadi ' . $diet . ($mend ? ' dengan prioritas tinggi.' : '.')]);
