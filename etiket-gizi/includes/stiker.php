<?php
declare(strict_types=1);
require_once APP_ROOT . '/lib/fpdf/fpdf.php';
require_once APP_ROOT . '/lib/phpqrcode/qrlib.php';

/** Matriks QR (array string '0'/'1'). QR hanya berisi ID pesanan. */
function qr_matrix(string $text): array {
    return QRencode::factory(QR_ECLEVEL_M, 1, 0)->encode($text, false);
}
function qr_svg(string $text, int $px = 180): string {
    $m = qr_matrix($text); $n = count($m); $q = 2; $tot = $n + 2 * $q; $d = '';
    foreach ($m as $y => $row) for ($x = 0, $l = strlen($row); $x < $l; $x++) if ($row[$x] === '1') $d .= 'M' . ($x + $q) . ',' . ($y + $q) . 'h1v1h-1z';
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $px . '" height="' . $px . '" viewBox="0 0 ' . $tot . ' ' . $tot . '" shape-rendering="crispEdges"><rect width="' . $tot . '" height="' . $tot . '" fill="#fff"/><path d="' . $d . '" fill="#000"/></svg>';
}
function pdf_txt(string $s): string {
    $r = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    return $r === false ? $s : $r;
}
function stiker_path(string $idPesanan): string {
    return APP_ROOT . '/storage/stiker/' . preg_replace('/[^A-Za-z0-9_-]/', '', $idPesanan) . '.pdf';
}
function pesanan_lengkap(string $id): ?array {
    return q('SELECT o.*, p.no_rm, p.nama, p.kamar FROM pesanan_diet o JOIN pasien p ON p.id_pasien = o.id_pasien WHERE o.id_pesanan = ?', [$id])->fetch() ?: null;
}

/** Membuat stiker siap cetak (PDF) berukuran sesuai konfigurasi. Mengembalikan lokasi berkas. */
function buat_stiker_pdf(array $r): string {
    $W = (float)cfg('stiker', 'lebar_mm'); $H = (float)cfg('stiker', 'tinggi_mm');
    $pdf = new FPDF('L', 'mm', [$H, $W]);
    $pdf->SetMargins(0, 0, 0); $pdf->SetAutoPageBreak(false);
    $pdf->AddPage(); $pdf->SetTitle('Etiket ' . $r['id_pesanan']);
    $pdf->SetDrawColor(20, 20, 20); $pdf->SetLineWidth(0.3); $pdf->Rect(0.8, 0.8, $W - 1.6, $H - 1.6);
    $pdf->SetFont('Helvetica', 'B', 5.2); $pdf->SetTextColor(20, 20, 20);
    $pdf->SetXY(3, 2.2); $pdf->Cell($W - 6, 3, pdf_txt('INSTALASI GIZI  |  ' . strtoupper(cfg('app', 'rs'))), 0, 0, 'L');
    $pdf->SetLineWidth(0.2); $pdf->Line(3, 6.2, $W - 3, 6.2);
    $baris = [['Nama', $r['nama']], ['No. RM', $r['no_rm']], ['Kamar', $r['kamar']], ['Diet', $r['jenis_diet']], ['Jadwal', $r['jadwal_makan']]];
    $y = 8.0; $maxW = $W - 3 - 15 - 23;
    foreach ($baris as [$lab, $val]) {
        $pdf->SetFont('Helvetica', '', 5.2); $pdf->SetTextColor(90, 90, 90); $pdf->SetXY(3, $y); $pdf->Cell(11, 4, pdf_txt($lab), 0, 0);
        $size = 7.4; $pdf->SetFont('Helvetica', 'B', $size);
        while ($pdf->GetStringWidth(pdf_txt((string)$val)) > $maxW && $size > 4.5) { $size -= 0.3; $pdf->SetFont('Helvetica', 'B', $size); }
        $pdf->SetTextColor(15, 15, 15); $pdf->SetXY(14.5, $y - 0.4); $pdf->Cell($maxW, 4.6, pdf_txt(': ' . $val), 0, 0);
        $y += 5.2;
    }
    // QR Code (vektor, hanya ID pesanan)
    $m = qr_matrix($r['id_pesanan']); $n = count($m); $qs = 19.0; $mod = $qs / ($n + 2); $qx = $W - 3 - $qs; $qy = 8.0;
    $pdf->SetFillColor(255, 255, 255); $pdf->Rect($qx, $qy, $qs, $qs, 'F'); $pdf->SetFillColor(0, 0, 0);
    foreach ($m as $iy => $row) for ($ix = 0, $l = strlen($row); $ix < $l; $ix++) if ($row[$ix] === '1') $pdf->Rect($qx + ($ix + 1) * $mod, $qy + ($iy + 1) * $mod, $mod + 0.01, $mod + 0.01, 'F');
    $pdf->SetFont('Helvetica', '', 4.4); $pdf->SetTextColor(20, 20, 20); $pdf->SetXY($qx - 2, $qy + $qs + 0.6); $pdf->Cell($qs + 4, 2.6, $r['id_pesanan'], 0, 0, 'C');
    if (!empty($r['diet_mendadak'])) {
        $pdf->SetFillColor(0, 0, 0); $pdf->Rect(3, $H - 8.2, 27, 4.8, 'F');
        $pdf->SetFont('Helvetica', 'B', 6); $pdf->SetTextColor(255, 255, 255); $pdf->SetXY(3, $H - 8.0); $pdf->Cell(27, 4.4, 'DIET MENDADAK', 0, 0, 'C');
    }
    $pdf->SetFont('Helvetica', '', 3.8); $pdf->SetTextColor(90, 90, 90); $pdf->SetXY(3, $H - 3.6); $pdf->Cell($W - 6, 2.4, 'Dicetak ' . date('d/m/Y H:i'), 0, 0, 'L');
    $path = stiker_path($r['id_pesanan']);
    if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
    $pdf->Output('F', $path);
    return $path;
}

/** Membuat stiker + mencatat ke tabel etiket. */
function hasilkan_etiket(string $idPesanan): array {
    $r = pesanan_lengkap($idPesanan);
    if (!$r) throw new RuntimeException('Pesanan tidak ditemukan: ' . $idPesanan);
    $path = buat_stiker_pdf($r);
    $lat = (int)(cfg('simulasi_latensi_ms') ?? 0);
    if ($lat > 0) usleep($lat * 1000);
    q("INSERT INTO etiket (id_pesanan, kode_qr, tgl_cetak, status_cetak, berkas_pdf) VALUES (?,?,?,'dibuat',?)
       ON DUPLICATE KEY UPDATE kode_qr=VALUES(kode_qr), tgl_cetak=VALUES(tgl_cetak), status_cetak='dibuat', berkas_pdf=VALUES(berkas_pdf)",
      [$idPesanan, $idPesanan, date('Y-m-d H:i:s'), basename($path)]);
    return $r;
}
