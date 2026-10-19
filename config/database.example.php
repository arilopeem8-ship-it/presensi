<?php

mysqli_report(MYSQLI_REPORT_OFF);

date_default_timezone_set('Asia/Jakarta');

// =====================================================
// CONTOH KONFIGURASI DATABASE
// =====================================================
// Ganti nilai di bawah sesuai server masing-masing.
// JANGAN masukkan password database asli ke GitHub.
// =====================================================

$host = "localhost";
$port = 3306;

$user = "USERNAME_DATABASE";
$pass = "PASSWORD_DATABASE";
$db   = "NAMA_DATABASE";

// =====================================================
// KONEKSI DATABASE
// =====================================================

$conn = mysqli_connect(
    $host,
    $user,
    $pass,
    $db,
    $port
);

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