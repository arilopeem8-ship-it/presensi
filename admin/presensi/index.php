
<?php
require_once '../../config/database.php';
require_once '../../config/helper.php';
check_admin();

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $tanggal = date('Y-m-d');
}

$status = $_GET['status'] ?? '';
$kelas = trim($_GET['kelas'] ?? '');

$sql = "SELECT p.*, u.nama_lengkap, u.username, k.nama_kelas
        FROM presensi p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN kelas k ON k.id = u.id_kelas
        WHERE p.tanggal = ?";

$params = [$tanggal];
$types = 's';

if ($status !== '') {
    $sql .= " AND p.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($kelas !== '') {
    $sql .= " AND k.id = ?";
    $params[] = (int)$kelas;
    $types .= 'i';
}

$sql .= " ORDER BY p.jam_masuk ASC";

$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$rows = mysqli_stmt_get_result($st);

$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
?>```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presensi Siswa | Sistem Presensi</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="index.php" class="active">
            <i class="fa-solid fa-clock"></i> Presensi
        </a>
        <a href="../user/index.php">
            <i class="fa-solid fa-users"></i> User
        </a>
        <a href="../kelas/index.php">
            <i class="fa-solid fa-school"></i> Kelas
        </a>
        <a href="../jam_kerja/index.php">
            <i class="fa-solid fa-calendar-days"></i> Jadwal Presensi
        </a>
        <a href="../perizinan/index.php">
            <i class="fa-solid fa-file-circle-check"></i> Perizinan
        </a>
        <a href="../laporan/index.php">
            <i class="fa-solid fa-chart-column"></i> Laporan
        </a>
        
        <a href="../auth/logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>
</header>

<main class="container attendance-page">

    <div class="page-heading">
        <div>
            <h1>
                <i class="fa-solid fa-clock"></i>
                Presensi Siswa
            </h1>
        </div>
    </div>

    <!-- FILTER PRESENSI -->
    <section class="card attendance-filter-card">

        <div class="section-heading">
            <div class="section-icon">

            </div>
            <div>
                <h3>Filter Presensi</h3>
                <p>Pilih kriteria untuk menampilkan data presensi.</p>
            </div>
        </div>

        <form method="get">
            <div class="form-grid attendance-filter-grid">

                <div class="form-group">
                    <label for="tanggal">Tanggal Presensi</label>
                    <div class="input-icon">
                      
                        <input
                            type="date"
                            id="tanggal"
                            name="tanggal"
                            value="<?= e($tanggal) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status Kehadiran</label>
                    <div class="input-icon select-icon">
                      
                        <select id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="tepat_waktu"
                                <?= $status === 'tepat_waktu' ? 'selected' : '' ?>>
                                Tepat Waktu
                            </option>
                            <option value="terlambat"
                                <?= $status === 'terlambat' ? 'selected' : '' ?>>
                                Terlambat
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kelas">Kelas</label>
                    <div class="input-icon select-icon">
                    
                        <select id="kelas" name="kelas">
                            <option value="">Semua Kelas</option>
                            <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                <option
                                    value="<?= e($k['id']) ?>"
                                    <?= $kelas == (string) $k['id'] ? 'selected' : '' ?>>
                                    <?= e($k['nama_kelas']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

            </div>

            <div class="attendance-filter-actions">
                <button type="submit" class="btn attendance-filter-btn">
                    
                    Tampilkan Data
                </button>

                <a href="index.php" class="btn attendance-reset-btn">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset Filter
                </a>
            </div>
        </form>
    </section>

    <!-- TABEL PRESENSI -->
    <section class="card attendance-list-card">

        <div class="section-heading attendance-list-heading">
            <div class="section-icon">
    
            </div>

            <div>
                <h3>Data Presensi</h3>
                <p>
                    Tanggal:
                    <strong><?= e(format_tanggal($tanggal)) ?></strong>
                </p>
            </div>
        </div>

        <div class="table-wrap attendance-table-wrap">
            <table class="table attendance-table">
                <thead>
                    <tr>
                        <th class="attendance-number">No.</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jam Masuk</th>
                        <th>Status Kehadiran</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>

                    <?php if (mysqli_num_rows($rows) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                            <tr>
                                <td class="attendance-number">
                                    <?= $no++ ?>
                                </td>

                                <td>
                                    <div class="attendance-student">
                                        <span class="attendance-avatar">
                                         
                                        </span>
                                        <span class="attendance-student-name">
                                            <?= e($r['nama_lengkap']) ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <span class="attendance-class">
                                        <?= e($r['nama_kelas'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="attendance-time">
                                    
                                        <?= e($r['jam_masuk'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($r['status'] === 'tepat_waktu'): ?>
                                        <span class="attendance-status status-on-time">
                                          
                                            Tepat Waktu
                                        </span>
                                    <?php elseif ($r['status'] === 'terlambat'): ?>
                                        <span class="attendance-status status-late">
                                     
                                            Terlambat
                                        </span>
                                    <?php else: ?>
                                        <span class="attendance-status status-other">
                                            <?= e(ucwords(str_replace('_', ' ', $r['status'] ?? '-'))) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="attendance-empty">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <strong>Tidak ada data presensi</strong>
                                <span>
                                    Tidak ditemukan data untuk filter yang dipilih.
                                </span>
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
```