<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function api_json(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) return trim($m[1]);
    return '';
}

function require_api_user(mysqli $conn): array
{
    $token = bearer_token();
    if ($token === '') api_json(['success'=>false,'message'=>'Token tidak ditemukan.'], 401);

    $hash = hash('sha256', $token);
    $st = mysqli_prepare($conn, "SELECT t.user_id, u.username, u.nama_lengkap, u.role, u.id_kelas, k.nama_kelas
        FROM api_tokens t
        JOIN users u ON u.id = t.user_id
        LEFT JOIN kelas k ON k.id = u.id_kelas
        WHERE t.token_hash = ? AND t.expires_at > NOW() LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $hash);
    mysqli_stmt_execute($st);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    if (!$user) api_json(['success'=>false,'message'=>'Token tidak valid atau sudah kedaluwarsa.'], 401);
    return $user;
}

function distance_meters(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earth = 6371000.0;
    $p1 = deg2rad($lat1);
    $p2 = deg2rad($lat2);
    $dp = deg2rad($lat2 - $lat1);
    $dl = deg2rad($lon2 - $lon1);
    $a = sin($dp/2) ** 2 + cos($p1) * cos($p2) * sin($dl/2) ** 2;
    return 2 * $earth * asin(min(1, sqrt($a)));
}
