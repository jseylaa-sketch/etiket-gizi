-- =====================================================================
-- Basis data E-Ticket Gizi  (mengikuti Class Diagram pada jurnal)
-- Semua data bersifat dummy. Kata sandi akun uji: gizi12345
-- =====================================================================
CREATE DATABASE IF NOT EXISTS etiket_gizi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE etiket_gizi;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS hasil_uji;
DROP TABLE IF EXISTS log_aktivitas;
DROP TABLE IF EXISTS status_distribusi;
DROP TABLE IF EXISTS pekerjaan_cetak;
DROP TABLE IF EXISTS etiket;
DROP TABLE IF EXISTS pesanan_diet;
DROP TABLE IF EXISTS pasien;
DROP TABLE IF EXISTS pengguna;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE pengguna (
  id_pengguna   INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  peran         ENUM('dokter_perawat','ahli_gizi','petugas_distribusi') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE pasien (
  id_pasien INT AUTO_INCREMENT PRIMARY KEY,
  no_rm     VARCHAR(20)  NOT NULL UNIQUE,
  nama      VARCHAR(100) NOT NULL,
  kamar     VARCHAR(30)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE pesanan_diet (
  id_pesanan    VARCHAR(25) PRIMARY KEY,            -- isi QR Code (hanya ID pesanan)
  id_pasien     INT NOT NULL,
  jenis_diet    VARCHAR(50) NOT NULL,
  jadwal_makan  ENUM('Pagi','Siang','Sore') NOT NULL,
  diet_mendadak TINYINT(1) NOT NULL DEFAULT 0,
  waktu_ubah    DATETIME NULL,
  catatan       VARCHAR(255) NULL,
  status        ENUM('baru','diperbarui','siap_cetak','dalam_antrean','selesai') NOT NULL DEFAULT 'siap_cetak',
  FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien) ON DELETE CASCADE,
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE etiket (
  id_etiket    INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan   VARCHAR(25) NOT NULL UNIQUE,
  kode_qr      VARCHAR(25) NOT NULL,
  tgl_cetak    DATETIME NULL,
  status_cetak ENUM('belum','dibuat') NOT NULL DEFAULT 'belum',
  berkas_pdf   VARCHAR(255) NULL,
  FOREIGN KEY (id_pesanan) REFERENCES pesanan_diet(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pekerjaan_cetak (
  id_pekerjaan  INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan    VARCHAR(25) NOT NULL,
  prioritas     TINYINT NOT NULL DEFAULT 2,         -- 1 = tinggi (diet mendadak), 2 = normal
  mode          ENUM('spooling','langsung') NOT NULL DEFAULT 'spooling',
  status        ENUM('antre','diproses','selesai','gagal') NOT NULL DEFAULT 'antre',
  waktu_masuk   DATETIME(3) NOT NULL,
  waktu_mulai   DATETIME(3) NULL,
  waktu_selesai DATETIME(3) NULL,
  id_uji        INT NULL,                           -- terisi bila pekerjaan berasal dari uji kinerja
  FOREIGN KEY (id_pesanan) REFERENCES pesanan_diet(id_pesanan) ON DELETE CASCADE,
  INDEX idx_antrean (status, prioritas, waktu_masuk), -- urutan: prioritas, lalu FCFS
  INDEX idx_uji (id_uji)
) ENGINE=InnoDB;

CREATE TABLE status_distribusi (
  id_status   INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan  VARCHAR(25) NOT NULL,
  tahap       ENUM('diporsi','dalam_pengiriman','diterima') NOT NULL,
  waktu       DATETIME NOT NULL,
  id_pengguna INT NULL,
  FOREIGN KEY (id_pesanan)  REFERENCES pesanan_diet(id_pesanan) ON DELETE CASCADE,
  FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE log_aktivitas (
  id_log      INT AUTO_INCREMENT PRIMARY KEY,
  waktu       DATETIME NOT NULL,
  id_pengguna INT NULL,
  aksi        VARCHAR(60) NOT NULL,
  detail      VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Hasil uji kinerja (dasar Tabel hasil pada jurnal)
CREATE TABLE hasil_uji (
  id_uji                   INT AUTO_INCREMENT PRIMARY KEY,
  kelompok                 VARCHAR(30) NOT NULL,
  skenario                 ENUM('langsung','spooling_fcfs','spooling_prioritas') NOT NULL,
  jumlah_stiker            INT NOT NULL,
  ulangan_ke               INT NOT NULL,
  waktu_respons_ms         DECIMAL(10,1) NOT NULL,
  waktu_total_ms           DECIMAL(10,1) NULL,
  waktu_tunggu_mendadak_ms DECIMAL(10,1) NULL,
  status                   ENUM('berjalan','selesai') NOT NULL DEFAULT 'berjalan',
  dibuat                   DATETIME NOT NULL
) ENGINE=InnoDB;

-- Pengguna dummy (kata sandi semuanya: gizi12345)
INSERT INTO pengguna (nama, username, password_hash, peran) VALUES
('Perawat Contoh',            'perawat',    '$2y$10$aJAMRCyQzSs2FqXnBTJSFeXmTSvo5mL0XlIDUl2pG3JzsJEqXqzSC', 'dokter_perawat'),
('Ahli Gizi Contoh',          'gizi',       '$2y$10$aJAMRCyQzSs2FqXnBTJSFeXmTSvo5mL0XlIDUl2pG3JzsJEqXqzSC', 'ahli_gizi'),
('Petugas Distribusi Contoh', 'distribusi', '$2y$10$aJAMRCyQzSs2FqXnBTJSFeXmTSvo5mL0XlIDUl2pG3JzsJEqXqzSC', 'petugas_distribusi');

-- Data pasien dan pesanan diet TIDAK diisi di sini.
-- Keduanya diambil dari SIMRS tiruan lewat tombol "Ambil Data SIMRS" pada website.
