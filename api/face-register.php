<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
$user = require_api_user($conn);
if ($user['role'] !== 'user') api_json(['success'=>false,'message'=>'Hanya akun siswa yang dapat mendaftarkan wajah.'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_json(['success'=>false,'message'=>'Method harus POST.'], 405);
$data = api_input();
$descriptor = $data['descriptor'] ?? null;
if (!is_array($descriptor) || count($descriptor) < 32 || count($descriptor) > 2048) api_json(['success'=>false,'message'=>'Face descriptor tidak valid.'], 422);
foreach ($descriptor as $v) if (!is_numeric($v)) api_json(['success'=>false,'message'=>'Face descriptor berisi data tidak valid.'], 422);
$json = json_encode(array_values(array_map('floatval', $descriptor)));
$st = mysqli_prepare($conn, "INSERT INTO face_profiles (user_id, descriptor_json, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE descriptor_json = VALUES(descriptor_json), updated_at = NOW()");
mysqli_stmt_bind_param($st, 'is', $user['user_id'], $json);
mysqli_stmt_execute($st);
api_json(['success'=>true,'message'=>'Data wajah berhasil disimpan.']);
