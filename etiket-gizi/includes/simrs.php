<?php
declare(strict_types=1);

/** Mengambil data pasien aktif dari SIMRS (tiruan) lewat HTTP. */
function simrs_ambil(string $jadwal = ''): array {
    $url = cfg('simrs_url');
    if (!$url) {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $url = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('simrs_tiruan/pasien_aktif.php');
    }
    if ($jadwal !== '') $url .= (str_contains($url, '?') ? '&' : '?') . 'jadwal=' . urlencode($jadwal);
    $t0 = microtime(true);
    $body = false; $err = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4]);
        $body = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 8]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) $err = 'gagal membuka alamat';
    }
    if ($body === false || $body === '') return ['ok' => false, 'pesan' => 'SIMRS tidak dapat dihubungi (' . $err . '). Alamat: ' . $url];
    $j = json_decode($body, true);
    if (!is_array($j) || ($j['status'] ?? '') !== 'ok') return ['ok' => false, 'pesan' => 'Respons SIMRS tidak valid.'];
    return ['ok' => true, 'data' => $j['data'], 'ms' => round((microtime(true) - $t0) * 1000, 1)];
}

/** Menyimpan data SIMRS ke basis data E-Ticket (tanpa penyalinan manual). */
function simrs_sinkron(array $rows): array {
    $baru = 0; $diperbarui = 0; $tetap = 0;
    $pdo = db(); $pdo->beginTransaction();
    try {
        foreach ($rows as $r) {
            $p = q('SELECT id_pasien FROM pasien WHERE no_rm = ?', [$r['no_rm']])->fetch();
            if ($p) { $idp = (int)$p['id_pasien']; q('UPDATE pasien SET nama=?, kamar=? WHERE id_pasien=?', [$r['nama'], $r['kamar'], $idp]); }
            else { q('INSERT INTO pasien (no_rm, nama, kamar) VALUES (?,?,?)', [$r['no_rm'], $r['nama'], $r['kamar']]); $idp = (int)$pdo->lastInsertId(); }
            $mend = !empty($r['diet_mendadak']) ? 1 : 0;
            $o = q('SELECT * FROM pesanan_diet WHERE id_pesanan = ?', [$r['id_pesanan']])->fetch();
            if (!$o) {
                q('INSERT INTO pesanan_diet (id_pesanan,id_pasien,jenis_diet,jadwal_makan,diet_mendadak,waktu_ubah,status) VALUES (?,?,?,?,?,?,?)',
                  [$r['id_pesanan'], $idp, $r['jenis_diet'], $r['jadwal_makan'], $mend, $r['waktu_ubah'] ?: null, 'siap_cetak']);
                $baru++;
            } elseif ($o['jenis_diet'] !== $r['jenis_diet'] || (int)$o['diet_mendadak'] !== $mend) {
                q("UPDATE pesanan_diet SET jenis_diet=?, diet_mendadak=?, waktu_ubah=?, status=IF(status='selesai','diperbarui',status) WHERE id_pesanan=?",
                  [$r['jenis_diet'], $mend, $r['waktu_ubah'] ?: date('Y-m-d H:i:s'), $r['id_pesanan']]);
                q("UPDATE etiket SET status_cetak='belum' WHERE id_pesanan=?", [$r['id_pesanan']]);
                $diperbarui++;
            } else $tetap++;
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    return ['baru' => $baru, 'diperbarui' => $diperbarui, 'tetap' => $tetap];
}
