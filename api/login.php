<?php
declare(strict_types=1);
require_once __DIR__ . '/_common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_json(['success'=>false,'message'=>'Method harus POST.'], 405);

$data = api_input();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') api_json(['success'=>false,'message'=>'Username dan password wajib diisi.'], 422);

$st = mysqli_prepare($conn, "SELECT id, username, password, nama_lengkap, role, id_kelas FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($st, 's', $username);
mysqli_stmt_execute($st);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

if (!$user || !password_verify($password, $user['password'])) api_json(['success'=>false,'message'=>'Username atau password salah.'], 401);

$token = bin2hex(random_bytes(32));
$hash = hash('sha256', $token);
$expires = date('Y-m-d H:i:s', time() + 86400 * 7);

$st = mysqli_prepare($conn, "INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($st, 'iss', $user['id'], $hash, $expires);
mysqli_stmt_execute($st);

api_json([
    'success'=>true,
    'message'=>'Login berhasil.',
    'token'=>$token,
    'expires_at'=>$expires,
    'user'=>[
        'id'=>(int)$user['id'],
        'username'=>$user['username'],
        'nama_lengkap'=>$user['nama_lengkap'],
        'role'=>$user['role'],
        'id_kelas'=>$user['id_kelas'] !== null ? (int)$user['id_kelas'] : null
    ]
]);
