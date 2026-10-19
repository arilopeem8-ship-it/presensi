<?php
require_once '../../config/database.php';
require_once '../../config/helper.php';
check_admin();
$edit = null;
//Ambil Data Untuk Edit
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = mysqli_prepare(  $conn, "SELECT * FROM kelas WHERE id = ?" );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit = mysqli_fetch_assoc($result);
}
 //Hapus Data
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = mysqli_prepare(  $conn,  "DELETE FROM kelas WHERE id = ?" );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        flash('success', 'Data kelas berhasil dihapus.');
    } else {
        flash('warning', 'Data kelas tidak ditemukan.');
    }
    header('Location: index.php');
    exit;
}
 //Tambah / Edit Daata
if ($_SERVER['REQUEST_METHOD'] === 'POST') {$id = (int) ($_POST['id'] ?? 0);$nama_kelas = trim($_POST['nama_kelas'] ?? '');
     //Validasi
    if ($nama_kelas === '') {
        flash('error', 'Nama kelas wajib diisi.');
    } elseif ($id > 0) {
        // Update Data
        $stmt = mysqli_prepare( $conn, "UPDATE kelas SET nama_kelas = ? WHERE id = ?" );
        mysqli_stmt_bind_param( $stmt,'si', $nama_kelas, $id );
        mysqli_stmt_execute($stmt);
        flash('success', 'Data kelas berhasil diubah.');
    } else {
       //Tambah Data
        $stmt = mysqli_prepare( $conn,"INSERT INTO kelas (nama_kelas) VALUES (?)");
        mysqli_stmt_bind_param( $stmt,'s',$nama_kelas);
        mysqli_stmt_execute($stmt);
        flash('success', 'Data kelas berhasil ditambahkan.');
    }
    header('Location: index.php');
    exit;
}
 // Ambil Semua Data Kelas
$list = mysqli_query(  $conn,"SELECT * FROM kelas ORDER BY id DESC");?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Data Kelas | Sistem Presensi</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

    <!-- NAVIGASI ADMIN -->
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

            <a href="index.php" class="active">
                <i class="fa-solid fa-book-open"></i>
                <span>Kelas</span>
            </a>

            <a href="../jam_kerja/index.php">
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

        <!-- JUDUL -->
        <section class="hero">

            <div>
                <h1>
                    <i class="fa-solid fa-book-open"></i>
                    Data Kelas
                </h1>

        </section>

        <!-- NOTIFIKASI -->
        <?php show_flash(); ?>

        <!-- FORM TAMBAH / EDIT -->
        <section class="card class-form-card">

            <div class="section-heading">

                <div>
                    <h3>
                        <?= $edit ? 'Edit Data Kelas' : 'Tambah Data Kelas'; ?>
                    </h3>

                    <p class="muted">
                        Isi nama kelas sesuai data sekolah.
                    </p>
                </div>

            </div>

            <form method="post">

                <input
                    type="hidden"
                    name="id"
                    value="<?= e($edit['id'] ?? 0); ?>"
                >

                <div class="form-group">
                    <label for="nama_kelas">Nama Kelas</label>

                    <input
                        type="text"
                        id="nama_kelas"
                        name="nama_kelas"
                        value="<?= e($edit['nama_kelas'] ?? ''); ?>"
                        placeholder="Contoh: XI PPLG 1"
                        required
                    >
                </div>

                <div class="class-form-actions">

                    <button type="submit" class="btn">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <?= $edit ? 'Simpan Perubahan' : 'Tambah Kelas'; ?>
                    </button>

                    <?php if ($edit): ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fa-solid fa-xmark"></i>
                            Batal
                        </a>
                    <?php endif; ?>

                </div>

            </form>

        </section>

        <!-- TABEL DATA KELAS -->
        <section class="card class-list-card">

            <div class="section-heading">

                <div class="section-icon">
                    <i class="fa-solid fa-list"></i>
                </div>

                <div>
                    <h3>Daftar Kelas</h3>

                    <p class="muted">
                        Daftar kelas yang tersimpan dalam sistem.
                    </p>
                </div>

            </div>

            <div class="class-table-wrapper">

                <table class="class-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($r = mysqli_fetch_assoc($list)): ?>

                            <tr>

                                <td>
                                    <span class="class-id">
                                        #<?= e($r['id']); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="class-name">
                                        <span class="class-name-icon">
                                            <i class="fa-solid fa-school"></i>
                                        </span>

                                        <strong>
                                            <?= e($r['nama_kelas']); ?>
                                        </strong>
                                    </div>
                                </td>

                                <td>
                                    <div class="class-actions">

                                        <a
                                            href="?edit=<?= e($r['id']); ?>"
                                            class="btn btn-edit"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            Edit
                                        </a>

                                        <a
                                            href="?delete=<?= e($r['id']); ?>"
                                            class="btn btn-delete"
                                            onclick="return confirm('Yakin ingin menghapus data kelas ini?')"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            Hapus
                                        </a>

                                    </div>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>
</html>
