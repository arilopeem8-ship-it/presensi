
<?php
require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

$uid = (int) ($_SESSION['user_id'] ?? 0);

// Tandai satu notifikasi sebagai dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tandai_dibaca'])) {
    $id = (int) $_POST['tandai_dibaca'];

    $st = mysqli_prepare(
        $conn,
        "UPDATE notifikasi SET dibaca = 1 WHERE id = ? AND user_id = ?"
    );

    if ($st) {
        mysqli_stmt_bind_param($st, 'ii', $id, $uid);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }

    header('Location: notifikasi.php');
    exit;
}

// Tandai semua notifikasi sebagai dibaca
if (isset($_GET['semua'])) {
    $st = mysqli_prepare(
        $conn,
        "UPDATE notifikasi SET dibaca = 1 WHERE user_id = ?"
    );

    if ($st) {
        mysqli_stmt_bind_param($st, 'i', $uid);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }

    header('Location: notifikasi.php');
    exit;
}

// Ambil semua notifikasi milik user yang sedang login
$st = mysqli_prepare(
    $conn,
    "SELECT * FROM notifikasi
     WHERE user_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($st, 'i', $uid);
mysqli_stmt_execute($st);
$list = mysqli_stmt_get_result($st);

// Hitung notifikasi belum dibaca
$belumDibaca = 0;

while ($row = mysqli_fetch_assoc($list)) {
    if ((int) $row['dibaca'] === 0) {
        $belumDibaca++;
    }
}

$notifCount = $belumDibaca;
mysqli_data_seek($list, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifikasi</title>

    <link rel="stylesheet" href="../assets/css/user.css">

</head>

<body class="user-body">

<div class="user-layout">

    <!-- SIDEBAR -->
    <aside class="user-sidebar">

        <div class="user-brand">
            <div>
                <strong>Sistem Presensi</strong>
                <small>Portal Siswa</small>
            </div>
        </div>
     <nav class="user-nav">

            <a href="index.php">
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

            <a href="notifikasi.php" class="active">
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
            <a href="../auth/logout.php">Logout</a>
        </div>

    </aside>

    <!-- KONTEN UTAMA -->
    <main class="user-main">

        <header class="user-header">
            <div>
                <strong>Notifikasi</strong>
                <p>Informasi terbaru akun kamu</p>
            </div>
        </header>

        <section class="user-content">

            <div class="user-page-title">Notifikasi</div>

            <p class="user-page-subtitle">
                Informasi terbaru tentang akun dan pengajuan izin atau sakit kamu.
            </p>

            <div class="notification-summary">
                <div class="notification-count">
                    <?= $belumDibaca ?> notifikasi belum dibaca
                </div>

                <?php if ($belumDibaca > 0): ?>
                    <a
                        href="notifikasi.php?semua=1"
                        class="btn"
                        onclick="return confirm('Tandai semua notifikasi sebagai sudah dibaca?')"
                    >
                        Tandai Semua Dibaca
                    </a>
                <?php endif; ?>
            </div>

            <div class="user-card">

                <?php if (mysqli_num_rows($list) > 0): ?>

                    <?php while ($n = mysqli_fetch_assoc($list)): ?>

                        <?php
                        $sudahDibaca = (int) $n['dibaca'] === 1;
                        ?>

                        <article class="notification-item <?= !$sudahDibaca ? 'unread' : '' ?>">

                            <div class="notification-top">

                                <h3 class="notification-title">
                                    <?= e($n['judul'] ?? 'Notifikasi') ?>
                                </h3>

                                <?php if (!$sudahDibaca): ?>
                                    <span class="notification-label">Baru</span>
                                <?php endif; ?>

                            </div>

                            <p class="notification-message">
                                <?= nl2br(e($n['pesan'] ?? '')) ?>
                            </p>

                            <div class="notification-date">
                                <?php
                                if (!empty($n['created_at'])) {
                                    echo e(date(
                                        'd-m-Y H:i',
                                        strtotime($n['created_at'])
                                    ));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>

                            <?php if (!$sudahDibaca): ?>
                                <div class="notification-actions">
                                    <form method="post">
                                        <button
                                            type="submit"
                                            name="tandai_dibaca"
                                            value="<?= (int) $n['id'] ?>"
                                            class="btn"
                                        >
                                            Tandai Dibaca
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>

                        </article>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="notification-empty">
                        <strong>Belum ada notifikasi</strong>
                        <p>Notifikasi akun kamu akan muncul di halaman ini.</p>
                    </div>

                <?php endif; ?>

            </div>

            <div class="user-footer">
                Sistem Presensi Siswa
            </div>

        </section>

    </main>

</div>

</body>
</html>
