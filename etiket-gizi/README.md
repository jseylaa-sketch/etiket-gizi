# E-Ticket Gizi

Website pembuatan etiket (stiker) gizi rawat inap berbasis QR Code dan spooling I/O, untuk penelitian
**"Optimasi Pembuatan Etiket Gizi Rawat Inap Berbasis QR Code dan Spooling I/O (Studi Kasus: RS Islam Hj. Siti Muniroh)"**.

Teknologi: PHP 8 (tanpa framework), MySQL/MariaDB, HTML, CSS, JavaScript biasa.
Pustaka yang sudah disertakan di folder proyek (tidak perlu internet): FPDF (PDF), phpqrcode (QR Code, keluaran SVG),
Chart.js (grafik), jsQR (pembaca QR lewat kamera), dan font Plus Jakarta Sans.

> Seluruh data adalah **data dummy**. SIMRS disimulasikan oleh `simrs_tiruan/pasien_aktif.php`.
> Sistem berhenti sampai **menghasilkan stiker PDF siap cetak**; tidak ada pengiriman ke printer.

## 1. Memasang (XAMPP, Windows)
1. Pasang XAMPP, lalu jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Ekstrak berkas ZIP ini sehingga folder `etiket-gizi` berada di `C:\xampp\htdocs\etiket-gizi`.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih berkas `database.sql`, lalu **Import**.
   (Basis data `etiket_gizi` dibuat otomatis. Mengimpor ulang akan mengosongkan semua data.)
4. Buka `http://localhost/etiket-gizi/` dan masuk dengan akun uji di bawah.

Jika kata sandi MySQL Anda tidak kosong, ubah bagian `db` di `config.php`.

## 2. Akun uji (kata sandi semuanya `gizi12345`)
| Username | Peran | Halaman yang dapat dibuka |
| --- | --- | --- |
| `gizi` | Ahli Gizi | semua halaman |
| `perawat` | Dokter/Perawat | Dashboard, Data Pasien (memperbarui diet) |
| `distribusi` | Petugas Distribusi | Dashboard, Distribusi |

## 3. Alur penggunaan
1. **Dashboard / Data Pasien** (Ahli Gizi): klik **Ambil Data SIMRS**. Data pasien dan pesanan diet diambil lewat REST API tiruan, bukan diketik ulang.
2. **Data Pasien**: klik **Perbarui Diet** pada pasien. Aktifkan *Perubahan mendadak* agar stikernya berprioritas tinggi.
3. **Pesanan Diet**: pilih pesanan lalu **Kirim ke Antrean Cetak** (mode spooling), atau
   **Pembuatan Etiket**: buat stiker satu pasien, atau massal dengan mode **Spooling** atau **Langsung**.
4. **Antrean Cetak**: pantau pekerjaan. Urutan: prioritas tinggi dahulu, lalu urutan masuk (FCFS). PDF stiker dapat dibuka dari tabel.
5. **Distribusi**: pindai QR Code (kamera atau input manual) lalu catat tahap *Diporsi → Dalam Pengiriman → Diterima Pasien*.
6. **Uji Kinerja** (Ahli Gizi): bandingkan pembuatan *Langsung*, *Spooling FCFS*, dan *Spooling Prioritas*. Hasil dapat diekspor ke CSV.

## 4. Cara kerja spooling
- Mode **Langsung**: halaman menunggu sampai seluruh PDF selesai dibuat.
- Mode **Spooling**: pekerjaan disimpan di tabel `pekerjaan_cetak` (satu INSERT massal), halaman langsung merespons, lalu skrip
  `worker/pekerja.php` (proses PHP terpisah) mengambil pekerjaan menurut `prioritas ASC, waktu_masuk ASC` dan membuat PDF.
- Satu pekerja aktif pada satu waktu (kunci berkas). Jika pekerja tidak bisa dijalankan, ubah `worker_mode` menjadi `'poll'` di `config.php`
  (halaman Antrean akan memanggil pekerja secara berkala).
- Pekerja dapat dijalankan manual: `C:\xampp\php\php.exe C:\xampp\htdocs\etiket-gizi\worker\pekerja.php`.

## 5. Konfigurasi (`config.php`)
| Kunci | Fungsi |
| --- | --- |
| `db` | Koneksi MySQL (bawaan XAMPP: root tanpa kata sandi) |
| `worker_mode` | `spawn` (proses terpisah) atau `poll` (cadangan) |
| `php_cli` | Lokasi `php.exe` bila deteksi otomatis gagal |
| `stiker` | Ukuran stiker dalam mm (bawaan 60 × 40, **ukuran contoh**, sesuaikan dengan printer) |
| `simulasi_latensi_ms` | Opsional untuk uji: tambahan waktu per stiker (ms) untuk meniru perangkat keluaran lambat. Bawaan 0. **Bila dipakai, wajib ditulis di bagian metode jurnal.** |

## 6. Keamanan yang diterapkan
Kata sandi di-hash (`password_hash`), semua kueri memakai *prepared statement*, token CSRF pada setiap permintaan POST,
pembatasan halaman menurut peran, QR Code hanya berisi **ID pesanan** (tanpa data identitas pasien), dan folder `includes`, `lib`,
`worker`, `storage` dilindungi `.htaccess`.

## 7. Struktur folder
```
etiket-gizi/
├─ index.php, login.php, logout.php          Dashboard, masuk, keluar
├─ pasien.php, pesanan.php, etiket.php       Data Pasien, Pesanan Diet, Pembuatan Etiket
├─ antrean.php, distribusi.php, uji_kinerja.php
├─ qr.php, unduh_stiker.php, uji_export.php  QR (SVG), unduh PDF, ekspor CSV
├─ api/                                      Endpoint JSON (ambil_simrs, kirim_antrean, scan, uji_*, ...)
├─ includes/                                 Inti: db, auth, layout, spooler, stiker, simrs
├─ worker/pekerja.php                        Pekerja spooling (proses terpisah)
├─ simrs_tiruan/                             SIMRS tiruan (45 pasien dummy)
├─ lib/                                      FPDF dan phpqrcode
├─ assets/                                   CSS, JS, font, Chart.js, jsQR
├─ storage/stiker/                           PDF stiker yang dihasilkan
└─ database.sql                              Skema dan akun uji
```

## 8. Pemetaan ke jurnal
| Bagian jurnal | Di website |
| --- | --- |
| Use Case: Mengelola Pesanan Diet, Mengambil Data dari SIMRS | Data Pasien, Pesanan Diet, `api/ambil_simrs.php` |
| Use Case: Memperbarui Diet, Memprioritaskan Diet Mendadak | Modal *Perbarui Diet* |
| Use Case: Mencetak Etiket, Mengurutkan Antrean Cetak | Pembuatan Etiket, Antrean Cetak, `includes/spooler.php` |
| Use Case: Memindai QR Code, Memperbarui Status Distribusi | Distribusi, `api/scan.php` |
| Class Diagram | Tabel `pengguna`, `pasien`, `pesanan_diet`, `etiket`, `pekerjaan_cetak`, `status_distribusi` |
| Tabel 2 (metrik pengujian) | Halaman Uji Kinerja: waktu respons, waktu total, waktu tunggu stiker mendadak |
| Pengujian *black box* | Seluruh halaman di atas (isi Template Lembar Pengujian) |

## 9. Batasan
Data dummy; SIMRS tiruan; berjalan di server lokal; tidak mencetak ke printer; hasil uji kinerja hanya menggambarkan perilaku rancangan.
Kamera pemindai QR memerlukan `localhost` atau HTTPS dan izin kamera dari peramban.
