-- =========================================================
-- Jika nama database berbeda, ubah baris USE di bawah.
USE `3086311361_db_presensi`;

-- MIGRASI PRESENSI: LOKASI + KAMERA + GPS
-- Aman dijalankan pada database lama.
-- Database dari db_presensi.sql terbaru sudah memiliki semua
-- bagian ini, sehingga statement yang sudah ada akan dilewati.
-- =========================================================

SET @db := DATABASE();

-- ---------------------------------------------------------
-- 1. TABEL LOKASI
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS lokasi (
    id INT NOT NULL AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    latitude VARCHAR(50) NOT NULL,
    longitude VARCHAR(50) NOT NULL,
    radius INT NOT NULL DEFAULT 100,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO lokasi (nama_lokasi, latitude, longitude, radius)
SELECT 'Sekolah Utama', '-6.200000', '106.816666', 100
WHERE NOT EXISTS (SELECT 1 FROM lokasi);

-- ---------------------------------------------------------
-- 2. KOLOM FOTO + KOORDINAT DI PRESENSI
-- ---------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presensi' AND COLUMN_NAME='foto_masuk')=0,
    'ALTER TABLE presensi ADD COLUMN foto_masuk VARCHAR(255) NULL AFTER jam_masuk', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presensi' AND COLUMN_NAME='lat_masuk')=0,
    'ALTER TABLE presensi ADD COLUMN lat_masuk VARCHAR(50) NULL AFTER foto_masuk', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presensi' AND COLUMN_NAME='long_masuk')=0,
    'ALTER TABLE presensi ADD COLUMN long_masuk VARCHAR(50) NULL AFTER lat_masuk', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presensi' AND COLUMN_NAME='foto_pulang')=0,
    'ALTER TABLE presensi ADD COLUMN foto_pulang VARCHAR(255) NULL AFTER jam_pulang', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presensi' AND COLUMN_NAME='lat_pulang')=0,
    'ALTER TABLE presensi ADD COLUMN lat_pulang VARCHAR(50) NULL AFTER foto_pulang', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='presensi' AND COLUMN_NAME='long_pulang')=0,
    'ALTER TABLE presensi ADD COLUMN long_pulang VARCHAR(50) NULL AFTER lat_pulang', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Catatan: database terbaru juga mempunyai latitude_masuk/longitude_masuk.
-- Kolom tersebut dibiarkan agar kompatibel dengan data lama.


-- ---------------------------------------------------------
-- 3. FACE VECTOR UNTUK VERIFIKASI WAJAH
-- ---------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='face_vector')=0,
    'ALTER TABLE users ADD COLUMN face_vector TEXT NULL COMMENT "JSON descriptor 128 angka hasil face-api.js"', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
