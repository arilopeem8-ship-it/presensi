<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * URL dasar aplikasi.
 * Jika folder project di XAMPP diganti, ubah nilai ini saja.
 * Contoh: http://127.0.0.1:8080/project-presensi
 */
define('BASE_URL', '/project-presensi');

// URL layanan Python untuk membuat dan memverifikasi face vector.
// Local: http://127.0.0.1:5001
// Hosting: ganti ke URL VPS/server Python kamu.
define('FACE_API_URL', 'http://127.0.0.1:5002');

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function check_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('auth/login.php');
    }
}

function check_admin(): void
{
    check_login();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        redirect('user/index.php');
    }
}

function format_tanggal(?string $tanggal): string
{
    return $tanggal ? date('d-m-Y', strtotime($tanggal)) : '-';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function show_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    echo '<div class="alert ' . e($flash['type']) . '">' .
         e($flash['message']) .
         '</div>';
}

function jumlah_notifikasi_belum_dibaca(mysqli $conn, int $user_id): int
{
    $st = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = ? AND dibaca = 0");
    if (!$st) return 0;
    mysqli_stmt_bind_param($st, 'i', $user_id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    return (int)($row['total'] ?? 0);
}
