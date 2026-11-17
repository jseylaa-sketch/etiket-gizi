<?php
// Pekerja spooling: dijalankan dari baris perintah / proses latar belakang.
// Contoh manual:  php worker/pekerja.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Hanya untuk CLI.'); }
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/spooler.php';
$n = pekerja_jalankan(2000);
fwrite(STDOUT, $n < 0 ? "Pekerja lain sedang aktif.\n" : "Selesai: {$n} pekerjaan diproses.\n");
