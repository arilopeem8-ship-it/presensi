<?php

mysqli_report(MYSQLI_REPORT_OFF);

date_default_timezone_set('Asia/Jakarta');

// =====================================================
// KONFIGURASI DATABASE XAMPP LOCAL
// =====================================================
$host = "localhost";
$port = 3306;
$user = "root";
$pass = "";
$db   = "3086311361_db_presensi";

// =====================================================
// KONEKSI DATABASE
// =====================================================
$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// =====================================================
// CHARACTER SET
// =====================================================
mysqli_set_charset($conn, 'utf8mb4');

// =====================================================
// TIMEZONE WIB
// =====================================================
mysqli_query($conn, "SET time_zone = '+07:00'");