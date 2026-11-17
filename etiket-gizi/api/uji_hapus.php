<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi']);
q('DELETE FROM pekerjaan_cetak WHERE id_uji IS NOT NULL');
q('DELETE FROM hasil_uji');
log_aksi('hapus_hasil_uji', 'seluruh hasil uji dihapus');
json_out(['ok' => true, 'pesan' => 'Seluruh hasil uji dihapus.']);
