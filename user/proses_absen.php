<?php

require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

date_default_timezone_set('Asia/Jakarta');


/* =====================================================
   CEK METHOD
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect('user/absen.php');

}


/* =====================================================
   DATA POST
===================================================== */

$uid = (int)($_SESSION['user_id'] ?? 0);

$aksi = $_POST['aksi'] ?? '';

$foto = $_POST['foto'] ?? '';

$lat = filter_var(
    $_POST['latitude'] ?? null,
    FILTER_VALIDATE_FLOAT
);

$lng = filter_var(
    $_POST['longitude'] ?? null,
    FILTER_VALIDATE_FLOAT
);


$today = date('Y-m-d');

$now = date('H:i:s');


/* =====================================================
   FUNGSI GAGAL
===================================================== */

function gagal_absen(string $message): never
{

    flash(
        'error',
        '❌ ' . $message
    );

    redirect('user/absen.php');

}


/* =====================================================
   HAVERSINE
===================================================== */

function haversine_server(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float {

    $earth = 6371000;

    $rad = M_PI / 180;

    $dLat =
        ($lat2 - $lat1) * $rad;

    $dLon =
        ($lon2 - $lon1) * $rad;

    $a =
        sin($dLat / 2) ** 2
        +
        cos($lat1 * $rad)
        *
        cos($lat2 * $rad)
        *
        sin($dLon / 2) ** 2;

    return
        $earth *
        2 *
        atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

}


/* =====================================================
   CEK AKSI
===================================================== */

if (
    !in_array(
        $aksi,
        ['hadir', 'pulang'],
        true
    )
) {

    gagal_absen(
        'Aksi presensi tidak valid.'
    );

}


/* =====================================================
   CEK FACE VECTOR
===================================================== */

$stFV = mysqli_prepare(
    $conn,
    "SELECT face_vector
     FROM users
     WHERE id = ?
     LIMIT 1"
);


if (!$stFV) {

    gagal_absen(
        'Data face vector akun tidak dapat dibaca.'
    );

}


mysqli_stmt_bind_param(
    $stFV,
    'i',
    $uid
);

mysqli_stmt_execute($stFV);


$fvResult =
    mysqli_stmt_get_result($stFV);


$fvRow =
    mysqli_fetch_assoc($fvResult);


mysqli_stmt_close($stFV);


$storedVector =
    $fvRow['face_vector'] ?? '';


if (
    !is_string($storedVector)
    ||
    trim($storedVector) === ''
) {

    gagal_absen(
        'Face vector belum terdaftar. Silakan daftar wajah terlebih dahulu.'
    );

}


/* =====================================================
   CEK FOTO
===================================================== */

if (
    !is_string($foto)
    ||
    !preg_match(
        '/^data:image\/(jpeg|jpg|png);base64,/i',
        $foto
    )
) {

    gagal_absen(
        'Foto wajah belum dikirim atau format foto tidak valid.'
    );

}


/* =====================================================
   AMBIL BASE64
===================================================== */

$base64 = preg_replace(
    '/^data:image\/(jpeg|jpg|png);base64,/i',
    '',
    $foto
);


$binary =
    base64_decode(
        $base64,
        true
    );


if (
    $binary === false
    ||
    strlen($binary) < 1000
    ||
    strlen($binary) > 5 * 1024 * 1024
) {

    gagal_absen(
        'Foto wajah tidak valid atau terlalu besar.'
    );

}


/* =====================================================
   CEK GAMBAR
===================================================== */

$imgInfo =
    @getimagesizefromstring(
        $binary
    );


if (!$imgInfo) {

    gagal_absen(
        'File foto bukan gambar yang valid.'
    );

}


$mime =
    $imgInfo['mime'] ?? '';


if (
    !in_array(
        $mime,
        [
            'image/jpeg',
            'image/png'
        ],
        true
    )
) {

    gagal_absen(
        'Format foto harus JPG, JPEG, atau PNG.'
    );

}


/* =====================================================
   BUAT FOTO TEMPORARY
===================================================== */

$tmp =
    sys_get_temp_dir()
    .
    DIRECTORY_SEPARATOR
    .
    'verify_'
    .
    $uid
    .
    '_'
    .
    bin2hex(
        random_bytes(6)
    )
    .
    '.jpg';


if (
    file_put_contents(
        $tmp,
        $binary,
        LOCK_EX
    ) === false
) {

    gagal_absen(
        'Foto sementara gagal dibuat.'
    );

}


/* =====================================================
   VERIFIKASI WAJAH KE PYTHON
===================================================== */

$ch =
    curl_init(
        FACE_API_URL . '/verify'
    );


$postData = [

    'image' =>
        new CURLFile(
            $tmp,
            $mime,
            'face.' .
            (
                $mime === 'image/png'
                    ? 'png'
                    : 'jpg'
            )
        ),

    'stored_vector' =>
        $storedVector

];


curl_setopt_array(
    $ch,
    [

        CURLOPT_POST =>
            true,

        CURLOPT_POSTFIELDS =>
            $postData,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_CONNECTTIMEOUT =>
            5,

        CURLOPT_TIMEOUT =>
            30

    ]
);


$raw =
    curl_exec($ch);


$curlErr =
    curl_error($ch);


$http =
    (int)curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/* Hapus temporary */

@unlink($tmp);


/* =====================================================
   CEK PYTHON API
===================================================== */

if ($raw === false) {

    gagal_absen(
        'Python Face API tidak dapat dihubungi: '
        .
        $curlErr
    );

}


$faceResult =
    json_decode(
        $raw,
        true
    );


if (!is_array($faceResult)) {

    gagal_absen(
        'Respons Python Face API tidak valid. HTTP: '
        .
        $http
    );

}


/* =====================================================
   HASIL FACE API
===================================================== */

if (
    empty(
        $faceResult['success']
    )
) {

    gagal_absen(
        $faceResult['message']
        ??
        'Verifikasi wajah gagal.'
    );

}


if (
    empty(
        $faceResult['match']
    )
) {

    $distance =
        (float)(
            $faceResult['distance']
            ??
            0
        );


    gagal_absen(
        'Wajah tidak cocok dengan wajah yang terdaftar. Face distance: '
        .
        number_format(
            $distance,
            3
        )
    );

}


/* =====================================================
   CEK GPS
===================================================== */

if (
    $lat === false
    ||
    $lng === false
    ||
    $lat < -90
    ||
    $lat > 90
    ||
    $lng < -180
    ||
    $lng > 180
) {

    gagal_absen(
        'Koordinat GPS tidak valid. Silakan aktifkan lokasi dan coba lagi.'
    );

}


/* =====================================================
   AMBIL LOKASI SEKOLAH
===================================================== */

$stLokasi = mysqli_prepare(
    $conn,
    "SELECT
        id,
        nama_lokasi,
        latitude,
        longitude,
        radius
     FROM lokasi
     ORDER BY id ASC
     LIMIT 1"
);


if (!$stLokasi) {

    gagal_absen(
        'Konfigurasi lokasi sekolah belum tersedia.'
    );

}


mysqli_stmt_execute(
    $stLokasi
);


$lokasi =
    mysqli_fetch_assoc(
        mysqli_stmt_get_result(
            $stLokasi
        )
    );


mysqli_stmt_close(
    $stLokasi
);


if (!$lokasi) {

    gagal_absen(
        'Lokasi sekolah belum dibuat oleh admin.'
    );

}


/* =====================================================
   HITUNG JARAK
===================================================== */

$schoolLat =
    (float)$lokasi['latitude'];

$schoolLng =
    (float)$lokasi['longitude'];

$radius =
    max(
        1,
        (int)$lokasi['radius']
    );


$distance =
    haversine_server(
        (float)$lat,
        (float)$lng,
        $schoolLat,
        $schoolLng
    );


if (
    $distance > $radius
) {

    gagal_absen(
        'Kamu berada di luar radius sekolah. '
        .
        'Jarak kamu sekitar '
        .
        round($distance)
        .
        ' meter, sedangkan radius yang diizinkan '
        .
        $radius
        .
        ' meter.'
    );

}


/* =====================================================
   CEK HARI SEKOLAH
===================================================== */

$day =
    (int)date('N');


if (
    !in_array(
        $day,
        [1, 2, 3, 4, 5],
        true
    )
) {

    gagal_absen(
        'Presensi hanya tersedia pada hari sekolah, Senin sampai Jumat.'
    );

}


/* =====================================================
   AMBIL JADWAL
===================================================== */

$stJadwal = mysqli_prepare(
    $conn,
    "SELECT
        id,
        jam_masuk,
        jam_pulang,
        toleransi_terlambat
     FROM jam_kerja
     ORDER BY id ASC
     LIMIT 1"
);


if (!$stJadwal) {

    gagal_absen(
        'Jadwal presensi belum tersedia.'
    );

}


mysqli_stmt_execute(
    $stJadwal
);


$jadwal =
    mysqli_fetch_assoc(
        mysqli_stmt_get_result(
            $stJadwal
        )
    );


mysqli_stmt_close(
    $stJadwal
);


if (!$jadwal) {

    gagal_absen(
        'Jadwal presensi belum dibuat oleh admin.'
    );

}


/* =====================================================
   CEK IZIN
===================================================== */

$stIzin = mysqli_prepare(
    $conn,
    "SELECT
        jenis,
        status
     FROM perizinan
     WHERE user_id = ?
     AND status IN ('pending','disetujui')
     AND tanggal_mulai <= ?
     AND tanggal_selesai >= ?
     LIMIT 1"
);


if ($stIzin) {

    mysqli_stmt_bind_param(
        $stIzin,
        'iss',
        $uid,
        $today,
        $today
    );


    mysqli_stmt_execute(
        $stIzin
    );


    $izin =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $stIzin
            )
        );


    mysqli_stmt_close(
        $stIzin
    );


    if ($izin) {

        gagal_absen(
            'Kamu memiliki pengajuan '
            .
            ucfirst(
                $izin['jenis']
            )
            .
            ' untuk hari ini dengan status '
            .
            $izin['status']
            .
            '. Presensi tidak dapat dilakukan.'
        );

    }

}


