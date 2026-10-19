<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../config/helper.php';

$user = require_api_user($conn);
if ($user['role'] !== 'user') {
    api_json(['success' => false, 'message' => 'Hanya siswa yang dapat melakukan presensi.'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json(['success' => false, 'message' => 'Method harus POST.'], 405);
}

date_default_timezone_set('Asia/Jakarta');

$action = $_POST['aksi'] ?? '';
$lat = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$lon = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
$accuracy = filter_var($_POST['accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
$today = date('Y-m-d');
$now = date('H:i:s');

if (!in_array($action, ['hadir', 'pulang'], true)) {
    api_json(['success' => false, 'message' => 'Aksi presensi tidak valid.'], 422);
}
if ($lat === false || $lon === false || $accuracy === false
    || $lat === null || $lon === null || $accuracy === null
    || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    api_json(['success' => false, 'message' => 'Koordinat GPS tidak valid.'], 422);
}
if ($accuracy <= 0 || $accuracy > 200) {
    api_json(['success' => false, 'message' => 'Akurasi GPS terlalu rendah. Coba lagi di area terbuka.'], 422);
}
if (!in_array((int)date('N'), [1, 2, 3, 4, 5], true)) {
    api_json(['success' => false, 'message' => 'Presensi hanya tersedia pada hari sekolah, Senin sampai Jumat.'], 403);
}

$image = $_FILES['foto'] ?? null;
if (!is_array($image) || ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || !is_uploaded_file($image['tmp_name'] ?? '')) {
    api_json(['success' => false, 'message' => 'Foto wajah wajib dikirim.'], 422);
}
if (($image['size'] ?? 0) < 1000 || $image['size'] > 5 * 1024 * 1024) {
    api_json(['success' => false, 'message' => 'Ukuran foto tidak valid atau lebih dari 5 MB.'], 422);
}
$imageInfo = getimagesize($image['tmp_name']);
$mime = $imageInfo['mime'] ?? '';
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    api_json(['success' => false, 'message' => 'Format foto harus JPG, JPEG, atau PNG.'], 422);
}

$st = mysqli_prepare($conn, 'SELECT face_vector FROM users WHERE id = ? LIMIT 1');
if (!$st) {
    error_log('API attendance face lookup prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Data wajah belum dapat diperiksa.'], 500);
}
mysqli_stmt_bind_param($st, 'i', $user['user_id']);
mysqli_stmt_execute($st);
$faceRow = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
$storedVector = $faceRow['face_vector'] ?? '';
if (!is_string($storedVector) || trim($storedVector) === '') {
    api_json(['success' => false, 'message' => 'Wajah belum terdaftar. Silakan daftar wajah terlebih dahulu.'], 409);
}

$faceApi = curl_init(FACE_API_URL . '/verify');
curl_setopt_array($faceApi, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'image' => new CURLFile($image['tmp_name'], $mime, 'face.' . ($mime === 'image/png' ? 'png' : 'jpg')),
        'stored_vector' => $storedVector,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 30,
]);
$faceRaw = curl_exec($faceApi);
$faceError = curl_error($faceApi);
$faceHttpCode = (int)curl_getinfo($faceApi, CURLINFO_HTTP_CODE);
curl_close($faceApi);
if ($faceRaw === false) {
    error_log('API attendance face service unavailable: ' . $faceError);
    api_json(['success' => false, 'message' => 'Layanan verifikasi wajah tidak dapat dihubungi.'], 502);
}
$faceResult = json_decode($faceRaw, true);
if (!is_array($faceResult) || $faceHttpCode >= 500) {
    error_log('API attendance received invalid face response, HTTP ' . $faceHttpCode);
    api_json(['success' => false, 'message' => 'Respons layanan verifikasi wajah tidak valid.'], 502);
}
if (empty($faceResult['success']) || empty($faceResult['match'])) {
    api_json([
        'success' => false,
        'message' => $faceResult['message'] ?? 'Wajah tidak cocok dengan data yang terdaftar.',
    ], 422);
}

$scheduleResult = mysqli_query($conn, 'SELECT id, latitude, longitude, radius_meter, jam_masuk, jam_pulang, toleransi_terlambat FROM jam_kerja ORDER BY id ASC LIMIT 1');
if (!$scheduleResult) {
    error_log('API attendance schedule query failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Jadwal dan lokasi presensi belum dapat dibaca.'], 500);
}
$schedule = mysqli_fetch_assoc($scheduleResult);
if (!$schedule) {
    api_json(['success' => false, 'message' => 'Jadwal presensi belum dibuat oleh admin.'], 409);
}

$schoolLatitude = filter_var($schedule['latitude'], FILTER_VALIDATE_FLOAT);
$schoolLongitude = filter_var($schedule['longitude'], FILTER_VALIDATE_FLOAT);
if ($schoolLatitude === false || $schoolLongitude === false) {
    api_json(['success' => false, 'message' => 'Koordinat sekolah belum diatur dengan benar oleh admin.'], 409);
}

$distance = distance_meters($lat, $lon, (float)$schoolLatitude, (float)$schoolLongitude);
$radius = max(1, (int)$schedule['radius_meter']);
if ($distance > $radius) {
    api_json([
        'success' => false,
        'message' => 'Anda sedang di luar radius sekolah. Jarak Anda sekitar ' . round($distance) . ' meter, radius yang diizinkan ' . $radius . ' meter.',
        'distance_meter' => round($distance, 1),
        'radius_meter' => $radius,
    ], 403);
}

