<?php
/* =====================================================
   PROSES DAFTAR WAJAH (ANTI "ERROR TAPI TERSIMPAN")
   - Semua output liar (warning/notice) dibuang
   - Fatal error sekalipun tetap jadi JSON
   - Simpan ke DB adalah langkah PALING AKHIR
===================================================== */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0'); // JANGAN cetak warning/notice ke body JSON

require_once '../config/database.php';
require_once '../config/helper.php';
check_login();

function json_exit(bool $ok, string $msg): void
{
    if (ob_get_level() > 0) ob_end_clean(); // buang output liar sebelum JSON
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $ok, 'message' => $msg]);
    exit;
}

// Fatal error pun tetap dibalas JSON supaya JS tidak bingung
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Fatal error server: ' . $e['message']]);
    }
});

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_exit(false, 'Method tidak valid.');
}

// Deteksi body kebesaran (post_max_size) -> $_POST kosong padahal foto dikirim
if (empty($_POST)) {
    $len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len > 0) {
        json_exit(false, 'Ukuran foto melebihi batas server (post_max_size). Perkecil resolusi foto.');
    }
    json_exit(false, 'Tidak ada data yang dikirim.');
}

$foto = $_POST['foto'] ?? '';
if (!is_string($foto) || !preg_match('/^data:image\/(jpeg|jpg);base64,/', $foto)) {
    json_exit(false, 'Foto wajah belum dikirim atau format tidak valid (harus JPG/JPEG).');
}
$binary = base64_decode(preg_replace('/^data:image\/(jpeg|jpg);base64,/', '', $foto), true);
if ($binary === false || strlen($binary) < 1000 || strlen($binary) > 5 * 1024 * 1024) {
    json_exit(false, 'Foto tidak valid atau terlalu besar (maks 5MB).');
}
if (!@getimagesizefromstring($binary)) {
    json_exit(false, 'File foto bukan gambar yang valid.');
}

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
    json_exit(false, 'Session login tidak valid, silakan login ulang.');
}

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'face_' . $uid . '_' . bin2hex(random_bytes(6)) . '.jpg';
if (file_put_contents($tmp, $binary, LOCK_EX) === false) {
    json_exit(false, 'Gagal membuat file sementara di server.');
}

function call_face_python(string $url, string $file): array
{
    $ch = curl_init($url);
    $post = ['image' => new CURLFile($file, 'image/jpeg', 'face.jpg')];
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['success' => false, 'message' => 'Python Face API tidak dapat dihubungi: ' . $err];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        // simpan respons mentah untuk debugging
        @file_put_contents(__DIR__ . '/face_error.log', date('c') . " HTTP {$code}: {$raw}\n", FILE_APPEND);
        return ['success' => false, 'message' => 'Respons Python Face API tidak valid (HTTP ' . $code . ').'];
    }
    return $data;
}

$result = call_face_python(FACE_API_URL . '/encode', $tmp);
@unlink($tmp);

if (empty($result['success'])) {
    json_exit(false, $result['message'] ?? 'Encode wajah gagal.');
}

$vector = $result['vector'] ?? null;
if (!is_array($vector) || count($vector) !== 128) {
    json_exit(false, 'Python tidak menghasilkan face vector 128 dimensi.');
}

$json = json_encode(array_map('floatval', $vector), JSON_UNESCAPED_SLASHES);

/* ========== SIMPAN KE DB = LANGKAH TERAKHIR ========== */
$st = mysqli_prepare($conn, 'UPDATE users SET face_vector = ? WHERE id = ?');
if (!$st) {
    json_exit(false, 'Query gagal: ' . mysqli_error($conn));
}
mysqli_stmt_bind_param($st, 'si', $json, $uid);
if (mysqli_stmt_execute($st) && mysqli_affected_rows($conn) >= 0) {
    json_exit(true, 'Face vector berhasil disimpan.');
}
json_exit(false, 'Gagal menyimpan face vector: ' . mysqli_stmt_error($st));