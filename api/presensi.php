<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
$user = require_api_user($conn);
if ($user['role'] !== 'user') api_json(['success'=>false,'message'=>'Hanya siswa yang dapat melakukan presensi.'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_json(['success'=>false,'message'=>'Method harus POST.'], 405);

$data = api_input();
$lat = isset($data['latitude']) ? (float)$data['latitude'] : null;
$lon = isset($data['longitude']) ? (float)$data['longitude'] : null;
$accuracy = isset($data['accuracy']) ? (float)$data['accuracy'] : null;
$faceVerified = filter_var($data['face_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
$similarity = isset($data['face_similarity']) ? (float)$data['face_similarity'] : null;

if ($lat === null || $lon === null || $accuracy === null) api_json(['success'=>false,'message'=>'Lokasi GPS belum tersedia.'], 422);
if ($accuracy <= 0 || $accuracy > 200) api_json(['success'=>false,'message'=>'Akurasi GPS terlalu rendah. Silakan coba di area terbuka.'], 422);
if (!$faceVerified) api_json(['success'=>false,'message'=>'Verifikasi wajah belum berhasil.'], 422);

$jk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM jam_kerja ORDER BY id LIMIT 1"));
if (!$jk) api_json(['success'=>false,'message'=>'Jadwal presensi belum tersedia.'], 409);
if ($jk['latitude_sekolah'] === null || $jk['longitude_sekolah'] === null) api_json(['success'=>false,'message'=>'Lokasi sekolah belum diatur oleh admin.'], 409);

$distance = distance_meters($lat, $lon, (float)$jk['latitude_sekolah'], (float)$jk['longitude_sekolah']);
$radius = max(1, (int)$jk['radius_meter']);
if ($distance > $radius) api_json(['success'=>false,'message'=>'Anda berada di luar area sekolah.','distance_meter'=>round($distance,1),'radius_meter'=>$radius], 403);

$today = date('Y-m-d');
$st = mysqli_prepare($conn, "SELECT id FROM presensi WHERE user_id = ? AND tanggal = ? LIMIT 1");
mysqli_stmt_bind_param($st, 'is', $user['user_id'], $today);
mysqli_stmt_execute($st);
if (mysqli_num_rows(mysqli_stmt_get_result($st)) > 0) api_json(['success'=>false,'message'=>'Anda sudah melakukan presensi hari ini.'], 409);

$now = date('H:i:s');
$target = strtotime($jk['jam_masuk']);
$limit = $target + ((int)$jk['toleransi_terlambat'] * 60);
$status = strtotime($now) <= $limit ? 'tepat_waktu' : 'terlambat';

$st = mysqli_prepare($conn, "INSERT INTO presensi (user_id,id_jam_kerja,tanggal,jam_masuk,status,latitude,longitude,accuracy_meter,face_verified,face_similarity,distance_meter) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$faceFlag = 1;
mysqli_stmt_bind_param($st, 'iisssdddidd', $user['user_id'], $jk['id'], $today, $now, $status, $lat, $lon, $accuracy, $faceFlag, $similarity, $distance);
if (!mysqli_stmt_execute($st)) api_json(['success'=>false,'message'=>'Presensi gagal: '.mysqli_stmt_error($st)], 500);

api_json(['success'=>true,'message'=>'Presensi berhasil.', 'status'=>$status, 'jam_masuk'=>$now, 'distance_meter'=>round($distance,1)]);
