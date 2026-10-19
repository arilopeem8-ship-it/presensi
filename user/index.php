
<?php

require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

$uid = (int) ($_SESSION['user_id'] ?? 0);
$namaUser = $_SESSION['nama_lengkap'] ?? 'Siswa';
$today = date('Y-m-d');

// =====================================================
// NOTIFIKASI
// =====================================================

$notifCount = 0;

if (function_exists('jumlah_notifikasi_belum_dibaca')) {
    $notifCount = (int) jumlah_notifikasi_belum_dibaca(
        $conn,
        $uid
    );
}

$notifikasi = false;

$notifSt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM notifikasi
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 3"
);

if ($notifSt) {
    mysqli_stmt_bind_param($notifSt, 'i', $uid);
    mysqli_stmt_execute($notifSt);
    $notifikasi = mysqli_stmt_get_result($notifSt);
}

// =====================================================
// PRESENSI HARI INI
// =====================================================

$todayRow = false;

$st = mysqli_prepare(
    $conn,
    "SELECT *
     FROM presensi
     WHERE user_id = ?
     AND tanggal = ?
     LIMIT 1"
);

if (!$st) {
    die('Query presensi gagal: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($st, 'is', $uid, $today);
mysqli_stmt_execute($st);

$resultToday = mysqli_stmt_get_result($st);

if ($resultToday) {
    $todayRow = mysqli_fetch_assoc($resultToday);
}

// =====================================================
// FUNGSI STATUS
// =====================================================

function label_status(string $status): string
{
    return match ($status) {
        'tepat_waktu' => 'Tepat Waktu',
        'terlambat'   => 'Terlambat',
        'izin'        => 'Izin',
        'sakit'       => 'Sakit',
        'alpa'        => 'Alpa',
        default       => ucfirst(str_replace('_', ' ', $status)),
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'tepat_waktu' => 'success',
        'terlambat'   => 'warning',
        'izin'        => 'info',
        'sakit'       => 'info',
        'alpa'        => 'danger',
        default       => 'info',
    };
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Siswa - Sistem Presensi</title>

    <link rel="stylesheet" href="../assets/css/user.css">
</head>

<body class="user-body">

<div class="user-layout">

    <!-- SIDEBAR -->
    <aside class="user-sidebar">

        <div class="user-brand">


            <div>
                Sistem Presensi
                <small>Area Siswa</small>
            </div>
        </div>

        <nav class="user-nav">

            <a href="index.php" class="active">
                <span class="nav-icon"></span>
                Dashboard
            </a>

            <a href="absen.php">
                <span class="nav-icon"></span>
                Presensi
            </a>

            <a href="pengajuan_izin.php">
                <span class="nav-icon"></span>
                Izin / Sakit
            </a>

            <a href="profil.php">
                <span class="nav-icon"></span>
                Profil Saya
            </a>

            <a href="wajah.php">
                <span class="nav-icon"></span>
                Daftar Wajah
            </a>

            <a href="notifikasi.php">
                <span class="nav-icon"></span>
                Notifikasi

                <?php if ($notifCount > 0): ?>
                    <span class="nav-badge">
                        <?= e($notifCount) ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="../auth/logout.php">
                <span class="nav-icon"></span>
                Logout
            </a>

        </nav>

        <div class="user-sidebar-footer">
            <strong>Disiplin Hari Ini</strong>
            Untuk Masa Depan Nanti
        </div>

    </aside>

    <!-- KONTEN UTAMA -->
    <main class="user-main">

        <!-- HEADER -->
        <header class="user-header">

            <div class="user-search">
                <input
                    type="search"
                    id="searchDashboard"
                    placeholder="Cari informasi..."
                    aria-label="Cari informasi"
                >
            </div>

            <div class="user-header-profile">

                <div class="user-avatar">
                    <?= e(strtoupper(substr($namaUser, 0, 1))) ?>
                </div>

                <div>
                    <strong><?= e($namaUser) ?></strong>
                    <small>Siswa</small>
                </div>

            </div>

        </header>

        <div class="user-content">

            <!-- JUDUL -->
            <h1 class="user-page-title">
                Selamat Datang, <?= e($namaUser) ?>
            </h1>

            <p class="user-page-subtitle">
                Pantau kehadiran dan kelola kebutuhan presensimu
                melalui dashboard ini.
            </p>

            <!-- BANNER -->
            <section class="user-welcome">

                <div class="user-welcome-content">

                    <div class="user-welcome-label">
                        DASHBOARD SISWA
                    </div>

                    <h1>
                        Presensi Mudah,
                        Belajar Lebih Teratur
                    </h1>

                    <p>
                        Jangan lupa melakukan presensi hari ini.
                        Tetap semangat dalam belajar.
                    </p>

                    <div style="margin-top: 18px;">
                        <a href="absen.php" class="btn">
                            Buka Presensi
                        </a>
                    </div>

                </div>
                    
                <div class="user-welcome-image">
                  <img src="../assets/logo.png" alt="Logo Sekolah">
                </div>


            </section>

            <!-- KARTU AKSI CEPAT -->
            <section class="user-grid">

                <article class="user-card user-card-action">

                    <div class="user-card-icon">
                        PM
                    </div>

                    <h3>Presensi Masuk</h3>

                    <p>
                        Lakukan presensi saat tiba di sekolah.
                    </p>

                    <a href="absen.php" class="user-card-link">
                        Buka Presensi
                    </a>

                </article>

                <article class="user-card user-card-action">

                    <div class="user-card-icon green">
                        IP
                    </div>

                    <h3>Izin dan Sakit</h3>

                    <p>
                        Ajukan izin jika tidak dapat hadir.
                    </p>

                    <a href="pengajuan_izin.php" class="user-card-link">
                        Ajukan Izin
                    </a>

                </article>

                <article class="user-card user-card-action">

                    <div class="user-card-icon purple">
                        PF
                    </div>

                    <h3>Profil Saya</h3>

                    <p>
                        Lihat informasi akun dan data diri.
                    </p>

                    <a href="profil.php" class="user-card-link">
                        Lihat Profil
                    </a>

                </article>

                <article class="user-card user-card-action">

                    <div class="user-card-icon orange">
                        WF
                    </div>

                    <h3>Data Wajah</h3>

                    <p>
                        Kelola pendaftaran wajah untuk presensi.
                    </p>

                    <a href="wajah.php" class="user-card-link">
                        Periksa Data Wajah
                    </a>

                </article>

            </section>

            <!-- AREA INFORMASI -->
            <section class="user-dashboard-columns">

                <!-- STATUS PRESENSI -->
                <article class="user-card">

                    <div class="dashboard-section-heading">
                        <div>
                            <h2>Status Presensi Hari Ini</h2>
                            <p>
                                <?= e(date('d-m-Y')) ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($todayRow): ?>

                        <?php
                        $statusHariIni = (string) (
                            $todayRow['status'] ?? ''
                        );

                        $classHariIni = status_class($statusHariIni);
                        ?>

                        <div class="user-status-box <?= e($classHariIni) ?>">

                            <div class="user-status-content">
                                <strong>
                                    <?= e(label_status($statusHariIni)) ?>
                                </strong>

                                <p>
                                    Presensi hari ini sudah tercatat.
                                </p>
                            </div>

                        </div>

                        <div class="user-attendance-details">

                            <div>
                                <span>Jam Masuk</span>

                                <strong>
                                    <?= e($todayRow['jam_masuk'] ?? '-') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Jam Pulang</span>

                                <strong>
                                    <?= e($todayRow['jam_pulang'] ?? '-') ?>
                                </strong>
                            </div>

                        </div>

                    <?php else: ?>

                        <div class="user-status-box warning">

                            <div class="user-status-content">
                                <strong>Belum Presensi</strong>

                                <p>
                                    Kamu belum melakukan presensi
                                    untuk hari ini.
                                </p>
                            </div>

                        </div>

                        <a href="absen.php" class="btn">
                            Absen Sekarang
                        </a>

                    <?php endif; ?>

                </article>

                <!-- NOTIFIKASI -->
                <article class="user-card">

                    <div class="dashboard-section-heading">

                        <div>
                            <h2>Notifikasi Terbaru</h2>

                            <p>
                                Informasi terbaru untukmu.
                            </p>
                        </div>

                        <a href="notifikasi.php" class="user-card-link">
                            Lihat Semua
                        </a>

                    </div>

                    <div class="user-list">

                        <?php if (
                            $notifikasi &&
                            mysqli_num_rows($notifikasi) > 0
                        ): ?>

                            <?php while (
                                $n = mysqli_fetch_assoc($notifikasi)
                            ): ?>

                                <div class="user-list-item">

                                    <div class="user-notification-content">

                                        <strong>
                                            <?= e($n['judul'] ?? 'Notifikasi') ?>
                                        </strong>

                                        <?php
                                        $belumDibaca =
                                            isset($n['dibaca']) &&
                                            (string) $n['dibaca'] === '0';
                                        ?>

                                        <?php if ($belumDibaca): ?>
                                            <span class="user-badge warning">
                                                Baru
                                            </span>
                                        <?php endif; ?>

                                        <p>
                                            <?= e($n['pesan'] ?? '') ?>
                                        </p>

                                        <?php if (!empty($n['created_at'])): ?>
                                            <small>
                                                <?php
                                                $waktu = strtotime($n['created_at']);

                                                echo $waktu !== false
                                                    ? e(date('d-m-Y H:i', $waktu))
                                                    : '-';
                                                ?>
                                            </small>
                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <p class="user-empty-message">
                                Belum ada notifikasi terbaru.
                            </p>

                        <?php endif; ?>

                    </div>

                </article>

            </section>

            <!-- FOOTER -->
            <footer class="user-footer">

                <span>
                    Sistem Presensi Siswa
                </span>

                <span>
                    Disiplin Hari Ini, Sukses Esok Hari
                </span>

            </footer>

        </div>

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('searchDashboard');

    if (!input) return;

    input.addEventListener('input', function () {
        const keyword = this.value.toLowerCase().trim();

        document.querySelectorAll(
            '.user-card, .user-welcome'
        ).forEach(function (element) {
            element.style.display =
                element.textContent.toLowerCase().includes(keyword) ||
                keyword === ''
                    ? ''
                    : 'none';
        });
    });
});
</script>

</body>
</html>
