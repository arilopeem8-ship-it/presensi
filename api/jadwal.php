<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
require_api_user($conn);
$jk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id,jam_masuk,jam_pulang,toleransi_terlambat,latitude_sekolah,longitude_sekolah,radius_meter FROM jam_kerja ORDER BY id LIMIT 1"));
if (!$jk) api_json(['success'=>false,'message'=>'Jadwal presensi belum tersedia.'], 404);
api_json(['success'=>true,'jadwal'=>[
    'id'=>(int)$jk['id'],
    'jam_masuk'=>$jk['jam_masuk'],
    'jam_pulang'=>$jk['jam_pulang'],
    'toleransi_terlambat'=>(int)$jk['toleransi_terlambat'],
    'latitude_sekolah'=>$jk['latitude_sekolah'] !== null ? (float)$jk['latitude_sekolah'] : null,
    'longitude_sekolah'=>$jk['longitude_sekolah'] !== null ? (float)$jk['longitude_sekolah'] : null,
    'radius_meter'=>(int)$jk['radius_meter']
]]);
