<?php
declare(strict_types=1);
require_once __DIR__ . '/stiker.php';

const DAFTAR_DIET = ['Diet Biasa', 'Diet Lunak', 'Diet Rendah Garam', 'Diet DM', 'Diet Sonde', 'Diet Rendah Protein'];

/** Menambah pekerjaan ke antrean (spool) dengan satu INSERT massal. Prioritas 1 untuk diet mendadak, lalu FCFS. */
function antrean_tambah(array $ids, string $mode = 'spooling', bool $prioritas = true, ?int $idUji = null): array {
    $ids = array_values(array_unique(array_map('strval', $ids)));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $mend = q("SELECT id_pesanan, diet_mendadak FROM pesanan_diet WHERE id_pesanan IN ($in)", $ids)->fetchAll(PDO::FETCH_KEY_PAIR);
    $aktif = [];
    if ($idUji === null) {
        $aktif = array_flip(q("SELECT DISTINCT id_pesanan FROM pekerjaan_cetak WHERE status IN ('antre','diproses') AND id_uji IS NULL AND id_pesanan IN ($in)", $ids)->fetchAll(PDO::FETCH_COLUMN));
    }
    $base = microtime(true); $rows = []; $par = []; $pakai = []; $i = 0;
    foreach ($ids as $id) {
        if (!isset($mend[$id]) || isset($aktif[$id])) continue;
        $pr = ($prioritas && (int)$mend[$id] === 1) ? 1 : 2;
        $rows[] = '(?,?,?,?,?,?)';
        array_push($par, $id, $pr, $mode, 'antre', ms_str($base + $i * 0.001), $idUji);
        $pakai[] = $id; $i++;
    }
    if (!$rows) return [];
    $pdo = db(); $pdo->beginTransaction();
    try {
        q('INSERT INTO pekerjaan_cetak (id_pesanan, prioritas, mode, status, waktu_masuk, id_uji) VALUES ' . implode(',', $rows), $par);
        $first = (int)$pdo->lastInsertId();
        if ($idUji === null) q("UPDATE pesanan_diet SET status='dalam_antrean' WHERE id_pesanan IN (" . implode(',', array_fill(0, count($pakai), '?')) . ')', $pakai);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    return range($first, $first + count($pakai) - 1);
}

function proses_pekerjaan(array $job): void {
    $ok = q("UPDATE pekerjaan_cetak SET status='diproses', waktu_mulai=? WHERE id_pekerjaan=? AND status='antre'", [now_ms(), $job['id_pekerjaan']])->rowCount();
    if (!$ok) return;
    try {
        hasilkan_etiket($job['id_pesanan']);
        q("UPDATE pekerjaan_cetak SET status='selesai', waktu_selesai=? WHERE id_pekerjaan=?", [now_ms(), $job['id_pekerjaan']]);
        if ($job['id_uji'] === null) q("UPDATE pesanan_diet SET status='selesai' WHERE id_pesanan=?", [$job['id_pesanan']]);
    } catch (Throwable $e) {
        q("UPDATE pekerjaan_cetak SET status='gagal', waktu_selesai=? WHERE id_pekerjaan=?", [now_ms(), $job['id_pekerjaan']]);
    }
}
function job_berikut(): ?array {
    // urutan: prioritas (1 lebih dulu), lalu waktu masuk (FCFS)
    return q("SELECT * FROM pekerjaan_cetak WHERE status='antre' ORDER BY prioritas ASC, waktu_masuk ASC, id_pekerjaan ASC LIMIT 1")->fetch() ?: null;
}

function lock_path(): string { return APP_ROOT . '/storage/worker.lock'; }
function worker_aktif(): bool {
    $f = @fopen(lock_path(), 'c'); if (!$f) return false;
    $got = flock($f, LOCK_EX | LOCK_NB);
    if ($got) flock($f, LOCK_UN);
    fclose($f);
    return !$got;
}
/** Pekerja: mengambil pekerjaan satu per satu sampai antrean kosong. Hanya satu pekerja aktif (flock). */
function pekerja_jalankan(int $idleMs = 1500, int $maks = PHP_INT_MAX): int {
    $f = fopen(lock_path(), 'c');
    if (!$f || !flock($f, LOCK_EX | LOCK_NB)) return -1;
    $n = 0; $idle = null;
    while ($n < $maks) {
        $job = job_berikut();
        if ($job) { proses_pekerjaan($job); $n++; $idle = null; continue; }
        if ($idleMs <= 0) break;
        $idle ??= microtime(true);
        if ((microtime(true) - $idle) * 1000 > $idleMs) break;
        usleep(80000);
    }
    flock($f, LOCK_UN); fclose($f);
    return $n;
}

function php_cli_path(): string {
    if (cfg('php_cli')) return (string)cfg('php_cli');
    if (PHP_SAPI === 'cli') return PHP_BINARY;
    $win = DIRECTORY_SEPARATOR === '\\';
    $cands = [];
    if ($ini = php_ini_loaded_file()) $cands[] = dirname($ini);
    $cands[] = PHP_BINDIR; $cands[] = 'C:\\xampp\\php'; $cands[] = '/usr/bin';
    foreach ($cands as $d) { $p = $d . DIRECTORY_SEPARATOR . ($win ? 'php.exe' : 'php'); if (is_file($p)) return $p; }
    return $win ? 'php.exe' : 'php';
}
/** Menjalankan pekerja sebagai proses terpisah di latar belakang. */
function spawn_worker(): bool {
    if (cfg('worker_mode') !== 'spawn') return false;
    if (worker_aktif()) return true;
    $php = php_cli_path(); $script = APP_ROOT . '/worker/pekerja.php';
    $off = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (DIRECTORY_SEPARATOR === '\\') {
        if (in_array('popen', $off, true)) return false;
        pclose(popen('start /B "" ' . escapeshellarg($php) . ' ' . escapeshellarg($script) . ' > NUL 2>&1', 'r'));
    } else {
        if (in_array('exec', $off, true)) return false;
        exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
    }
    return true;
}
/** Memastikan ada pekerja bila ada pekerjaan menunggu (penyembuhan otomatis). */
function pastikan_pekerja(): void {
    $antre = (int)q("SELECT COUNT(*) FROM pekerjaan_cetak WHERE status='antre'")->fetchColumn();
    if ($antre > 0 && !worker_aktif() && cfg('worker_mode') === 'spawn') spawn_worker();
}