/* =====================================================
   CEK PRESENSI HARI INI
===================================================== */

$stP = mysqli_prepare(
    $conn,
    "SELECT
        id,
        jam_masuk,
        jam_pulang
     FROM presensi
     WHERE user_id = ?
     AND tanggal = ?
     LIMIT 1"
);


if (!$stP) {

    gagal_absen(
        'Data presensi tidak dapat dibaca.'
    );

}


mysqli_stmt_bind_param(
    $stP,
    'is',
    $uid,
    $today
);


mysqli_stmt_execute(
    $stP
);


$presensi =
    mysqli_fetch_assoc(
        mysqli_stmt_get_result(
            $stP
        )
    )
    ?:
    null;


mysqli_stmt_close(
    $stP
);


/* =====================================================
   FOLDER FOTO
===================================================== */

$uploadDir =
    dirname(__DIR__)
    .
    '/uploads/absensi/';


if (
    !is_dir($uploadDir)
) {

    if (
        !mkdir(
            $uploadDir,
            0755,
            true
        )
    ) {

        gagal_absen(
            'Folder upload absensi tidak dapat dibuat.'
        );

    }

}


/* =====================================================
   SIMPAN FOTO
===================================================== */

$jenisFoto =
    $aksi === 'hadir'
        ? 'masuk'
        : 'pulang';


