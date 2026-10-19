# API Android - Presensi Siswa

Backend PHP lama tetap dipakai. APK Kotlin berkomunikasi melalui folder `/api`.

## Endpoint

- `POST /api/login.php` — login siswa/admin dan mendapatkan Bearer token.
- `GET /api/me.php` — data akun dari token.
- `GET /api/jadwal.php` — jadwal + koordinat sekolah + radius.
- `POST /api/face-register.php` — menyimpan face descriptor siswa.
- `POST /api/presensi.php` — validasi GPS + hasil verifikasi wajah lalu menyimpan presensi.
- `GET /api/riwayat.php` — riwayat presensi siswa.

## Setup

1. Jalankan `android_migration.sql` pada `db_presensi`.
2. Isi koordinat sekolah pada tabel `jam_kerja`.
3. Letakkan project PHP di XAMPP, misalnya `htdocs/project-presensi`.
4. APK Android memakai URL PC/server, bukan `localhost` dari HP.
   - Emulator Android Studio: `http://10.0.2.2/project-presensi/api/`
   - HP fisik: gunakan IP LAN PC, misalnya `http://192.168.1.10/project-presensi/api/`
5. Untuk produksi, gunakan HTTPS.

## Catatan wajah

`face-register.php` dan `presensi.php` sudah menyiapkan tempat untuk face descriptor. APK harus memakai model face recognition yang sama saat membuat descriptor. Jangan menganggap `face_verified=true` dari client sebagai keamanan penuh; untuk deployment nyata, descriptor sebaiknya diverifikasi ulang di server atau memakai mekanisme attestation/anti-spoofing.
