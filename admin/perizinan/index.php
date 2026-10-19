
<?php

require_once '../../config/database.php';
require_once '../../config/helper.php';

check_admin();

/*
|--------------------------------------------------------------------------
| Proses Verifikasi Perizinan
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $id = (int) ($_POST['id'] ?? 0) ;$status = $_POST['status'] ?? ''; $catatan = trim($_POST['catatan_admin'] ?? '');
     if (!in_array($status, ['disetujui', 'ditolak'], true)) { flash('error', 'Status tidak valid.'); header('Location: index.php'); exit; }
      mysqli_begin_transaction($conn);
    try {
        /*
        |--------------------------------------------------------------------------
        | Update Status Perizinan
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare( $conn, "UPDATE perizinan SET status = ?, catatan_admin = ? WHERE id = ?" );
        mysqli_stmt_bind_param(  $stmt,'ssi',$status, $catatan,$id );
        mysqli_stmt_execute($stmt);

        /*
        |--------------------------------------------------------------------------
        | Ambil Data Perizinan
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare( $conn, "SELECT * FROM perizinan WHERE id = ?" );

        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $perizinan = mysqli_fetch_assoc($result);

        if ($status === 'disetujui' && $perizinan) {$tanggal_mulai = new DateTime($perizinan['tanggal_mulai']); $tanggal_selesai = new DateTime($perizinan['tanggal_selesai']);
            $status_presensi = ($perizinan['jenis'] === 'izin') ? 'izin': 'sakit';
            /*
            |--------------------------------------------------------------------------
            | Proses Setiap Hari
            |--------------------------------------------------------------------------
            */
            while ($tanggal_mulai <= $tanggal_selesai) {$tanggal = $tanggal_mulai->format('Y-m-d'); $user_id = (int) $perizinan['user_id'];

                $stmt = mysqli_prepare( $conn, "SELECT id FROM presensi WHERE user_id = ? AND tanggal = ? LIMIT 1");
                mysqli_stmt_bind_param( $stmt, 'is', $user_id, $tanggal );

                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $presensi = mysqli_fetch_assoc($result);

                if ($presensi) {$presensi_id = (int) $presensi['id']; 
                $stmt_update = mysqli_prepare( $conn, "UPDATE presensi SET status = ?, jam_masuk = NULL, jam_pulang = NULL  WHERE id = ?" );
                    mysqli_stmt_bind_param( $stmt_update,'si',  $status_presensi, $presensi_id );
                    mysqli_stmt_execute($stmt_update);
                } else {

                    $stmt_insert = mysqli_prepare( $conn, "INSERT INTO presensi ( user_id, tanggal, status ) VALUES (?, ?, ?)" );
                    mysqli_stmt_bind_param( $stmt_insert, 'iss', $user_id, $tanggal, $status_presensi);
                    mysqli_stmt_execute($stmt_insert);
                }
                $tanggal_mulai->modify('+1 day');
            }
        }
        // Kirim notifikasi ke siswa setelah pengajuan diputuskan.
        if ($perizinan) {
            $judul = $status === 'disetujui' ? 'Pengajuan izin disetujui' : 'Pengajuan izin ditolak';
            $pesan = $status === 'disetujui'
                ? 'Pengajuan ' . $perizinan['jenis'] . ' tanggal ' . format_tanggal($perizinan['tanggal_mulai']) . ' s/d ' . format_tanggal($perizinan['tanggal_selesai']) . ' telah disetujui admin.'
                : 'Pengajuan ' . $perizinan['jenis'] . ' tanggal ' . format_tanggal($perizinan['tanggal_mulai']) . ' s/d ' . format_tanggal($perizinan['tanggal_selesai']) . ' ditolak admin.';
            if ($catatan !== '') { $pesan .= ' Catatan: ' . $catatan; }
            $notif = mysqli_prepare($conn, "INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES (?, ?, ?, ?)");
            $tipe = $status === 'disetujui' ? 'success' : 'error';
            mysqli_stmt_bind_param($notif, 'isss', $perizinan['user_id'], $judul, $pesan, $tipe);
            mysqli_stmt_execute($notif);
        }

        mysqli_commit($conn);
        flash('success','Pengajuan berhasil diperbarui.');
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        flash('error', 'Gagal memproses pengajuan.' );
    }
    header('Location: index.php');
    exit;
}

