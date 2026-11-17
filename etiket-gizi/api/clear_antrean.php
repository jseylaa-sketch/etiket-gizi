<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi']);
q("DELETE FROM pekerjaan_cetak WHERE status IN ('selesai','gagal') AND id_uji IS NULL");
log_aksi('bersihkan_antrean', 'pekerjaan selesai dihapus dari daftar');
json_out(['ok' => true, 'pesan' => 'Daftar pekerjaan selesai dibersihkan.']);
