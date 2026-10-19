<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
include '../includes/header.php';

$stmt = $conn->query("SELECT p.*, u.nama_lengkap FROM presensi p JOIN users u ON p.user_id = u.id ORDER BY p.tanggal DESC, p.jam_masuk DESC");
$presensiList = $stmt->fetchAll();
?>

<h2>Dashboard Admin - Monitoring Presensi Siswa</h2>
<table class="table">
    <thead>
        <tr>
            <th>Nama</th>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Koordinat</th>
            <th>Skor Wajah</th>
            <th>Foto</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($presensiList as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
            <td><?= $p['tanggal'] ?></td>
            <td><?= $p['jam_masuk'] ?></td>
            <td><?= $p['lat_masuk'] ?>, <?= $p['long_masuk'] ?></td>
            <td><?= round(($p['similarity_score'] ?? 0) * 100, 1) ?>%</td>
            <td><a href="../uploads/absen/<?= $p['foto_masuk'] ?>" target="_blank">Lihat Foto</a></td>
            <td><span class="badge badge-success"><?= $p['status'] ?></span></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>
