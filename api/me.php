<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';
$user = require_api_user($conn);
api_json(['success'=>true,'user'=>[
    'id'=>(int)$user['user_id'],
    'username'=>$user['username'],
    'nama_lengkap'=>$user['nama_lengkap'],
    'role'=>$user['role'],
    'id_kelas'=>$user['id_kelas'] !== null ? (int)$user['id_kelas'] : null,
    'nama_kelas'=>$user['nama_kelas']
]]);
