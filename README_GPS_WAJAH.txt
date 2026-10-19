FITUR PRESENSI KAMERA + GPS
===========================

File utama:
- user/absen.php          : halaman kamera, GPS, jadwal, tombol hadir/pulang
- user/proses_absen.php   : validasi server, Haversine, upload foto, simpan presensi
- uploads/absensi/        : folder foto presensi
- database_update_presensi_gps.sql : migrasi database lama

ALUR ABSEN
----------
1. Siswa membuka user/absen.php.
2. Browser meminta izin kamera dan lokasi.
3. GPS dihitung dengan Haversine terhadap tabel lokasi.
4. Tombol absen hanya aktif jika jarak <= radius dan foto sudah diambil.
5. Saat form dikirim, server menghitung ulang jarak GPS. Jadi bypass dari browser tetap ditolak.
6. Foto Base64 diubah menjadi JPEG dan disimpan di uploads/absensi/.
7. Presensi masuk menyimpan jam, foto, latitude, longitude, dan status tepat_waktu/terlambat.
8. Presensi pulang menyimpan jam, foto, latitude, longitude.

PENTING
-------
- Kamera dan GPS pada hosting harus menggunakan HTTPS. localhost juga dapat digunakan untuk pengujian.
- Koordinat contoh pada database adalah -6.200000, 106.816666 dengan radius 100 meter. Ganti data tabel lokasi sesuai koordinat sekolah sebenarnya.
- Fitur ini mengambil snapshot wajah dari kamera. Ini BELUM melakukan pencocokan biometrik/face recognition terhadap face_vector karena database yang diberikan belum menyediakan proses/enrollment model wajah. Snapshot foto tetap disimpan sebagai bukti presensi.
- Pastikan folder uploads/absensi dapat ditulis oleh PHP.