$list = mysqli_query( $conn, "SELECT  p.*, u.username, u.nama_lengkap FROM perizinan p JOIN users u
        ON u.id = p.user_id ORDER BY p.id DESC" );

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Verifikasi Perizinan | Sistem Presensi</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<header class="topbar">

    <div class="brand">
        <div class="brand-logo">
            <i class="fa-solid fa-school"></i>
        </div>

        <div class="brand-text">
            <strong>SISTEM PRESENSI</strong>
            <span>ADMINISTRATOR</span>
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

        <a href="../jam_kerja/index.php">
            <i class="fa-regular fa-calendar-days"></i>
            <span>Jadwal Presensi</span>
        </a>

        <a href="index.php" class="active">
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

<main class="container">

    <!-- JUDUL HALAMAN -->
    <section class="hero">
        <div>
            <h1>
                <i class="fa-solid fa-file-circle-check"></i>
                Verifikasi Perizinan
            </h1>

            <p class="muted">
                Periksa pengajuan izin dan sakit siswa, lalu tentukan statusnya.
            </p>
        </div>
    </section>

    <!-- PESAN NOTIFIKASI -->
    <?php show_flash(); ?>

    <!-- DAFTAR PERIZINAN -->
    <section class="card permission-card">

        <div class="section-heading">
            <div class="section-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>

            <div>
                <h3>Daftar Pengajuan Perizinan</h3>
                <p class="muted">
                    Periksa informasi pengajuan dan lakukan verifikasi.
                </p>
            </div>
        </div>

        <div class="permission-table-wrapper">

            <table class="permission-table">

                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th>Verifikasi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($list) > 0): ?>

                        <?php while ($r = mysqli_fetch_assoc($list)): ?>

                            <?php
                                $status = $r['status'];

                                $statusClass = 'status-pending';
                                $statusIcon = 'fa-hourglass-half';

                                if ($status === 'disetujui') {
                                    $statusClass = 'status-approved';
                                    $statusIcon = 'fa-circle-check';
                                } elseif ($status === 'ditolak') {
                                    $statusClass = 'status-rejected';
                                    $statusIcon = 'fa-circle-xmark';
                                }
                            ?>

                            <tr>

                                <!-- PENGGUNA -->
                                <td>
                                    <div class="permission-user">
                                        <div class="permission-avatar">
                                            <i class="fa-solid fa-user"></i>
                                        </div>

                                        <div>
                                            <strong>
                                                <?= e($r['nama_lengkap']); ?>
                                            </strong>

                                            <span>
                                                @<?= e($r['username']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- JENIS IZIN -->
                                <td>
                                    <span class="permission-type">
                                        <?= e(ucfirst($r['jenis'])); ?>
                                    </span>
                                </td>

                                <!-- TANGGAL -->
                                <td>
                                    <div class="permission-date">
                                        <strong>
                                            <?= format_tanggal($r['tanggal_mulai']); ?>
                                        </strong>

                                        <span>
                                            s/d <?= format_tanggal($r['tanggal_selesai']); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- ALASAN -->
                                <td>
                                    <div class="permission-reason">
                                        <?= nl2br(e($r['alasan'])); ?>
                                    </div>
                                </td>

                                <!-- BUKTI -->
                                <td>
                                    <?php if (!empty($r['bukti_surat'])): ?>

                                        <a
                                            href="../../uploads/izin/<?= rawurlencode($r['bukti_surat']); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-proof"
                                        >
                                            <i class="fa-solid fa-paperclip"></i>
                                            Lihat Bukti
                                        </a>

                                    <?php else: ?>

                                        <span class="permission-no-proof">
                                            <i class="fa-regular fa-file"></i>
                                            Tidak ada
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="permission-status <?= $statusClass; ?>">
                                        <i class="fa-solid <?= $statusIcon; ?>"></i>
                                        <?= e(ucfirst($status)); ?>
                                    </span>
                                </td>

                                <!-- VERIFIKASI -->
                                <td>
                                    <?php if ($status === 'pending'): ?>

                                        <form method="post" class="permission-verify-form">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= e($r['id']); ?>"
                                            >

                                            <label class="visually-hidden" for="status-<?= e($r['id']); ?>">
                                                Pilih status
                                            </label>

                                            <select
                                                id="status-<?= e($r['id']); ?>"
                                                name="status"
                                                required
                                            >
                                                <option value="">Pilih status</option>
                                                <option value="disetujui">Setujui</option>
                                                <option value="ditolak">Tolak</option>
                                            </select>

                                            <label class="visually-hidden" for="catatan-<?= e($r['id']); ?>">
                                                Catatan admin
                                            </label>

                                            <input
                                                type="text"
                                                id="catatan-<?= e($r['id']); ?>"
                                                name="catatan_admin"
                                                placeholder="Catatan admin (opsional)"
                                            >

                                            <button type="submit" class="btn btn-verify">
                                                <i class="fa-solid fa-floppy-disk"></i>
                                                Simpan
                                            </button>

                                        </form>

                                    <?php else: ?>

                                        <div class="permission-admin-note">
                                            <i class="fa-regular fa-comment-dots"></i>

                                            <?= e($r['catatan_admin'] ?? '-'); ?>
                                        </div>

                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7">
                                <div class="permission-empty">
                                    <i class="fa-regular fa-folder-open"></i>

                                    <strong>Belum ada pengajuan perizinan</strong>

                                    <span>
                                        Pengajuan izin atau sakit akan muncul di sini.
                                    </span>
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
