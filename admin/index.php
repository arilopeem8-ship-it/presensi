
<?php

require_once '../config/database.php';
require_once '../config/helper.php';

check_admin();

/*
|--------------------------------------------------------------------------
| Statistik Dashboard
|--------------------------------------------------------------------------
*/

// Total User
$result = mysqli_query( $conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
$users = mysqli_fetch_assoc($result)['total'];
// Total Presensi Hari Ini
$result = mysqli_query( $conn, "SELECT COUNT(*) AS total FROM presensi WHERE tanggal = CURDATE()");
$presensi_hari_ini = mysqli_fetch_assoc($result)['total'];
// Total Perizinan Pending
$result = mysqli_query( $conn,  "SELECT COUNT(*) AS total FROM perizinan WHERE status = 'pending'");
$izin_pending = mysqli_fetch_assoc($result)['total'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport"content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>
</head>
<body>

<header class="topbar">


    <!-- BRAND / LOGO SEKOLAH -->
    <div class="brand">
        <div class="brand-logo">
        
        </div>

        <div class="brand-text">
            <strong>SISTEM PRESENSI</strong>
            <span>SMKN 1 KRAKSAAN</span>
        </div>
    </div>

    <!-- MENU ADMIN -->
    <nav class="nav">

        <a href="index.php" class="active">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <a href="presensi/index.php">
            <i class="fa-regular fa-clock"></i>
            <span>Presensi</span>
        </a>

        <a href="user/index.php">
            <i class="fa-solid fa-users"></i>
            <span>User</span>
        </a>

        <a href="kelas/index.php">
            <i class="fa-solid fa-book-open"></i>
            <span>Kelas</span>
        </a>

        <a href="jam_kerja/index.php">
            <i class="fa-regular fa-calendar-days"></i>
            <span>Jadwal Presensi</span>
        </a>

        <a href="perizinan/index.php">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Perizinan</span>
        </a>

        <a href="laporan/index.php">
            <i class="fa-solid fa-chart-column"></i>
            <span>Laporan</span>
        </a>

        <a href="../auth/logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>

</header>

<main class="container">

    <!-- HEADER DASHBOARD -->
    <section class="hero dashboard-hero">
        
        <div>
            <span class="dashboard-greeting">
             <div class="form-grid attendance-filter-grid">
                <input
                    type="search"
                    id="searchDashboard"
                    placeholder="Cari informasi..."
                    aria-label="Cari informasi"
                >
            </div>
            </span>

            <h1>Dashboard Admin</h1>

            <p class="muted">
                Selamat datang kembali,
                <strong>
                    <?= e($_SESSION['nama_lengkap'] ?? 'Admin'); ?>
                </strong>.
                Kelola presensi sekolah dengan mudah.
            </p>
        </div>
    </section>

    <!-- STATISTIK -->
    <section class="grid dashboard-stats">

        <!-- TOTAL USER -->
        <article class="card stat-card stat-users">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Total User</p>
                    <div class="stat"><?= e($users); ?></div>
                </div>

                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <div class="stat-info">
                <i class="fa-solid fa-user-check"></i>
                Pengguna terdaftar
            </div>
        </article>

        <!-- PRESENSI HARI INI -->
        <article class="card stat-card stat-attendance">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Presensi Hari Ini</p>
                    <div class="stat"><?= e($presensi_hari_ini); ?></div>
                </div>

                <div class="stat-icon">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
            </div>

            <div class="stat-info">
                <i class="fa-solid fa-clock"></i>
                Data presensi hari ini
            </div>
        </article>

        <!-- IZIN MENUNGGU -->
        <article class="card stat-card stat-permission">
            <div class="stat-top">
                <div>
                    <p class="stat-label">Izin Menunggu</p>
                    <div class="stat"><?= e($izin_pending); ?></div>
                </div>

                <div class="stat-icon">
                    <i class="fa-solid fa-file-circle-exclamation"></i>
                </div>
            </div>

            <div class="stat-info">
                <i class="fa-solid fa-hourglass-half"></i>
                Perlu diverifikasi
            </div>
        </article>

    </section>

    <!-- MENU UTAMA -->
    <section class="card menu-card">

        <div class="menu-heading">
            <div>
                <h2>
                    <i class="fa-solid fa-grip"></i>
                    Menu Utama
                </h2>

                <p class="muted">
                    Pilih menu yang ingin kamu kelola.
                </p>
            </div>
        </div>

        <div class="actions dashboard-actions">

            <a class="dashboard-action" href="user/index.php">
                <span class="action-icon">
                    <i class="fa-solid fa-users-gear"></i>
                </span>

                <span class="action-text">
                    <strong>Kelola User</strong>
                    <small>Atur data pengguna</small>
                </span>

                <i class="fa-solid fa-chevron-right action-arrow"></i>
            </a>

            <a class="dashboard-action" href="perizinan/index.php">
                <span class="action-icon">
                    <i class="fa-solid fa-file-circle-check"></i>
                </span>

                <span class="action-text">
                    <strong>Verifikasi Perizinan</strong>
                    <small>Periksa pengajuan izin siswa</small>
                </span>

                <i class="fa-solid fa-chevron-right action-arrow"></i>
            </a>

            <a class="dashboard-action" href="laporan/index.php">
                <span class="action-icon">
                    <i class="fa-solid fa-chart-column"></i>
                </span>

                <span class="action-text">
                    <strong>Lihat Laporan</strong>
                    <small>Lihat rekap data presensi</small>
                </span>

                <i class="fa-solid fa-chevron-right action-arrow"></i>
            </a>

        </div>

    </section>

</main>
</body>
</html>
