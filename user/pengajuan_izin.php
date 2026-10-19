
<?php
require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

date_default_timezone_set('Asia/Jakarta');

$uid = (int) ($_SESSION['user_id'] ?? 0);
$notifCount = jumlah_notifikasi_belum_dibaca($conn, $uid);

// Proses pengajuan izin atau sakit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis   = $_POST['jenis'] ?? '';
    $mulai   = $_POST['tanggal_mulai'] ?? '';
    $selesai = $_POST['tanggal_selesai'] ?? '';
    $alasan  = trim($_POST['alasan'] ?? '');
    $file    = null;

    // Validasi data
    $tanggalValid = (
        $mulai !== '' &&
        $selesai !== '' &&
        DateTime::createFromFormat('!Y-m-d', $mulai) !== false &&
        DateTime::createFromFormat('!Y-m-d', $selesai) !== false &&
        DateTime::createFromFormat('!Y-m-d', $mulai)->format('Y-m-d') === $mulai &&
        DateTime::createFromFormat('!Y-m-d', $selesai)->format('Y-m-d') === $selesai
    );

    if (
        !in_array($jenis, ['izin', 'sakit'], true) ||
        !$tanggalValid ||
        $alasan === '' ||
        $selesai < $mulai
    ) {
        flash('error', 'Data pengajuan belum lengkap atau tanggal tidak valid.');
        header('Location: pengajuan_izin.php');
        exit;
    }

    // Cek apakah sudah ada presensi pada tanggal pengajuan
    $stPresensi = mysqli_prepare(
        $conn,
        "SELECT tanggal
         FROM presensi
         WHERE user_id = ?
           AND tanggal BETWEEN ? AND ?
         LIMIT 1"
    );

    if (!$stPresensi) {
        die('Query pengecekan presensi gagal.');
    }

    mysqli_stmt_bind_param($stPresensi, 'iss', $uid, $mulai, $selesai);
    mysqli_stmt_execute($stPresensi);
    $presensiBentrok = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stPresensi)
    );
    mysqli_stmt_close($stPresensi);

    if ($presensiBentrok) {
        flash(
            'error',
            'Pengajuan tidak dapat dibuat. Kamu sudah melakukan presensi pada tanggal ' .
            format_tanggal($presensiBentrok['tanggal']) .
            '. Satu tanggal tidak boleh memiliki presensi dan izin/sakit sekaligus.'
        );

        header('Location: pengajuan_izin.php');
        exit;
    }

    // Cek pengajuan yang masih pending atau sudah disetujui
    $stIzin = mysqli_prepare(
        $conn,
        "SELECT jenis, status, tanggal_mulai, tanggal_selesai
         FROM perizinan
         WHERE user_id = ?
           AND status IN ('pending', 'disetujui')
           AND tanggal_mulai <= ?
           AND tanggal_selesai >= ?
         LIMIT 1"
    );

    if (!$stIzin) {
        die('Query pengecekan pengajuan gagal.');
    }

    mysqli_stmt_bind_param($stIzin, 'iss', $uid, $selesai, $mulai);
    mysqli_stmt_execute($stIzin);
    $izinBentrok = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stIzin)
    );
    mysqli_stmt_close($stIzin);

    if ($izinBentrok) {
        flash(
            'error',
            'Tanggal pengajuan bertabrakan dengan pengajuan ' .
            ucfirst($izinBentrok['jenis']) .
            ' yang masih berstatus ' .
            $izinBentrok['status'] .
            '. Silakan gunakan tanggal lain.'
        );

        header('Location: pengajuan_izin.php');
        exit;
    }

    // Upload bukti surat jika disertakan
    if (
        isset($_FILES['bukti_surat']) &&
        $_FILES['bukti_surat']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['bukti_surat']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Upload bukti gagal. Silakan coba kembali.');
            header('Location: pengajuan_izin.php');
            exit;
        }

        $fileName = $_FILES['bukti_surat']['name'];
        $fileSize = $_FILES['bukti_surat']['size'];
        $tmpFile  = $_FILES['bukti_surat']['tmp_name'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'pdf'];

        if (
            !in_array($ext, $allowed, true) ||
            $fileSize > 3 * 1024 * 1024
        ) {
            flash('error', 'Bukti harus JPG/PNG/PDF dan maksimal 3 MB.');
            header('Location: pengajuan_izin.php');
            exit;
        }

        // Periksa tipe file sebenarnya
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmpFile);

        $allowedMime = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'pdf'  => ['application/pdf']
        ];

        if (
            !isset($allowedMime[$ext]) ||
            !in_array($mime, $allowedMime[$ext], true)
        ) {
            flash('error', 'Tipe file bukti tidak valid.');
            header('Location: pengajuan_izin.php');
            exit;
        }

        $dir = __DIR__ . '/../uploads/izin';

        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            flash('error', 'Folder penyimpanan bukti tidak dapat dibuat.');
            header('Location: pengajuan_izin.php');
            exit;
        }

        $file = uniqid('izin_', true) . '.' . $ext;

        if (!move_uploaded_file($tmpFile, $dir . '/' . $file)) {
            flash('error', 'Upload bukti gagal.');
            header('Location: pengajuan_izin.php');
            exit;
        }
    }

    // Simpan pengajuan ke database
    $st = mysqli_prepare(
        $conn,
        "INSERT INTO perizinan
            (user_id, jenis, tanggal_mulai, tanggal_selesai, alasan, bukti_surat)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$st) {
        if ($file !== null && is_file(__DIR__ . '/../uploads/izin/' . $file)) {
            unlink(__DIR__ . '/../uploads/izin/' . $file);
        }

        flash('error', 'Pengajuan gagal disiapkan.');
        header('Location: pengajuan_izin.php');
        exit;
    }

    mysqli_stmt_bind_param(
        $st,
        'isssss',
        $uid,
        $jenis,
        $mulai,
        $selesai,
        $alasan,
        $file
    );

    if (mysqli_stmt_execute($st)) {
        flash('success', 'Pengajuan berhasil dikirim dan menunggu verifikasi admin.');
    } else {
        if ($file !== null && is_file(__DIR__ . '/../uploads/izin/' . $file)) {
            unlink(__DIR__ . '/../uploads/izin/' . $file);
        }

        flash('error', 'Pengajuan gagal dikirim.');
    }

    mysqli_stmt_close($st);

    header('Location: pengajuan_izin.php');
    exit;
}

