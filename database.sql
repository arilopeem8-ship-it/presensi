CREATE DATABASE IF NOT EXISTS db_presensi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_presensi;

-- Sistem hanya memiliki 2 role: admin dan user (siswa).
CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

-- Satu jadwal sekolah, tanpa sistem shift.
CREATE TABLE IF NOT EXISTS jam_kerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jam_masuk TIME NOT NULL,
    jam_pulang TIME NOT NULL,
    toleransi_terlambat INT DEFAULT 15
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    id_kelas INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(id_kelas) REFERENCES kelas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS perizinan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jenis ENUM('izin','sakit') NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    alasan TEXT NOT NULL,
    bukti_surat VARCHAR(255) NULL,
    status ENUM('pending','disetujui','ditolak') DEFAULT 'pending',
    catatan_admin TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS presensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    id_jam_kerja INT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME NULL,
    jam_pulang TIME NULL,
    status ENUM('tepat_waktu','terlambat','izin','sakit','alpa') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(id_jam_kerja) REFERENCES jam_kerja(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_user_tanggal(user_id,tanggal)
) ENGINE=InnoDB;

INSERT INTO kelas(nama_kelas)
SELECT 'XI RPL 1'
WHERE NOT EXISTS(SELECT 1 FROM kelas WHERE nama_kelas='XI RPL 1');

INSERT INTO kelas(nama_kelas)
SELECT 'XI RPL 2'
WHERE NOT EXISTS(SELECT 1 FROM kelas WHERE nama_kelas='XI RPL 2');

INSERT INTO jam_kerja(jam_masuk,jam_pulang,toleransi_terlambat)
SELECT '07:00:00','15:00:00',15
WHERE NOT EXISTS(SELECT 1 FROM jam_kerja);

INSERT INTO users(username,password,nama_lengkap,role)
SELECT 'admin','$2y$12$vPCKAIxK.pNy1.LM8paTj.m.lZKOLD0EDeQD7Hs7ShWsI8Ycbrpw.','Administrator','admin'
WHERE NOT EXISTS(SELECT 1 FROM users WHERE username='admin');

-- Notifikasi siswa
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    tipe VARCHAR(30) DEFAULT 'info',
    dibaca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
