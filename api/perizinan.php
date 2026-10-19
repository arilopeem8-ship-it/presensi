<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

$user = require_api_user($conn);
if ($user['role'] !== 'user') {
    api_json(['success' => false, 'message' => 'Hanya siswa yang dapat mengajukan izin.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $st = mysqli_prepare($conn, 'SELECT id, jenis, tanggal_mulai, tanggal_selesai, alasan, status, catatan_admin, created_at FROM perizinan WHERE user_id = ? ORDER BY id DESC LIMIT 50');
    if (!$st) {
        error_log('API permission history prepare failed: ' . mysqli_error($conn));
        api_json(['success' => false, 'message' => 'Riwayat izin belum dapat dimuat.'], 500);
    }
    mysqli_stmt_bind_param($st, 'i', $user['user_id']);
    mysqli_stmt_execute($st);
    $result = mysqli_stmt_get_result($st);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($st);
    api_json(['success' => true, 'data' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json(['success' => false, 'message' => 'Method harus GET atau POST.'], 405);
}

$data = api_input();
$jenis = trim((string)($data['jenis'] ?? ''));
$mulai = trim((string)($data['tanggal_mulai'] ?? ''));
$selesai = trim((string)($data['tanggal_selesai'] ?? ''));
$alasan = trim((string)($data['alasan'] ?? ''));
$isValidDate = static function (string $date): bool {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts)) {
        return false;
    }
    return checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]);
};

if (!in_array($jenis, ['izin', 'sakit'], true)
    || !$isValidDate($mulai)
    || !$isValidDate($selesai)
    || $selesai < $mulai
    || $alasan === ''
    || strlen($alasan) > 5000) {
    api_json(['success' => false, 'message' => 'Jenis, tanggal, atau alasan pengajuan tidak valid.'], 422);
}

$st = mysqli_prepare($conn, 'SELECT tanggal FROM presensi WHERE user_id = ? AND tanggal BETWEEN ? AND ? LIMIT 1');
if (!$st) {
    error_log('API permission attendance conflict prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Pengajuan izin belum dapat diproses.'], 500);
}
mysqli_stmt_bind_param($st, 'iss', $user['user_id'], $mulai, $selesai);
mysqli_stmt_execute($st);
$attendanceConflict = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
if ($attendanceConflict) {
    api_json(['success' => false, 'message' => 'Pengajuan bertabrakan dengan presensi pada ' . $attendanceConflict['tanggal'] . '.'], 409);
}

$st = mysqli_prepare($conn, "SELECT jenis, status FROM perizinan
    WHERE user_id = ? AND status IN ('pending', 'disetujui')
      AND tanggal_mulai <= ? AND tanggal_selesai >= ? LIMIT 1");
if (!$st) {
    error_log('API permission overlap prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Pengajuan izin belum dapat diproses.'], 500);
}
mysqli_stmt_bind_param($st, 'iss', $user['user_id'], $selesai, $mulai);
mysqli_stmt_execute($st);
$permissionConflict = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
if ($permissionConflict) {
    api_json([
        'success' => false,
        'message' => 'Tanggal bertabrakan dengan pengajuan ' . $permissionConflict['jenis'] . ' yang masih ' . $permissionConflict['status'] . '.',
    ], 409);
}

$st = mysqli_prepare($conn, 'INSERT INTO perizinan (user_id, jenis, tanggal_mulai, tanggal_selesai, alasan) VALUES (?, ?, ?, ?, ?)');
if (!$st) {
    error_log('API permission insert prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Pengajuan izin belum dapat disimpan.'], 500);
}
mysqli_stmt_bind_param($st, 'issss', $user['user_id'], $jenis, $mulai, $selesai, $alasan);
if (!mysqli_stmt_execute($st)) {
    error_log('API permission insert failed: ' . mysqli_stmt_error($st));
    mysqli_stmt_close($st);
    api_json(['success' => false, 'message' => 'Pengajuan izin gagal disimpan.'], 500);
}
$id = mysqli_insert_id($conn);
mysqli_stmt_close($st);
api_json(['success' => true, 'message' => 'Pengajuan berhasil dikirim dan menunggu verifikasi admin.', 'id' => $id], 201);
