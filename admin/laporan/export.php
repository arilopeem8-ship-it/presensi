
<?php
require_once '../../config/database.php';
require_once '../../config/helper.php';
check_admin();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$kelas = (int)($_GET['kelas'] ?? 0);
$siswa = (int)($_GET['siswa'] ?? 0);
$status = $_GET['status'] ?? '';

$where = ["p.tanggal BETWEEN ? AND ?"];
$types = 'ss';
$params = [$from, $to];

if ($kelas > 0) {
    $where[] = 'u.id_kelas=?';
    $types .= 'i';
    $params[] = $kelas;
}

if ($siswa > 0) {
    $where[] = 'p.user_id=?';
    $types .= 'i';
    $params[] = $siswa;
}

if (in_array($status, ['tepat_waktu', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
    $where[] = 'p.status=?';
    $types .= 's';
    $params[] = $status;
}

$sql = "SELECT p.tanggal, u.nama_lengkap, k.nama_kelas,
        p.jam_masuk, p.jam_pulang, p.status
        FROM presensi p
        JOIN users u ON u.id=p.user_id
        LEFT JOIN kelas k ON k.id=u.id_kelas
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.tanggal DESC, u.nama_lengkap";

$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan-presensi-' . date('Y-m-d') . '.csv"');

echo "Tanggal,Nama,Kelas,Jam Masuk,Jam Pulang,Status\n";

while ($r = mysqli_fetch_assoc($res)) {
    $row = [
        $r['tanggal'],
        $r['nama_lengkap'],
        $r['nama_kelas'] ?? '-',
        $r['jam_masuk'] ?? '-',
        $r['jam_pulang'] ?? '-',
        str_replace('_', ' ', $r['status'])
    ];

    echo '"' . implode('","', array_map(
        fn($x) => str_replace('"', '""', $x),
        $row
    )) . '"' . "\n";
}