// Ambil riwayat pengajuan siswa
$st = mysqli_prepare(
    $conn,
    "SELECT *
     FROM perizinan
     WHERE user_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($st, 'i', $uid);
mysqli_stmt_execute($st);
$list = mysqli_stmt_get_result($st);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pengajuan Izin / Sakit</title>

    <!-- CSS khusus user -->
    <link rel="stylesheet" href="../assets/css/user.css">

</head>

<body class="user-body">

<div class="user-layout">

    <!-- SIDEBAR USER -->
    <aside class="user-sidebar">
        <div class="user-brand">
            <div>
                Sistem Presensi
                <small>Panel Siswa</small>
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

            <a href="pengajuan_izin.php" class="active">
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
            <strong>Sistem Presensi</strong>
            Panel presensi siswa
        </div>
    </aside>

    <!-- KONTEN UTAMA -->
    <main class="user-main">

        <header class="user-header">
            <div>
                <strong>Pengajuan Izin / Sakit</strong>
                <small>Kelola pengajuan presensi kamu</small>
            </div>
        </header>

        <div class="user-content">

            <h1 class="user-page-title">Pengajuan Izin / Sakit</h1>
            <p class="user-page-subtitle">
                Ajukan izin atau sakit dan pantau status pengajuanmu.
            </p>

            <?php show_flash(); ?>

            <!-- FORM PENGAJUAN -->
            <section class="user-card" style="margin-bottom: 22px;">

                <h2 class="user-section-heading">Form Pengajuan</h2>
                <p class="user-section-description">
                    Lengkapi data pengajuan dengan benar.
                </p>

                <div class="user-alert">
                    Pengajuan tidak dapat bertabrakan dengan tanggal presensi
                    atau pengajuan lain yang masih menunggu verifikasi maupun
                    sudah disetujui.
                </div>

                <form method="post" enctype="multipart/form-data">

                    <div class="user-form-grid">

                        <div class="user-form-group">
                            <label for="jenis">Jenis Pengajuan</label>
                            <select id="jenis" name="jenis" required>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                            </select>
                        </div>

                        <div class="user-form-group">
                            <label for="bukti_surat">Bukti Surat (Opsional)</label>
                            <input
                                type="file"
                                id="bukti_surat"
                                name="bukti_surat"
                                accept=".jpg,.jpeg,.png,.pdf"
                            >
                            <small class="user-form-hint">
                                Format JPG, PNG, atau PDF. Maksimal 3 MB.
                            </small>
                        </div>

                        <div class="user-form-group">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input
                                type="date"
                                id="tanggal_mulai"
                                name="tanggal_mulai"
                                required
                            >
                        </div>

                        <div class="user-form-group">
                            <label for="tanggal_selesai">Tanggal Selesai</label>
                            <input
                                type="date"
                                id="tanggal_selesai"
                                name="tanggal_selesai"
                                required
                            >
                        </div>

                    </div>

                    <div class="user-form-group">
                        <label for="alasan">Alasan Pengajuan</label>
                        <textarea
                            id="alasan"
                            name="alasan"
                            placeholder="Tuliskan alasan pengajuan izin atau sakit..."
                            required
                        ></textarea>
                    </div>

                    <div class="user-form-actions">
                        <button type="submit" class="btn">
                            Kirim Pengajuan
                        </button>
                    </div>

                </form>
            </section>

            <!-- RIWAYAT PENGAJUAN -->
            <section class="user-card">

                <h2 class="user-section-heading">Riwayat Pengajuan</h2>
                <p class="user-section-description">
                    Daftar pengajuan izin atau sakit yang pernah kamu buat.
                </p>

                <div class="user-table-wrapper">
                    <table class="user-table">

                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Catatan Admin</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($list) > 0): ?>

                                <?php while ($r = mysqli_fetch_assoc($list)): ?>
                                    <tr>
                                        <td>
                                            <?= $r['jenis'] === 'sakit' ? 'Sakit' : 'Izin' ?>
                                        </td>

                                        <td class="user-table-dates">
                                            <?= e(format_tanggal($r['tanggal_mulai'])) ?>
                                            <br>
                                            <span class="muted">sampai</span>
                                            <br>
                                            <?= e(format_tanggal($r['tanggal_selesai'])) ?>
                                        </td>

                                        <td class="user-table-reason">
                                            <?= nl2br(e($r['alasan'])) ?>
                                        </td>

                                        <td>
                                            <?php if ($r['status'] === 'disetujui'): ?>
                                                <span class="user-badge success">
                                                    Disetujui
                                                </span>
                                            <?php elseif ($r['status'] === 'ditolak'): ?>
                                                <span class="user-badge danger">
                                                    Ditolak
                                                </span>
                                            <?php else: ?>
                                                <span class="user-badge warning">
                                                    Menunggu
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= e($r['catatan_admin'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        Belum ada riwayat pengajuan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>

            </section>

            <footer class="user-footer">
                <span>Sistem Presensi Siswa</span>
                <span>Halaman Pengajuan Izin / Sakit</span>
            </footer>

        </div>
    </main>

</div>

</body>
</html>
