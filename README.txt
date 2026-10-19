# Project Presensi - PHP Native

Versi ini menggunakan **PHP Native + MySQLi + HTML/CSS**.

Konsep aplikasi: **Admin + User (Siswa)**. Tidak ada guru, jabatan, atau sistem shift.
Tidak menggunakan Laravel, CodeIgniter, React, Vue, Bootstrap, atau framework PHP lainnya.

## Struktur
- `config/database.php` - koneksi MySQL
- `config/helper.php` - session, autentikasi, redirect, flash message
- `auth/` - login/logout
- `admin/` - dashboard, user, kelas, jadwal presensi, perizinan, dan laporan
- `user/` - dashboard, presensi, pengajuan izin/sakit
- `uploads/izin/` - bukti pengajuan
- `assets/css/style.css` - tampilan
- `database.sql` - struktur dan data awal database
- `database_update.sql` - migrasi dari versi lama yang masih memiliki jabatan/shift

## Database
Default:
- Host: `127.0.0.1`
- Port: `3309`
- Database: `db_presensi`
- User: `root`
- Password: kosong

Jika MySQL XAMPP menggunakan port lain, sesuaikan `$port` pada `config/database.php`.

## URL
Default project:
`/project-presensi`

Jika nama folder berbeda, ubah:
`BASE_URL` pada `config/helper.php`.

## Login awal
Username: `admin`
Password: `admin123`

============================================================
FACE VECTOR PYTHON
============================================================
Pembuatan dan verifikasi face vector sekarang dilakukan oleh Python,
bukan JavaScript face-api.js.

Folder: python_face_api/
API: POST /encode dan POST /verify
Default: http://127.0.0.1:5001

PHP config/helper.php memiliki:
FACE_API_URL = http://127.0.0.1:5001

Untuk hosting shared PHP, Python API harus dijalankan pada server/VPS
yang dapat diakses dari hosting. Upload folder Python saja tidak cukup.
