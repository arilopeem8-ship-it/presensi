USE db_presensi;

-- Lokasi sekolah dan radius presensi.
ALTER TABLE jam_kerja
    ADD COLUMN IF NOT EXISTS latitude_sekolah DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS longitude_sekolah DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS radius_meter INT NOT NULL DEFAULT 100;

-- Token login khusus aplikasi Android.
CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_tokens_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Face descriptor hasil model face recognition di Android.
CREATE TABLE IF NOT EXISTS face_profiles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    descriptor_json LONGTEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data lokasi dan hasil verifikasi wajah pada setiap presensi.
ALTER TABLE presensi
    ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL,
    ADD COLUMN IF NOT EXISTS accuracy_meter DECIMAL(8,2) NULL,
    ADD COLUMN IF NOT EXISTS face_verified TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS face_similarity DECIMAL(8,5) NULL,
    ADD COLUMN IF NOT EXISTS distance_meter DECIMAL(10,2) NULL;

-- CONTOH: isi koordinat sekolah. GANTI dengan koordinat sekolah sebenarnya.
-- UPDATE jam_kerja SET latitude_sekolah = -7.0000000, longitude_sekolah = 112.0000000, radius_meter = 100 WHERE id = 1;
