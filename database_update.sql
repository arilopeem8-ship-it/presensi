-- Jalankan file ini SATU KALI jika database project lama sudah terlanjur memakai
-- tabel jabatan atau kolom nama_shift.
USE db_presensi;

-- 1. Hapus foreign key users -> jabatan jika masih ada.
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'id_jabatan'
      AND REFERENCED_TABLE_NAME = 'jabatan'
    LIMIT 1
);

SET @sql = IF(
    @fk_name IS NULL,
    'SELECT 1',
    CONCAT('ALTER TABLE users DROP FOREIGN KEY `', @fk_name, '`')
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Hapus kolom jabatan dari users jika masih ada.
SET @has_id_jabatan = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'id_jabatan'
);
SET @sql = IF(
    @has_id_jabatan = 0,
    'SELECT 1',
    'ALTER TABLE users DROP COLUMN id_jabatan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Hapus tabel jabatan karena tidak dipakai dalam sistem presensi siswa.
DROP TABLE IF EXISTS jabatan;

-- 4. Hapus nama_shift dari jadwal lama.
SET @has_nama_shift = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'jam_kerja'
      AND COLUMN_NAME = 'nama_shift'
);
SET @sql = IF(
    @has_nama_shift = 0,
    'SELECT 1',
    'ALTER TABLE jam_kerja DROP COLUMN nama_shift'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Jadwal lama tetap dipakai. Jika tabel kosong, buat satu jadwal sekolah.
INSERT INTO jam_kerja(jam_masuk,jam_pulang,toleransi_terlambat)
SELECT '07:00:00','15:00:00',15
WHERE NOT EXISTS(SELECT 1 FROM jam_kerja);


-- 6. Notifikasi untuk siswa
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
