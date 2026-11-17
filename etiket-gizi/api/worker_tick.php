<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/spooler.php';
api_guard(['ahli_gizi']);
$n = pekerja_jalankan(0, 4);   // mode poll: proses beberapa pekerjaan lalu kembali
json_out(['ok' => true, 'diproses' => max($n, 0), 'sibuk' => $n < 0]);