$namaFile =
    'presensi_'
    .
    $uid
    .
    '_'
    .
    $today
    .
    '_'
    .
    $jenisFoto
    .
    '_'
    .
    bin2hex(
        random_bytes(5)
    )
    .
    '.jpg';


$relativeFile =
    'uploads/absensi/'
    .
    $namaFile;


$fullPath =
    $uploadDir
    .
    $namaFile;


if (
    file_put_contents(
        $fullPath,
        $binary,
        LOCK_EX
    ) === false
) {

    gagal_absen(
        'Foto gagal disimpan ke server.'
    );

}


/* =====================================================
   ABSEN MASUK
===================================================== */

if ($aksi === 'hadir') {


    /* Sudah absen */

    if (
        $presensi
        &&
        !empty(
            $presensi['jam_masuk']
        )
    ) {

        @unlink($fullPath);


        gagal_absen(
            'Kamu sudah melakukan absen hadir hari ini pada pukul '
            .
            $presensi['jam_masuk']
            .
            '.'
        );

    }


    /* =================================================
       STATUS TERLAMBAT
    ================================================= */

    $toleransi =
        (int)(
            $jadwal['toleransi_terlambat']
            ??
            15
        );


    $batas =
        strtotime(
            $jadwal['jam_masuk']
            .
            ' +'
            .
            $toleransi
            .
            ' minutes'
        );


    $status =
        strtotime($now) <= $batas
            ? 'tepat_waktu'
            : 'terlambat';


    /* =================================================
       UPDATE PRESENSI YANG SUDAH ADA
    ================================================= */

    if ($presensi) {


        $st = mysqli_prepare(
            $conn,
            "UPDATE presensi
             SET
                id_jam_kerja = ?,
                jam_masuk = ?,
                foto_masuk = ?,
                lat_masuk = ?,
                long_masuk = ?,
                latitude_masuk = ?,
                longitude_masuk = ?,
                status = ?
             WHERE id = ?
             AND user_id = ?
             AND tanggal = ?"
        );


        if (!$st) {

            @unlink($fullPath);

            gagal_absen(
                'Query update presensi gagal: '
                .
                mysqli_error($conn)
            );

        }


        $idJam =
            (int)$jadwal['id'];

        $idPresensi =
            (int)$presensi['id'];

        $latS =
            (string)$lat;

        $lngS =
            (string)$lng;


        mysqli_stmt_bind_param(
            $st,
            'issssddsiis',
            $idJam,
            $now,
            $relativeFile,
            $latS,
            $lngS,
            $lat,
            $lng,
            $status,
            $idPresensi,
            $uid,
            $today
        );


    } else {


        /* =================================================
           INSERT PRESENSI BARU
        ================================================= */

        $st = mysqli_prepare(
            $conn,
            "INSERT INTO presensi
            (
                user_id,
                id_jam_kerja,
                tanggal,
                jam_masuk,
                foto_masuk,
                lat_masuk,
                long_masuk,
                latitude_masuk,
                longitude_masuk,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )"
        );


        if (!$st) {

            @unlink($fullPath);

            gagal_absen(
                'Query insert presensi gagal: '
                .
                mysqli_error($conn)
            );

        }


        $idJam =
            (int)$jadwal['id'];

        $latS =
            (string)$lat;

        $lngS =
            (string)$lng;


        mysqli_stmt_bind_param(
            $st,
            'iissssddss',
            $uid,
            $idJam,
            $today,
            $now,
            $relativeFile,
            $latS,
            $lngS,
            $lat,
            $lng,
            $status
        );

    }


    /* =================================================
       EXECUTE
    ================================================= */

    if (
        !mysqli_stmt_execute($st)
    ) {

        $error =
            mysqli_stmt_error($st);


        mysqli_stmt_close($st);


        @unlink($fullPath);


        gagal_absen(
            'Absen hadir gagal: '
            .
            $error
        );

    }


    mysqli_stmt_close($st);


    /* =================================================
       SUKSES
    ================================================= */

    flash(
        'success',
        '✅ Absen masuk berhasil pada '
        .
        $now
        .
        '. Status: '
        .
        (
            $status === 'tepat_waktu'
                ? 'Tepat Waktu'
                : 'Terlambat'
        )
        .
        '. Jarak GPS: '
        .
        round($distance)
        .
        ' meter.'
    );


    redirect(
        'user/absen.php'
    );

}


