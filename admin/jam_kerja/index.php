<?php

require_once '../../config/database.php';
require_once '../../config/helper.php';

check_login();

/*
|--------------------------------------------------------------------------
| JADWAL PRESENSI SEKOLAH
|--------------------------------------------------------------------------
| 1 = Senin
| 2 = Selasa
| 3 = Rabu
| 4 = Kamis
| 5 = Jumat
| 6 = Sabtu
| 7 = Minggu
|--------------------------------------------------------------------------
*/

$jadwal = [

    1 => [
        'hari'   => 'Senin',
        'masuk'  => '07:00:00',
        'pulang' => '14:45:00',
        'aktif'  => true
    ],

    2 => [
        'hari'   => 'Selasa',
        'masuk'  => '07:00:00',
        'pulang' => '15:10:00',
        'aktif'  => true
    ],

    3 => [
        'hari'   => 'Rabu',
        'masuk'  => '07:00:00',
        'pulang' => '15:10:00',
        'aktif'  => true
    ],

    4 => [
        'hari'   => 'Kamis',
        'masuk'  => '07:00:00',
        'pulang' => '15:10:00',
        'aktif'  => true
    ],

    5 => [
        'hari'   => 'Jumat',
        'masuk'  => '07:00:00',
        'pulang' => '12:00:00',
        'aktif'  => true
    ],

    6 => [
        'hari'   => 'Sabtu',
        'masuk'  => '-',
        'pulang' => '-',
        'aktif'  => false
    ],

    7 => [
        'hari'   => 'Minggu',
        'masuk'  => '-',
        'pulang' => '-',
        'aktif'  => false
    ]

];


/*
|--------------------------------------------------------------------------
| WAKTU SEKARANG
|--------------------------------------------------------------------------
*/

$hariSekarang = (int) date('N');
$jadwalHariIni = $jadwal[$hariSekarang];


/*
|--------------------------------------------------------------------------
| FORMAT JAM
|--------------------------------------------------------------------------
*/

function format_jam($jam)
{
    if ($jam === '-' || empty($jam)) {
        return '-';
    }

    return date('H:i', strtotime($jam));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jadwal Presensi | Sistem Presensi</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">

    <!-- Font Awesome untuk ikon -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

    <!-- SIDEBAR / NAVIGASI -->
    <header class="topbar">

        <div class="brand">
            <div class="brand-logo">
                <i class="fa-solid fa-school"></i>
            </div>

            <div class="brand-text">
                <strong>SISTEM PRESENSI</strong>
                <span>SMK NEGERI</span>
            </div>
        </div>

        <nav class="nav">

            <a href="../index.php">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>

            <a href="../presensi/index.php">
                <i class="fa-regular fa-clock"></i>
                <span>Presensi</span>
            </a>

            <a href="../user/index.php">
                <i class="fa-solid fa-users"></i>
                <span>User</span>
            </a>

            <a href="../kelas/index.php">
                <i class="fa-solid fa-book-open"></i>
                <span>Kelas</span>
            </a>

            <a href="index.php" class="active">
                <i class="fa-regular fa-calendar-days"></i>
                <span>Jadwal Presensi</span>
            </a>

            <a href="../perizinan/index.php">
                <i class="fa-solid fa-file-circle-check"></i>
                <span>Perizinan</span>
            </a>

            <a href="../laporan/index.php">
                <i class="fa-solid fa-chart-column"></i>
                <span>Laporan</span>
            </a>

            <a href="../../auth/logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>

        </nav>

    </header>

    <!-- KONTEN UTAMA -->
    <main class="container">

        <div class="schedule-page">

            <!-- JUDUL -->
            <section class="hero">

                <div>
                    <h1>
                        <i class="fa-regular fa-calendar-days"></i>
                        Jadwal Presensi
                    </h1>

                    <p class="muted">
                        Kelola informasi jadwal masuk dan pulang siswa.
                    </p>
                </div>

            </section>

            <!-- JAM REALTIME -->
            <section class="clock-card">

                <div class="clock-icon">
                    <i class="fa-regular fa-clock"></i>
                </div>

                <div class="clock-info">
                    <h3>Waktu Sekarang</h3>

                    <p>
                        <?= e($jadwalHariIni['hari']) ?>,
                        <?= date('d-m-Y') ?>
                    </p>
                </div>

                <div class="clock" id="realtimeClock">
                    <?= date('H:i:s') ?>
                </div>

            </section>

            <!-- JADWAL SEKOLAH -->
            <section class="schedule-card">

                <div class="schedule-heading">

                    <div class="schedule-heading-icon">
                        <i class="fa-solid fa-calendar-week"></i>
                    </div>

                    <div>
                        <h2 class="schedule-title">
                            Jadwal Sekolah
                        </h2>

                        <p class="schedule-subtitle">
                            Informasi jam masuk dan pulang setiap hari.
                        </p>
                    </div>

                </div>

                <div class="schedule-table-wrapper">

                    <table class="schedule-table">

                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($jadwal as $nomorHari => $data): ?>

                                <tr class="<?= $nomorHari === $hariSekarang ? 'today-row' : '' ?>">

                                    <td>
                                        <div class="day-cell">

                                            <span class="day-name">
                                                <?= e($data['hari']) ?>
                                            </span>

                                            <?php if ($nomorHari === $hariSekarang): ?>
                                                <span class="today-badge">
                                                    Hari Ini
                                                </span>
                                            <?php endif; ?>

                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($data['aktif']): ?>

                                            <span class="time-badge time-in">
                                                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                                <?= format_jam($data['masuk']) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="holiday">—</span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($data['aktif']): ?>

                                            <span class="time-badge time-out">
                                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                                <?= format_jam($data['pulang']) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="holiday">—</span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($data['aktif']): ?>

                                            <span class="status-school">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Sekolah
                                            </span>

                                        <?php else: ?>

                                            <span class="status-holiday">
                                                <i class="fa-solid fa-calendar-xmark"></i>
                                                Libur
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- INFORMASI -->
                <div class="schedule-note">
                    <div class="note-icon">
                         <p> <i class="fa-solid fa-circle-info"></i>
                        Siswa melakukan <strong>Absen Hadir</strong>
                        saat datang dan <strong>Absen Pulang</strong>
                        saat pulang. Waktu presensi dicatat sesuai
                        waktu tombol presensi ditekan.
                    </p>
                    </div>
                </div>

            </section>

        </div>

    </main>


<!-- =====================================================
     REALTIME CLOCK
===================================================== -->

<script>

function updateClock() {

    const now = new Date();

    const jam =
        String(now.getHours()).padStart(2, '0');

    const menit =
        String(now.getMinutes()).padStart(2, '0');

    const detik =
        String(now.getSeconds()).padStart(2, '0');

    const clock =
        document.getElementById('realtimeClock');

    if (clock) {

        clock.textContent =
            jam + ':' + menit + ':' + detik;

    }

}

updateClock();

setInterval(updateClock, 1000);

</script>


</body>

</html>