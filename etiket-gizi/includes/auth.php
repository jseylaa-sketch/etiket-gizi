<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('etiketgizi');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const MENU = [
    ['dashboard', 'Dashboard',        'dashboard', 'index.php',        ['dokter_perawat', 'ahli_gizi', 'petugas_distribusi']],
    ['pasien',    'Data Pasien',      'users',     'pasien.php',       ['dokter_perawat', 'ahli_gizi']],
    ['pesanan',   'Pesanan Diet',     'utensils',  'pesanan.php',      ['dokter_perawat', 'ahli_gizi']],
    ['etiket',    'Pembuatan Etiket', 'tag',       'etiket.php',       ['ahli_gizi']],
    ['antrean',   'Antrean Cetak',    'printer',   'antrean.php',      ['ahli_gizi']],
    ['distribusi','Distribusi',       'truck',     'distribusi.php',   ['ahli_gizi', 'petugas_distribusi']],
    ['uji',       'Uji Kinerja',      'chart',     'uji_kinerja.php',  ['ahli_gizi']],
];
function menu_untuk(string $peran): array { return array_values(array_filter(MENU, fn($m) => in_array($peran, $m[4], true))); }
function user(): ?array { return $_SESSION['u'] ?? null; }
function boleh(string $slug): bool {
    $u = user(); if (!$u) return false;
    foreach (MENU as $m) if ($m[0] === $slug) return in_array($u['peran'], $m[4], true);
    return false;
}
function login(string $username, string $password): bool {
    $r = q('SELECT * FROM pengguna WHERE username = ?', [trim($username)])->fetch();
    if (!$r || !password_verify($password, $r['password_hash'])) return false;
    session_regenerate_id(true);
    $_SESSION['u'] = ['id' => (int)$r['id_pengguna'], 'nama' => $r['nama'], 'username' => $r['username'], 'peran' => $r['peran']];
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    log_aksi('login', $r['username']);
    return true;
}
function logout(): void {
    log_aksi('logout', (string)($_SESSION['u']['username'] ?? ''));
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 3600, $p['path']); }
    session_destroy();
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok(): bool {
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    return is_string($t) && $t !== '' && hash_equals($_SESSION['csrf'] ?? '', $t);
}
/** Dipakai oleh endpoint API: cek login, peran, dan CSRF; keluarannya JSON. */
function api_guard(array $peran, bool $post = true): array {
    $u = user();
    if (!$u) json_out(['ok' => false, 'pesan' => 'Sesi berakhir, silakan login ulang.'], 401);
    if (!in_array($u['peran'], $peran, true)) json_out(['ok' => false, 'pesan' => 'Peran Anda tidak memiliki izin untuk aksi ini.'], 403);
    if ($post) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'pesan' => 'Metode tidak diizinkan.'], 405);
        if (!csrf_ok()) json_out(['ok' => false, 'pesan' => 'Token keamanan tidak valid. Muat ulang halaman.'], 419);
    }
    return $u;
}
function api_body(): array {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw ?: '', true);
    return is_array($j) ? $j : $_POST;
}
