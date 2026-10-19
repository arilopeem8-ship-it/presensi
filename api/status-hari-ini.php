<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
$user = require_api_user($conn);
if ($user['role'] !== 'user') {
    api_json(['success' => false, 'message' => 'Hanya siswa yang dapat melihat presensi.'], 403);
}

date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$st = mysqli_prepare($conn, 'SELECT jam_masuk, jam_pulang, status FROM presensi WHERE user_id = ? AND tanggal = ? LIMIT 1');
if (!$st) {
    error_log('API today attendance prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Status presensi belum dapat dimuat.'], 500);
}
mysqli_stmt_bind_param($st, 'is', $user['user_id'], $today);
mysqli_stmt_execute($st);
$record = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
api_json([
    'success' => true,
    'tanggal' => $today,
    'jam_masuk' => $record['jam_masuk'] ?? null,
    'jam_pulang' => $record['jam_pulang'] ?? null,
    'status' => $record['status'] ?? null,
]);
