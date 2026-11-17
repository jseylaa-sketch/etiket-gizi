<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/stiker.php';
if (!user()) { http_response_code(401); exit; }
$id = (string)($_GET['id'] ?? '');
if (!preg_match('/^[A-Za-z0-9-]{4,25}$/', $id)) { http_response_code(400); exit; }
header('Content-Type: image/svg+xml'); header('Cache-Control: private, max-age=3600');
echo qr_svg($id, (int)($_GET['px'] ?? 180));