/* =====================================================
   ABSEN PULANG
===================================================== */

if (
    !$presensi
    ||
    empty(
        $presensi['jam_masuk']
    )
) {

    @unlink($fullPath);


    gagal_absen(
        'Kamu harus melakukan absen hadir terlebih dahulu.'
    );

}


/* =====================================================
   SUDAH PULANG
===================================================== */

if (
    !empty(
        $presensi['jam_pulang']
    )
) {

    @unlink($fullPath);


    gagal_absen(
        'Kamu sudah melakukan absen pulang pada pukul '
        .
        $presensi['jam_pulang']
        .
        '.'
    );

}


/* =====================================================
   CEK JAM PULANG
===================================================== */

if (
    $now < $jadwal['jam_pulang']
) {

    @unlink($fullPath);


    gagal_absen(
        'Belum waktunya absen pulang. Jadwal pulang hari ini pukul '
        .
        date(
            'H:i',
            strtotime(
                $jadwal['jam_pulang']
            )
        )
        .
        '.'
    );

}


/* =====================================================
   UPDATE ABSEN PULANG
===================================================== */

$st = mysqli_prepare(
    $conn,
    "UPDATE presensi
     SET
        jam_pulang = ?,
        foto_pulang = ?,
        lat_pulang = ?,
        long_pulang = ?
     WHERE id = ?
     AND user_id = ?
     AND tanggal = ?"
);


if (!$st) {

    @unlink($fullPath);


    gagal_absen(
        'Query absen pulang gagal: '
        .
        mysqli_error($conn)
    );

}


$latS =
    (string)$lat;

$lngS =
    (string)$lng;

$idPresensi =
    (int)$presensi['id'];


mysqli_stmt_bind_param(
    $st,
    'ssssiss',
    $now,
    $relativeFile,
    $latS,
    $lngS,
    $idPresensi,
    $uid,
    $today
);


/* =====================================================
   EXECUTE PULANG
===================================================== */

if (
    !mysqli_stmt_execute($st)
) {

    $error =
        mysqli_stmt_error($st);


    mysqli_stmt_close($st);


    @unlink($fullPath);


    gagal_absen(
        'Absen pulang gagal: '
        .
        $error
    );

}


mysqli_stmt_close($st);


/* =====================================================
   SUKSES PULANG
===================================================== */

flash(
    'success',
    '🚪 Absen pulang berhasil pada '
    .
    $now
    .
    '. Jarak GPS: '
    .
    round($distance)
    .
    ' meter.'
);


redirect(
    'user/absen.php'
);