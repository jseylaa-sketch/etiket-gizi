<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/stiker.php';
if (!user()) { http_response_code(401); exit('Login diperlukan'); }
$id = (string)($_GET['id'] ?? ''); $f = stiker_path($id);
if (!preg_match('/^[A-Za-z0-9-]{4,25}$/', $id) || !is_file($f)) { http_response_code(404); exit('Stiker belum dibuat'); }
header('Content-Type: application/pdf'); header('Content-Disposition: inline; filename="' . basename($f) . '"'); header('Content-Length: ' . filesize($f));
readfile($f);
