<?php
// Konfigurasi aplikasi E-Ticket Gizi. Sesuaikan bila perlu.
return [
    'db' => [
        'host'    => '127.0.0.1',
        'name'    => 'etiket_gizi',
        'user'    => 'root',
        'pass'    => '',            // XAMPP bawaan: kosong
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'nama' => 'E-Ticket Gizi',
        'rs'   => 'RS Islam Hj. Siti Muniroh',
        'tz'   => 'Asia/Jakarta',
    ],
    // Alamat SIMRS tiruan. null = otomatis (http://localhost/etiket-gizi/simrs_tiruan/pasien_aktif.php)
    'simrs_url' => null,

    // Cara menjalankan pekerja spooling:
    //  'spawn' = proses PHP terpisah di latar belakang (disarankan, sesuai konsep spooling)
    //  'poll'  = halaman Antrean memanggil pekerja berkala (cadangan bila exec() dinonaktifkan)
    'worker_mode' => 'spawn',
    // Lokasi php.exe untuk mode 'spawn'. null = deteksi otomatis (XAMPP Windows: C:\xampp\php\php.exe)
    'php_cli' => null,

    // Ukuran stiker thermal (mm) - sesuaikan dengan printer yang direncanakan
    'stiker' => ['lebar_mm' => 60, 'tinggi_mm' => 40],

    // Opsional (uji): tambahan waktu tunggu per stiker dalam milidetik untuk meniru perangkat keluaran yang lambat.
    // Bawaan 0 (tidak ada simulasi). Jika diisi, WAJIB disebut pada bagian metode jurnal.
    'simulasi_latensi_ms' => 0,
];
