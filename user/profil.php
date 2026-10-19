
<?php
require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

$uid = (int) ($_SESSION['user_id'] ?? 0);
$notifCount = jumlah_notifikasi_belum_dibaca($conn, $uid);

$pesan = '';
$tipe = '';

// Proses pembaruan profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($nama === '' || $username === '') {
        $pesan = 'Nama lengkap dan username wajib diisi.';
        $tipe = 'error';
    } elseif (strlen($nama) > 150 || strlen($username) > 100) {
        $pesan = 'Nama atau username terlalu panjang.';
        $tipe = 'error';
    } elseif ($password !== '' && strlen($password) < 6) {
        $pesan = 'Password baru minimal 6 karakter.';
        $tipe = 'error';
    } elseif ($password !== '' && $password !== $konfirmasi) {
        $pesan = 'Konfirmasi password tidak sama.';
        $tipe = 'error';
    } else {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $st = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET nama_lengkap = ?, username = ?, password = ?
                 WHERE id = ?"
            );

            if ($st) {
                mysqli_stmt_bind_param(
                    $st,
                    'sssi',
                    $nama,
                    $username,
                    $hash,
                    $uid
                );
            }
        } else {
            $st = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET nama_lengkap = ?, username = ?
                 WHERE id = ?"
            );

            if ($st) {
                mysqli_stmt_bind_param(
                    $st,
                    'ssi',
                    $nama,
                    $username,
                    $uid
                );
            }
        }

        if (!$st) {
            $pesan = 'Permintaan pembaruan profil gagal disiapkan.';
            $tipe = 'error';
        } elseif (mysqli_stmt_execute($st)) {
            $_SESSION['nama_lengkap'] = $nama;
            $_SESSION['username'] = $username;

            $pesan = 'Profil berhasil diperbarui.';
            $tipe = 'success';

            mysqli_stmt_close($st);
        } else {
            if (mysqli_stmt_errno($st) === 1062) {
                $pesan = 'Username sudah digunakan. Silakan gunakan username lain.';
            } else {
                $pesan = 'Gagal memperbarui profil. Silakan coba kembali.';
            }

            $tipe = 'error';
            mysqli_stmt_close($st);
        }
    }
}

// Ambil informasi akun terbaru
$st = mysqli_prepare(
    $conn,
    "SELECT
        u.nama_lengkap,
        u.username,
        u.role,
        k.nama_kelas
     FROM users u
     LEFT JOIN kelas k ON k.id = u.id_kelas
     WHERE u.id = ?"
);

if (!$st) {
    die('Query profil gagal disiapkan.');
}

mysqli_stmt_bind_param($st, 'i', $uid);
mysqli_stmt_execute($st);

$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

mysqli_stmt_close($st);

if (!$profil) {
    die('Data profil tidak ditemukan.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Profil Saya</title>

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
            <strong>Sistem Presensi</strong>
            Panel presensi siswa
        </div>

    </aside>

    <!-- KONTEN UTAMA -->
    <main class="user-main">

        <header class="user-header">
            <div>
                <strong>Profil Saya</strong>
                <small>Informasi dan pengaturan akun</small>
            </div>
        </header>

        <div class="user-content">

            <h1 class="user-page-title">Profil Saya</h1>

            <p class="user-page-subtitle">
                Lihat informasi akun dan perbarui data pribadimu.
            </p>

            <?php if ($pesan !== ''): ?>
                <div class="user-alert <?= e($tipe) ?>">
                    <?= e($pesan) ?>
                </div>
            <?php endif; ?>

            <!-- INFORMASI DAN FORM PROFIL -->
            <section class="user-card">

                <h2 class="profile-section-title">
                    Informasi Akun
                </h2>

                <p class="profile-description">
                    Pastikan nama dan username kamu sudah benar.
                </p>

                <form method="post">

                    <div class="user-form-grid">

                        <div class="user-form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>

                            <input
                                type="text"
                                id="nama_lengkap"
                                name="nama_lengkap"
                                value="<?= e($profil['nama_lengkap']) ?>"
                                maxlength="150"
                                autocomplete="name"
                                required
                            >
                        </div>

                        <div class="user-form-group">
                            <label for="username">Username</label>

                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= e($profil['username']) ?>"
                                maxlength="100"
                                autocomplete="username"
                                required
                            >
                        </div>

                        <div class="user-form-group">
                            <label for="kelas">Kelas</label>

                            <input
                                type="text"
                                id="kelas"
                                value="<?= e($profil['nama_kelas'] ?? 'Belum ditentukan') ?>"
                                disabled
                            >
                        </div>

                        <div class="user-form-group">
                            <label for="role">Role Akun</label>

                            <input
                                type="text"
                                id="role"
                                value="<?= e(ucfirst($profil['role'])) ?>"
                                disabled
                            >
                        </div>

                    </div>

                    <hr class="profile-divider">

                    <h2 class="profile-section-title">
                        Ganti Password
                    </h2>

                    <p class="profile-description">
                        Kosongkan kedua kolom password jika tidak ingin
                        mengganti password.
                    </p>

                    <div class="user-form-grid">

                        <div class="user-form-group">
                            <label for="password">Password Baru</label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="Masukkan password baru"
                            >
                        </div>

                        <div class="user-form-group">
                            <label for="konfirmasi">Konfirmasi Password</label>

                            <input
                                type="password"
                                id="konfirmasi"
                                name="konfirmasi"
                                minlength="6"
                                autocomplete="new-password"
                                placeholder="Ulangi password baru"
                            >
                        </div>

                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn">
                            Simpan Perubahan
                        </button>
                    </div>

                    <div class="profile-info-note">
                        Kelas dan role hanya dapat dilihat di halaman ini.
                        Untuk mengubahnya, hubungi admin.
                    </div>

                </form>

            </section>

            <footer class="user-footer">
                <span>Sistem Presensi Siswa</span>
                <span>Halaman Profil Saya</span>
            </footer>

        </div>

    </main>

</div>

</body>
</html>
