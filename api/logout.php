<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json(['success'=>false,'message'=>'Method harus POST.'], 405);
}

$token = bearer_token();
if ($token === '') {
    api_json(['success'=>false,'message'=>'Token tidak ditemukan.'], 401);
}

$hash = hash('sha256', $token);
$st = mysqli_prepare($conn, "DELETE FROM api_tokens WHERE token_hash = ?");
if (!$st) {
    error_log('API logout query prepare failed: ' . mysqli_error($conn));
    api_json(['success'=>false,'message'=>'Logout server gagal diproses.'], 500);
}

mysqli_stmt_bind_param($st, 's', $hash);
if (!mysqli_stmt_execute($st)) {
    error_log('API logout query failed: ' . mysqli_stmt_error($st));
    mysqli_stmt_close($st);
    api_json(['success'=>false,'message'=>'Logout server gagal diproses.'], 500);
}

$revoked = mysqli_stmt_affected_rows($st) > 0;
mysqli_stmt_close($st);
api_json(['success'=>true,'message'=>'Logout berhasil.','token_revoked'=>$revoked]);
