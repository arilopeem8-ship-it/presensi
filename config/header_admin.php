<?php
// header_admin.php - Reusable Admin Layout Header
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Determine depth for relative links
$depth = ($_SERVER['PHP_SELF'] === '/project-presensi/admin/index.php' || $current_dir === 'admin') ? '' : '../';
$root  = ($current_dir === 'admin') ? '../' : '../../';

function nav_active($dir) {
    global $current_dir, $current_page;
    if ($dir === 'dashboard' && ($current_page === 'index.php' && $GLOBALS['current_dir'] === 'admin')) return 'active';
    return ($GLOBALS['current_dir'] === $dir) ? 'active' : '';
}

$nav_depth = ($current_dir === 'admin') ? '' : '../';
$auth_path = ($current_dir === 'admin') ? '../auth/logout.php' : '../../auth/logout.php';
$db_path   = ($current_dir === 'admin') ? '../config/database.php' : '../../config/database.php';
$hlp_path  = ($current_dir === 'admin') ? '../config/helper.php'  : '../../config/helper.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Manajemen Presensi - Panel Administrator">
    <title><?= $page_title ?? 'Dashboard' ?> - Admin Presensi</title>
    <link rel="stylesheet" href="<?= ($current_dir === 'admin') ? '../' : '../../' ?>assets/css/style.css">
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">P</div>
            <div class="sidebar-brand">Pre<span>sensi</span></div>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-label">Main Menu</li>
            <li>
                <a href="<?= $nav_depth ?>index.php" class="sidebar-link <?= ($current_dir === 'admin') ? 'active' : '' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="menu-label">Master Data</li>
            <li>
                <a href="<?= $nav_depth ?>user/index.php" class="sidebar-link <?= nav_active('user') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <span>Data User</span>
                </a>
            </li>
            <li>
                <a href="<?= $nav_depth ?>kelas/index.php" class="sidebar-link <?= nav_active('kelas') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Zm10 0a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6ZM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2Zm10 0a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2Z"/></svg>
                    <span>Data Kelas</span>
                </a>
            </li>
            <li>
                <a href="<?= $nav_depth ?>jabatan/index.php" class="sidebar-link <?= nav_active('jabatan') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    <span>Data Jabatan</span>
                </a>
            </li>
            <li>
                <a href="<?= $nav_depth ?>jam_kerja/index.php" class="sidebar-link <?= nav_active('jam_kerja') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    <span>Jam Kerja</span>
                </a>
            </li>

            <li class="menu-label">Presensi</li>
            <li>
                <a href="<?= $nav_depth ?>perizinan/index.php" class="sidebar-link <?= nav_active('perizinan') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    <span>Verifikasi Izin</span>
                </a>
            </li>
            <li>
                <a href="<?= $nav_depth ?>laporan/index.php" class="sidebar-link <?= nav_active('laporan') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    <span>Rekap Laporan</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-profile-badge">
                <div class="avatar"><?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'A', 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?></div>
                    <div class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <div class="topbar">
            <h1 class="topbar-title"><?= $page_title ?? 'Dashboard' ?></h1>
            <div class="topbar-actions">
                <span style="font-size:13px;color:var(--text-muted);" id="topbar-clock"></span>
                <a href="<?= $auth_path ?>" class="btn-logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/></svg>
                    Logout
                </a>
            </div>
        </div>
        <div class="content-body">