$st = mysqli_prepare($conn, "SELECT jenis, status FROM perizinan
    WHERE user_id = ? AND status IN ('pending', 'disetujui')
      AND tanggal_mulai <= ? AND tanggal_selesai >= ? LIMIT 1");
if (!$st) {
    error_log('API attendance permission check prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Status pengajuan izin belum dapat diperiksa.'], 500);
}
mysqli_stmt_bind_param($st, 'iss', $user['user_id'], $today, $today);
mysqli_stmt_execute($st);
$permission = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
if ($permission) {
    api_json([
        'success' => false,
        'message' => 'Pengajuan ' . $permission['jenis'] . ' Anda berstatus ' . $permission['status'] . '; presensi tidak dapat dilakukan.',
    ], 409);
}

$st = mysqli_prepare($conn, 'SELECT id, jam_masuk, jam_pulang FROM presensi WHERE user_id = ? AND tanggal = ? LIMIT 1');
if (!$st) {
    error_log('API attendance record lookup prepare failed: ' . mysqli_error($conn));
    api_json(['success' => false, 'message' => 'Data presensi belum dapat diperiksa.'], 500);
}
mysqli_stmt_bind_param($st, 'is', $user['user_id'], $today);
mysqli_stmt_execute($st);
$record = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

$photoDir = dirname(__DIR__) . '/uploads/absensi/';
if (!is_dir($photoDir) && !mkdir($photoDir, 0755, true) && !is_dir($photoDir)) {
    error_log('API attendance upload directory could not be created: ' . $photoDir);
    api_json(['success' => false, 'message' => 'Folder foto presensi tidak dapat dibuat.'], 500);
}
$photoType = $action === 'hadir' ? 'masuk' : 'pulang';
$fileName = 'presensi_' . (int)$user['user_id'] . '_' . $today . '_' . $photoType . '_' . bin2hex(random_bytes(5)) . '.jpg';
$relativeFile = 'uploads/absensi/' . $fileName;
$fullPath = $photoDir . $fileName;
if (!move_uploaded_file($image['tmp_name'], $fullPath)) {
    api_json(['success' => false, 'message' => 'Foto presensi gagal disimpan.'], 500);
}

if ($action === 'hadir') {
    if ($record && !empty($record['jam_masuk'])) {
        @unlink($fullPath);
        api_json(['success' => false, 'message' => 'Anda sudah absen masuk hari ini pada pukul ' . $record['jam_masuk'] . '.'], 409);
    }
    $limit = strtotime($schedule['jam_masuk'] . ' +' . (int)$schedule['toleransi_terlambat'] . ' minutes');
    $status = strtotime($now) <= $limit ? 'tepat_waktu' : 'terlambat';
    $latitudeText = (string)$lat;
    $longitudeText = (string)$lon;
    if ($record) {
        $st = mysqli_prepare($conn, 'UPDATE presensi SET id_jam_kerja = ?, jam_masuk = ?, foto_masuk = ?, lat_masuk = ?, long_masuk = ?, status = ? WHERE id = ? AND user_id = ? AND tanggal = ?');
        if ($st) {
            mysqli_stmt_bind_param($st, 'isssssiis', $schedule['id'], $now, $relativeFile, $latitudeText, $longitudeText, $status, $record['id'], $user['user_id'], $today);
        }
    } else {
        $st = mysqli_prepare($conn, 'INSERT INTO presensi (user_id, id_jam_kerja, tanggal, jam_masuk, foto_masuk, lat_masuk, long_masuk, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if ($st) {
            mysqli_stmt_bind_param($st, 'iissssss', $user['user_id'], $schedule['id'], $today, $now, $relativeFile, $latitudeText, $longitudeText, $status);
        }
    }
} else {
    if (!$record || empty($record['jam_masuk'])) {
        @unlink($fullPath);
        api_json(['success' => false, 'message' => 'Anda harus absen masuk terlebih dahulu.'], 409);
    }
    if (!empty($record['jam_pulang'])) {
        @unlink($fullPath);
        api_json(['success' => false, 'message' => 'Anda sudah absen pulang pada pukul ' . $record['jam_pulang'] . '.'], 409);
    }
    if ($now < $schedule['jam_pulang']) {
        @unlink($fullPath);
        api_json(['success' => false, 'message' => 'Belum waktunya absen pulang. Jadwal pulang pukul ' . date('H:i', strtotime($schedule['jam_pulang'])) . '.'], 409);
    }
    $latitudeText = (string)$lat;
    $longitudeText = (string)$lon;
    $st = mysqli_prepare($conn, 'UPDATE presensi SET jam_pulang = ?, foto_pulang = ?, lat_pulang = ?, long_pulang = ? WHERE id = ? AND user_id = ? AND tanggal = ? AND jam_pulang IS NULL');
    if ($st) {
        mysqli_stmt_bind_param($st, 'ssssiis', $now, $relativeFile, $latitudeText, $longitudeText, $record['id'], $user['user_id'], $today);
    }
}

if (!$st || !mysqli_stmt_execute($st)) {
    $error = $st ? mysqli_stmt_error($st) : mysqli_error($conn);
    if ($st) {
        mysqli_stmt_close($st);
    }
    @unlink($fullPath);
    error_log('API attendance save failed: ' . $error);
    api_json(['success' => false, 'message' => 'Presensi gagal disimpan. Silakan coba lagi.'], 500);
}
mysqli_stmt_close($st);
api_json([
    'success' => true,
    'message' => $action === 'hadir' ? 'Absen masuk berhasil.' : 'Absen pulang berhasil.',
    'aksi' => $action,
    'jam' => $now,
    'distance_meter' => round($distance, 1),
]);
