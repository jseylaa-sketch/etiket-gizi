<?php
declare(strict_types=1);

function cfg(string $k, ?string $sub = null) {
    $v = $GLOBALS['CFG'][$k] ?? null;
    return $sub === null ? $v : ($v[$sub] ?? null);
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Alamat dasar aplikasi, mis. /etiket-gizi (dihitung dari lokasi folder terhadap DOCUMENT_ROOT). */
function base_url(): string {
    static $b = null;
    if ($b !== null) return $b;
    $root = rtrim(str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $app  = str_replace('\\', '/', APP_ROOT);
    if ($root !== '' && stripos($app, $root) === 0) $b = substr($app, strlen($root));
    else $b = rtrim(dirname(dirname(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/x/y')))), '/');
    return $b = rtrim($b, '/');
}
function url(string $path = ''): string { return base_url() . '/' . ltrim($path, '/'); }
function asset(string $path): string {
    $f = APP_ROOT . '/assets/' . $path;
    return url('assets/' . $path) . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

/** Waktu dengan presisi milidetik untuk kolom DATETIME(3). */
function ms_str(?float $t = null): string {
    $t ??= microtime(true);
    return date('Y-m-d H:i:s', (int)$t) . '.' . sprintf('%03d', (int)(($t - floor($t)) * 1000));
}
function now_ms(): string { return ms_str(); }

function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function peran_label(string $p): string {
    return ['dokter_perawat' => 'Dokter/Perawat', 'ahli_gizi' => 'Ahli Gizi', 'petugas_distribusi' => 'Petugas Distribusi'][$p] ?? $p;
}
function tgl_id(?string $dt, bool $jam = true): string {
    if (!$dt) return '-';
    $t = strtotime($dt);
    return date($jam ? 'd/m/Y H:i' : 'd/m/Y', $t);
}
function hari_id(): string {
    $h = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $b = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $h[(int)date('w')] . ', ' . date('j') . ' ' . $b[(int)date('n') - 1] . ' ' . date('Y');
}

function log_aksi(string $aksi, string $detail = ''): void {
    try {
        q('INSERT INTO log_aktivitas (waktu, id_pengguna, aksi, detail) VALUES (?,?,?,?)',
          [date('Y-m-d H:i:s'), $_SESSION['u']['id'] ?? null, $aksi, mb_substr($detail, 0, 250)]);
    } catch (Throwable $e) { /* log tidak boleh mengganggu proses utama */ }
}

function pill(string $label, string $kind = 'gray', bool $pulse = false): string {
    return '<span class="pill pill-' . e($kind) . ($pulse ? ' pulse' : '') . '">' . e($label) . '</span>';
}
function pill_status_pesanan(string $s, bool $mendadak = false): string {
    if ($mendadak && $s !== 'selesai') return pill('Mendadak', 'red', true);
    return match ($s) {
        'siap_cetak'    => pill('Siap Cetak', 'green'),
        'baru'          => pill('Baru', 'gray'),
        'diperbarui'    => pill('Diperbarui', 'blue'),
        'dalam_antrean' => pill('Dalam Antrean', 'orange'),
        'selesai'       => pill('Stiker Selesai', 'teal'),
        default         => pill($s, 'gray'),
    };
}
function pill_prioritas(int $p): string { return $p === 1 ? pill('Tinggi', 'red') : pill('Normal', 'gray'); }
function pill_job(string $s): string {
    return match ($s) {
        'antre'    => pill('Menunggu', 'orange'),
        'diproses' => pill('Diproses', 'blue', true),
        'selesai'  => pill('Selesai', 'green'),
        'gagal'    => pill('Gagal', 'red'),
        default    => pill($s, 'gray'),
    };
}
const TAHAP = ['diporsi' => 'Diporsi', 'dalam_pengiriman' => 'Dalam Pengiriman', 'diterima' => 'Diterima Pasien'];
function tahap_berikut(?string $t): ?string {
    $u = array_keys(TAHAP);
    if ($t === null) return $u[0];
    $i = array_search($t, $u, true);
    return ($i === false || $i >= count($u) - 1) ? null : $u[$i + 1];
}

const IKON = [
 'dashboard'=>'<rect x="3" y="3" width="7" height="9" rx="1.6"/><rect x="14" y="3" width="7" height="5" rx="1.6"/><rect x="14" y="12" width="7" height="9" rx="1.6"/><rect x="3" y="16" width="7" height="5" rx="1.6"/>',
 'users'=>'<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17.5 14c2.6.2 4 2.2 4 5"/>',
 'utensils'=>'<path d="M6 3v8M3 3v5a3 3 0 0 0 6 0V3M6 11v10"/><path d="M17 3c-2.2 1.6-3 4.2-3 7h3v11"/>',
 'tag'=>'<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="8" cy="8" r="1.3"/>',
 'printer'=>'<path d="M7 9V3h10v6M7 17H4v-7h16v7h-3M7 14h10v7H7z"/>',
 'truck'=>'<path d="M2 6h11v10H2zM13 9h5l3 3v4h-8"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
 'chart'=>'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
 'logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
 'bell'=>'<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4zM10 21h4"/>',
 'search'=>'<circle cx="11" cy="11" r="6.5"/><path d="M16 16l5 5"/>',
 'refresh'=>'<path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5"/>',
 'plus'=>'<path d="M12 5v14M5 12h14"/>',
 'check'=>'<path d="M5 13l4 4L19 7"/>',
 'x'=>'<path d="M6 6l12 12M18 6L6 18"/>',
 'eye'=>'<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
 'eyeoff'=>'<path d="M3 3l18 18M10.6 6.1A9.6 9.6 0 0 1 12 6c6.4 0 10 6 10 6a17 17 0 0 1-3.2 3.9M6.6 7.6A17 17 0 0 0 2 12s3.6 7 10 7a9.6 9.6 0 0 0 4.2-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
 'lock'=>'<rect x="5" y="11" width="14" height="10" rx="2.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
 'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
 'qr'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v1M14 20h3M20 18v3h-3"/>',
 'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 'alert'=>'<path d="M12 3.5L22 20H2z"/><path d="M12 10v4.5M12 17.4v.2"/>',
 'download'=>'<path d="M12 4v11M7 10l5 5 5-5M4 20h16"/>',
 'play'=>'<path d="M7 4l13 8-13 8z"/>',
 'chev'=>'<path d="M6 9l6 6 6-6"/>',
 'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
 'camera'=>'<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
 'trash'=>'<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
 'file'=>'<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4"/>',
 'edit'=>'<path d="M4 20h4L19 9l-4-4L4 16z"/>',
 'bolt'=>'<path d="M13 3L5 14h6l-1 7 8-11h-6z"/>',
 'shield'=>'<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
 'leaf'=>'<path d="M5 19c0-8 5-14 15-15 0 10-6 15-14 15"/><path d="M5 19c3-5 6-8 10-10"/>',
 'upload'=>'<path d="M12 16V5M7 10l5-5 5 5M4 20h16"/>',
 'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>',
 'info'=>'<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.2"/>',
];
function icon(string $n, int $size = 20): string {
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . (IKON[$n] ?? '') . '</svg>';
}
