<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../config/database.php';
include '../includes/header.php';

$stmt = $conn->prepare("SELECT * FROM presensi WHERE user_id = :uid ORDER BY tanggal DESC");
$stmt->execute(['uid' => $_SESSION['user_id']]);
$myPresensi = $stmt->fetchAll();
?>

<h2>Dashboard Siswa - Riwayat Absensi Saya</h2>
<table class="table">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Jam Masuk</th>
            <th>Foto</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($myPresensi as $p): ?>
        <tr>
            <td><?= $p['tanggal'] ?></td>
            <td><?= $p['jam_masuk'] ?></td>
            <td><a href="../uploads/absen/<?= $p['foto_masuk'] ?>" target="_blank">Bukti Selfie</a></td>
            <td><?= $p['status'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>
