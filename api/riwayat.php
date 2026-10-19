<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
$user = require_api_user($conn);
$st = mysqli_prepare($conn, "SELECT id,tanggal,jam_masuk,jam_pulang,status,latitude,longitude,accuracy_meter,distance_meter,face_verified FROM presensi WHERE user_id = ? ORDER BY tanggal DESC, id DESC LIMIT 100");
mysqli_stmt_bind_param($st, 'i', $user['user_id']);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$rows=[];
while($r=mysqli_fetch_assoc($res)) $rows[]=$r;
api_json(['success'=>true,'data'=>$rows]);
