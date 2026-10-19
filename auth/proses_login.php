<?php
/* =====================================================
   LOGIN JSON (ANDROID RETROFIT + WEB LEGACY)
   - Output dijamin MURNI JSON (warning/notice dibuang)
   - Siswa: NISN + tanggal lahir (DDMMYYYY / YYYY-MM-DD / DD-MM-YYYY)
   - Admin: username + password hash
   - TIDAK ADA closing tag ?> supaya tidak ada output liar
===================================================== */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();
require_once '../config/database.php';

function json_out(array $data): void
{
    if (ob_get_level() > 0) ob_end_clean(); // buang output liar
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Fatal error server: ' . $e['message']]);
    }
});

function normalize_tgl(string $s): string
{
    $s = trim($s);
    if ($s === '') return '';
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s, $m)) {          // YYYY-MM-DD
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]) : '';
    }
    if (preg_match('/^\d{8}$/', $s)) {                                     // DDMMYYYY / YYYYMMDD
        if ((int)substr($s, 0, 4) >= 1900 && (int)substr($s, 0, 4) <= 2100) {
            $y = (int)substr($s, 0, 4); $mo = (int)substr($s, 4, 2); $d = (int)substr($s, 6, 2);
        } else {
            $d = (int)substr($s, 0, 2); $mo = (int)substr($s, 2, 2); $y = (int)substr($s, 4, 4);
        }
        return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
    }
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $s, $m)) {   // DD-MM-YYYY
        return checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : '';
    }
    $t = strtotime($s);
    return $t ? date('Y-m-d', $t) : '';
}

/* ---------- Baca input: JSON (Android) atau POST (web) ---------- */
$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);

if (is_array($json)) {
    $nisn     = trim((string)($json['nisn'] ?? ''));
    $tglIn    = trim((string)($json['tanggal_lahir'] ?? $json['password'] ?? $json['tgl'] ?? ''));
    $username = trim((string)($json['username'] ?? ''));
    $password = (string)($json['password'] ?? '');
    $isJson   = true;
} else {
    $nisn     = trim((string)($_POST['nisn'] ?? ''));
    $tglIn    = trim((string)($_POST['tanggal_lahir'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $isJson   = false;
}

/* ================= LOGIN SISWA (NISN + TANGGAL LAHIR) ================= */
if ($username === '') {
    if ($nisn === '' || $tglIn === '') {
        json_out(['status' => 'error', 'message' => 'NISN dan Tanggal Lahir wajib diisi!']);
    }
    $tgl_db = normalize_tgl($tglIn);
    if ($tgl_db === '') {
        json_out(['status' => 'error', 'message' => 'Format tanggal lahir tidak dikenali. Contoh: 17082010.']);
    }
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, nama_lengkap, nisn FROM users
         WHERE nisn = ? AND tanggal_lahir = ? AND role = 'user' LIMIT 1"
    );
    if (!$stmt) json_out(['status' => 'error', 'message' => 'Query gagal: ' . mysqli_error($conn)]);
    mysqli_stmt_bind_param($stmt, 'ss', $nisn, $tgl_db);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['login']        = true;
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['nama']         = $user['nama_lengkap'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role']         = 'user';
        $_SESSION['nisn']         = $user['nisn'];
        json_out([
            'status'       => 'success',
            'message'      => 'Login berhasil',
            'student_name' => $user['nama_lengkap'],
            'nisn'         => $user['nisn'],
        ]);
    }
    json_out(['status' => 'error', 'message' => 'NISN atau Tanggal Lahir salah!']);
}

/* ================= LOGIN ADMIN (USERNAME + PASSWORD) ================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, nama_lengkap, password FROM users
     WHERE username = ? AND role = 'admin' LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($admin && password_verify($password, $admin['password'])) {
    session_regenerate_id(true);
    $_SESSION['login']        = true;
    $_SESSION['user_id']      = $admin['id'];
    $_SESSION['admin_id']     = $admin['id'];
    $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
    $_SESSION['role']         = 'admin';
    if (!$isJson) { // request web lama -> redirect
        if (ob_get_level() > 0) ob_end_clean();
        header('Location: ../admin/index.php');
        exit;
    }
    json_out(['status' => 'success', 'message' => 'Login admin berhasil']);
}
json_out(['status' => 'error', 'message' => 'Username atau Password Admin salah!']);