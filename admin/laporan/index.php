
<?php
require_once '../../config/database.php';
require_once '../../config/helper.php';
check_admin();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$kelas_id = (int)($_GET['kelas'] ?? 0);
$user_id = (int)($_GET['siswa'] ?? 0);
$status = $_GET['status'] ?? '';

$kelasRows = [];
$kr = mysqli_query($conn, "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
while ($r = mysqli_fetch_assoc($kr)) $kelasRows[] = $r;

$userRows = [];
$ur = mysqli_query($conn, "SELECT id, nama_lengkap, id_kelas FROM users WHERE role='user' ORDER BY nama_lengkap");
while ($r = mysqli_fetch_assoc($ur)) $userRows[] = $r;

$where = ["p.tanggal BETWEEN ? AND ?"];
$types = 'ss';
$params = [$from, $to];

if ($kelas_id > 0) {
    $where[] = "u.id_kelas = ?";
    $types .= 'i';
    $params[] = $kelas_id;
}

if ($user_id > 0) {
    $where[] = "p.user_id = ?";
    $types .= 'i';
    $params[] = $user_id;
}

if (in_array($status, ['tepat_waktu', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
    $where[] = "p.status = ?";
    $types .= 's';
    $params[] = $status;
}

$sql = "SELECT p.tanggal, u.nama_lengkap, u.username, k.nama_kelas,
        p.jam_masuk, p.jam_pulang, p.status
        FROM presensi p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN kelas k ON k.id = u.id_kelas
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.tanggal DESC, u.nama_lengkap ASC";

$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$list = mysqli_stmt_get_result($st);

$exportQuery = http_build_query([
    'from' => $from,
    'to' => $to,
    'kelas' => $kelas_id,
    'siswa' => $user_id,
    'status' => $status
]);

function status_label($s) {
    return match($s) {
        'tepat_waktu' => 'Tepat Waktu',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpa' => 'Alpa',
        default => ucfirst($s)
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Presensi | Sistem Presensi</title>

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
        <a href="../presensi/index.php">
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
        <a href="index.php" class="active">
            <i class="fa-solid fa-chart-column"></i> Laporan
        </a>
        
        <a href="../auth/logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>
</header>

<main class="container report-page">

    <div class="page-heading">
        <div>
            <h1>
                <i class="fa-solid fa-chart-column"></i>
                Laporan Rekap Presensi
            </h1>

        </div>
    </div>

    <!-- FILTER LAPORAN -->
    <section class="card filter-card">

        <div class="section-heading">
            <div class="section-icon">
             
            </div>
            <div>
                <h3>Filter Laporan</h3>
                <p>Tentukan periode dan kriteria data yang ingin ditampilkan.</p>
            </div>
        </div>

        <form method="get">

            <div class="form-grid report-filter-grid">

                <div class="form-group">
                    <label for="from">Dari Tanggal</label>
                    <div class="input-icon">
                       
                        <input type="date" id="from" name="from"
                               value="<?= e($from) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="to">Sampai Tanggal</label>
                    <div class="input-icon">
                       
                        <input type="date" id="to" name="to"
                               value="<?= e($to) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="kelas">Kelas</label>
                    <div class="input-icon select-icon">
                      
                        <select id="kelas" name="kelas">
                            <option value="0">Semua Kelas</option>
                            <?php foreach ($kelasRows as $k): ?>
                                <option value="<?= e($k['id']) ?>"
                                    <?= $kelas_id == $k['id'] ? 'selected' : '' ?>>
                                    <?= e($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="siswa">Siswa</label>
                    <div class="input-icon select-icon">
                      
                        <select id="siswa" name="siswa">
                            <option value="0">Semua Siswa</option>
                            <?php foreach ($userRows as $u): ?>
                                <option value="<?= e($u['id']) ?>"
                                    <?= $user_id == $u['id'] ? 'selected' : '' ?>>
                                    <?= e($u['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status Kehadiran</label>
                    <div class="input-icon select-icon">
                       
                        <select id="status" name="status">
                            <option value="">Semua Status</option>

                            <?php foreach ([
                                'tepat_waktu' => 'Tepat Waktu',
                                'terlambat' => 'Terlambat',
                                'izin' => 'Izin',
                                'sakit' => 'Sakit',
                                'alpa' => 'Alpa'
                            ] as $v => $l): ?>
                                <option value="<?= e($v) ?>"
                                    <?= $status === $v ? 'selected' : '' ?>>
                                    <?= e($l) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>

            <div class="report-actions no-print">
                <button class="btn report-filter-btn" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Tampilkan
                </button>

                <button class="btn report-print-btn" type="button"
                        onclick="window.print()">
                    <i class="fa-solid fa-file-pdf"></i>
                    Cetak / PDF
                </button>

                <a class="btn report-export-btn"
                   href="export.php?<?= e($exportQuery) ?>">
                    <i class="fa-solid fa-file-excel"></i>
                    Export Excel
                </a>

                <a class="btn report-reset-btn" href="index.php">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>
            </div>

        </form>
    </section>

    <!-- DATA LAPORAN -->
    <section class="card report-data-card">

        <div class="section-heading report-data-heading">
            <div class="section-icon">
                <i class="fa-solid fa-table-list"></i>
            </div>
            <div>
                <h3>Data Presensi</h3>
                <p>Rekap kehadiran sesuai filter yang dipilih.</p>
            </div>
        </div>

        <div class="table-wrap report-table-wrap">
            <table class="table report-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $no = 1; ?>

                    <?php if (mysqli_num_rows($list) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($list)): ?>
                            <tr>
                                <td class="report-number">
                                    <?= $no++ ?>
                                </td>

                                <td class="report-date">
                                    <?= e(format_tanggal($r['tanggal'])) ?>
                                </td>

                                <td>
                                    <div class="report-student">
                                        <span class="report-avatar">
                                            <i class="fa-solid fa-user"></i>
                                        </span>
                                        <span>
                                            <?= e($r['nama_lengkap']) ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <span class="report-class">
                                        <?= e($r['nama_kelas'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="report-time">
                                        <?= e($r['jam_masuk'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="report-time">
                                        <?= e($r['jam_pulang'] ?? '-') ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $statusValue = $r['status'] ?? '';
                                    $statusClass = 'report-status-default';

                                    if ($statusValue === 'tepat_waktu') {
                                        $statusClass = 'report-status-ontime';
                                    } elseif ($statusValue === 'terlambat') {
                                        $statusClass = 'report-status-late';
                                    } elseif ($statusValue === 'izin') {
                                        $statusClass = 'report-status-permission';
                                    } elseif ($statusValue === 'sakit') {
                                        $statusClass = 'report-status-sick';
                                    } elseif ($statusValue === 'alpa') {
                                        $statusClass = 'report-status-absent';
                                    }
                                    ?>

                                    <span class="report-status <?= e($statusClass) ?>">
                                        <?= e(status_label($statusValue)) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="report-empty">
                                <i class="fa-solid fa-folder-open"></i>
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