<?php

require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

date_default_timezone_set('Asia/Jakarta');

$uid = (int)($_SESSION['user_id'] ?? 0);
$nama = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Pengguna';
$today = date('Y-m-d');


/* =====================================================
   DATA USER
===================================================== */

$stUser = mysqli_prepare(
    $conn,
    "SELECT nama_lengkap, face_vector
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stUser, 'i', $uid);
mysqli_stmt_execute($stUser);

$userData = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stUser)
);

mysqli_stmt_close($stUser);

if ($userData) {
    $nama = $userData['nama_lengkap'];
}

$faceTerdaftar = !empty($userData['face_vector']);


/* =====================================================
   DATA PRESENSI HARI INI
===================================================== */

$stPresensi = mysqli_prepare(
    $conn,
    "SELECT id, jam_masuk, jam_pulang, status
     FROM presensi
     WHERE user_id = ?
     AND tanggal = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stPresensi,
    'is',
    $uid,
    $today
);

mysqli_stmt_execute($stPresensi);

$presensi = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stPresensi)
);

mysqli_stmt_close($stPresensi);


/* =====================================================
   DATA LOKASI
===================================================== */

$stLokasi = mysqli_prepare(
    $conn,
    "SELECT id, nama_lokasi, latitude, longitude, radius
     FROM lokasi
     ORDER BY id ASC
     LIMIT 1"
);

mysqli_stmt_execute($stLokasi);

$lokasi = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stLokasi)
);

mysqli_stmt_close($stLokasi);

$schoolLat = $lokasi ? (float)$lokasi['latitude'] : 0;
$schoolLng = $lokasi ? (float)$lokasi['longitude'] : 0;
$radius = $lokasi ? (int)$lokasi['radius'] : 100;


/* =====================================================
   STATUS
===================================================== */

$statusPresensi = 'Belum Absen';

if ($presensi && !empty($presensi['jam_masuk'])) {

    if (!empty($presensi['jam_pulang'])) {

        $statusPresensi = 'Sudah Absen Masuk & Pulang';

    } else {

        $statusPresensi = 'Sudah Absen Masuk';

    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Presensi Siswa</title>

<style>
/* ===== RESET ===== */
* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    padding: 30px 20px;
    min-height: 100vh;
    background: linear-gradient(135deg, #eef5ff, #f8fbff);
    font-family: Arial, sans-serif;
    font-size: 15px;
    color: #243247;
    line-height: 1.6;
}

/* ===== CONTAINER ===== */
.container {
    width: 100%;
    max-width: 760px;
    margin: 0 auto;
}

/* ===== CARD ===== */
.card {
    width: 100%;
    background: #ffffff;
    padding: 32px;
    border: 1px solid #e7eef8;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(20, 65, 130, 0.08);
}

/* ===== HEADING ===== */
h1 {
    margin: 0 0 22px;
    color: #1554a5;
    font-size: 27px;
    font-weight: 700;
    line-height: 1.3;
    padding-bottom: 15px;
    border-bottom: 3px solid #f4c542;
}

/* ===== INFORMASI ===== */
.info {
    padding: 18px 20px;
    margin-bottom: 20px;
    background: #f7faff;
    border: 1px solid #e5eefb;
    border-radius: 12px;
    line-height: 1.9;
    overflow-wrap: anywhere;
}

/* ===== STATUS ===== */
.status {
    background: #edf5ff;
    border-left: 4px solid #1769d2;
    padding: 18px 20px;
    border-radius: 10px;
    margin-bottom: 22px;
    line-height: 1.8;
    overflow-wrap: anywhere;
}

/* ===== ALERT ===== */
.success,
.error,
.warning {
    padding: 15px 18px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: 14px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.success {
    background: #e5f7ed;
    color: #146c43;
    border-left: 4px solid #198754;
}

.error {
    background: #fff0f0;
    color: #a52834;
    border-left: 4px solid #dc3545;
}

.warning {
    background: #fff8df;
    color: #856404;
    border-left: 4px solid #e8b923;
}

/* ===== LOKASI ===== */
.location {
    background: #f7faff;
    border: 1px solid #e0eafa;
    border-radius: 12px;
    padding: 20px;
    margin-top: 22px;
    line-height: 1.9;
    overflow-wrap: anywhere;
}

/* ===== INPUT FILE ===== */
input[type="file"] {
    display: block;
    width: 100%;
    max-width: 100%;
    margin: 18px 0;
    padding: 12px;
    background: #f8fbff;
    border: 1px dashed #9bbce8;
    border-radius: 10px;
    color: #34465e;
    font-size: 14px;
}

input[type="file"]::file-selector-button {
    background: #1769d2;
    color: white;
    border: none;
    border-radius: 7px;
    padding: 10px 14px;
    margin-right: 12px;
    cursor: pointer;
}

/* ===== FOTO PREVIEW ===== */
#preview {
    display: none;
    width: 100%;
    max-width: 450px;
    height: auto;
    margin: 20px auto;
    border-radius: 12px;
    border: 2px solid #e0eafa;
    object-fit: contain;
}

/* ===== KAMERA ===== */
video {
    display: none;
    width: 100%;
    max-width: 450px;
    height: auto;
    margin: 20px auto;
    border-radius: 12px;
    background: #111827;
    object-fit: cover;
}

/* ===== BUTTON ===== */
button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    max-width: 100%;
    min-height: 44px;
    border: none;
    border-radius: 9px;
    padding: 12px 20px;
    margin: 5px 4px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    text-align: center;
    cursor: pointer;
    transition: background .2s, box-shadow .2s,
                transform .2s;
}

button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 12px rgba(0,0,0,.12);
}

button:active:not(:disabled) {
    transform: translateY(0);
}

.btn-blue {
    background: #1769d2;
    color: #ffffff;
}

.btn-blue:hover:not(:disabled) {
    background: #1055af;
}

.btn-green {
    background: #198754;
    color: #ffffff;
}

.btn-green:hover:not(:disabled) {
    background: #146c43;
}

.btn-red {
    background: #dc3545;
    color: #ffffff;
}

.btn-red:hover:not(:disabled) {
    background: #b52b38;
}

.btn-gray {
    background: #64748b;
    color: #ffffff;
}

.btn-gray:hover:not(:disabled) {
    background: #475569;
}

button:disabled {
    opacity: .5;
    cursor: not-allowed;
    box-shadow: none;
}

/* ===== LINK KEMBALI ===== */
.back {
    display: inline-block;
    margin-top: 24px;
    color: #1769d2;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: color .2s;
}

.back:hover {
    color: #0c428e;
    text-decoration: underline;
}

/* ===== TABLET ===== */
@media (max-width: 1024px) {
    body {
        padding: 24px 18px;
    }

    .container {
        max-width: 700px;
    }

    .card {
        padding: 26px;
    }

    h1 {
        font-size: 25px;
    }
}

/* ===== HP / MOBILE ===== */
@media (max-width: 600px) {
    body {
        padding: 15px 12px;
        font-size: 14px;
    }

    .container {
        width: 100%;
    }

    .card {
        padding: 20px 16px;
        border-radius: 14px;
    }

    h1 {
        font-size: 21px;
        margin-bottom: 18px;
    }

    .info,
    .status,
    .location {
        padding: 14px;
        font-size: 13px;
        line-height: 1.8;
    }

    .success,
    .error,
    .warning {
        padding: 12px;
        font-size: 13px;
    }

    input[type="file"] {
        font-size: 12px;
        padding: 10px;
    }

    #preview,
    video {
        max-width: 100%;
        margin: 15px auto;
    }

    button {
        width: 100%;
        margin: 6px 0;
        padding: 13px 15px;
        font-size: 14px;
    }

    .back {
        margin-top: 20px;
    }
}

/* ===== HP KECIL ===== */
@media (max-width: 360px) {
    body {
        padding: 10px;
    }

    .card {
        padding: 15px 12px;
    }

    h1 {
        font-size: 19px;
    }

    .info,
    .status,
    .location {
        padding: 12px;
    }
}

/* ===== AKSESIBILITAS ===== */
button:focus-visible,
.back:focus-visible,
input:focus-visible {
    outline: 3px solid #f4c542;
    outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto;
        transition: none !important;
    }
}
</style>


</head>

<body>

<div class="container">

<div class="card">

<h1>📸 Presensi Siswa</h1>


<!-- =====================================================
     FLASH MESSAGE
===================================================== -->

<?php show_flash(); ?>


<!-- =====================================================
     DATA SISWA
===================================================== -->

<div class="info">

    <strong>Nama:</strong>
    <?= e($nama) ?>

    <br>

    <strong>Tanggal:</strong>
    <?= date('d-m-Y') ?>

</div>


<div class="status">

    <strong>Status:</strong>
    <?= e($statusPresensi) ?>

    <?php if ($presensi && !empty($presensi['jam_masuk'])): ?>

        <br>

        <strong>Jam Masuk:</strong>
        <?= e($presensi['jam_masuk']) ?>

    <?php endif; ?>


    <?php if ($presensi && !empty($presensi['jam_pulang'])): ?>

        <br>

        <strong>Jam Pulang:</strong>
        <?= e($presensi['jam_pulang']) ?>

    <?php endif; ?>

</div>


<!-- =====================================================
     FACE VECTOR
===================================================== -->

<?php if ($faceTerdaftar): ?>

    <div class="success">

        ✅ Face vector sudah terdaftar.

    </div>

<?php else: ?>

    <div class="error">

        ❌ Face vector belum terdaftar.

        <br><br>

        <a href="wajah.php">
            Daftar wajah terlebih dahulu
        </a>

    </div>

<?php endif; ?>


<?php if ($faceTerdaftar): ?>


<!-- =====================================================
     FORM PRESENSI
===================================================== -->

<form
    method="POST"
    action="proses_absen.php"
    id="formAbsen">


    <!-- AKSI -->

    <input
        type="hidden"
        name="aksi"
        id="aksi"
        value=""
    >


    <!-- FOTO -->

    <input
        type="hidden"
        name="foto"
        id="foto"
    >


    <!-- GPS -->

    <input
        type="hidden"
        name="latitude"
        id="latitude"
    >

    <input
        type="hidden"
        name="longitude"
        id="longitude"
    >


    <h2>📷 Foto Wajah</h2>

    <p>
        Upload foto wajah yang jelas untuk verifikasi.
    </p>


    <!-- =================================================
         CAMERA
    ================================================== -->

    <video
        id="video"
        autoplay
        playsinline>
    </video>


    <canvas
        id="canvas"
        style="display:none;">
    </canvas>


    <!-- =================================================
         PREVIEW
    ================================================== -->

    <img
        id="preview"
        alt="Preview Foto"
    >


    <!-- =================================================
         UPLOAD
    ================================================== -->

    <input
        type="file"
        id="fotoFile"
        accept="image/jpeg,image/jpg,image/png"
    >


    <br>


    <button
        type="button"
        class="btn-blue"
        id="btnCamera">

        📷 Buka Kamera

    </button>


    <button
        type="button"
        class="btn-green"
        id="btnCapture"
        style="display:none;">

        📸 Ambil Foto

    </button>


    <button
        type="button"
        class="btn-gray"
        id="btnStop"
        style="display:none;">

        ✖ Tutup Kamera

    </button>


    <br><br>


    <!-- =================================================
         TOMBOL ABSEN
    ================================================== -->

    <button
        type="button"
        class="btn-green"
        id="btnHadir"
        <?= ($presensi && !empty($presensi['jam_masuk'])) ? 'disabled' : '' ?>>

        ✅ Absen Masuk

    </button>


    <button
        type="button"
        class="btn-blue"
        id="btnPulang"
        <?= (!$presensi || empty($presensi['jam_masuk']) || !empty($presensi['jam_pulang'])) ? 'disabled' : '' ?>>

        🚪 Absen Pulang

    </button>


</form>

<?php endif; ?>


<!-- =====================================================
     GPS
===================================================== -->

<div class="location">

    <h2>📍 Lokasi GPS</h2>

    <div>
        Latitude:
        <span id="latText">
            Mencari...
        </span>
    </div>

    <div>
        Longitude:
        <span id="lngText">
            Mencari...
        </span>
    </div>

    <div>
        Jarak:
        <span id="distanceText">
            Menghitung...
        </span>
    </div>

    <div>
        Radius sekolah:
        <strong>
            <?= e($radius) ?> meter
        </strong>
    </div>

    <div id="gpsStatus">
        🔄 Mendapatkan lokasi...
    </div>

</div>


<a
    href="index.php"
    class="back">

    ← Kembali ke Dashboard

</a>

</div>

</div>


<script>

/* =====================================================
   DATA SEKOLAH
===================================================== */

const schoolLat =
    <?= json_encode($schoolLat) ?>;

const schoolLng =
    <?= json_encode($schoolLng) ?>;

const schoolRadius =
    <?= json_encode($radius) ?>;


/* =====================================================
   ELEMENT
===================================================== */

const formAbsen =
    document.getElementById('formAbsen');

const aksi =
    document.getElementById('aksi');

const foto =
    document.getElementById('foto');

const latitude =
    document.getElementById('latitude');

const longitude =
    document.getElementById('longitude');

const fotoFile =
    document.getElementById('fotoFile');

const preview =
    document.getElementById('preview');

const video =
    document.getElementById('video');

const canvas =
    document.getElementById('canvas');

const btnCamera =
    document.getElementById('btnCamera');

const btnCapture =
    document.getElementById('btnCapture');

const btnStop =
    document.getElementById('btnStop');

const btnHadir =
    document.getElementById('btnHadir');

const btnPulang =
    document.getElementById('btnPulang');

const latText =
    document.getElementById('latText');

const lngText =
    document.getElementById('lngText');

const distanceText =
    document.getElementById('distanceText');

const gpsStatus =
    document.getElementById('gpsStatus');


let stream = null;

let currentDistance = null;


/* =====================================================
   HITUNG JARAK GPS
===================================================== */

function haversine(
    lat1,
    lon1,
    lat2,
    lon2
) {

    const R = 6371000;

    const dLat =
        (lat2 - lat1)
        *
        Math.PI
        /
        180;

    const dLon =
        (lon2 - lon1)
        *
        Math.PI
        /
        180;

    const a =
        Math.sin(dLat / 2) ** 2
        +
        Math.cos(
            lat1 * Math.PI / 180
        )
        *
        Math.cos(
            lat2 * Math.PI / 180
        )
        *
        Math.sin(dLon / 2) ** 2;

    return R *
        2 *
        Math.atan2(
            Math.sqrt(a),
            Math.sqrt(1 - a)
        );
}


/* =====================================================
   GPS
===================================================== */

function getLocation() {

    if (!navigator.geolocation) {

        gpsStatus.innerHTML =
            '❌ Browser tidak mendukung GPS.';

        return;
    }


    navigator.geolocation.getCurrentPosition(

        function(position) {

            const lat =
                position.coords.latitude;

            const lng =
                position.coords.longitude;


            latitude.value =
                lat;

            longitude.value =
                lng;


            latText.textContent =
                lat;

            lngText.textContent =
                lng;


            currentDistance =
                haversine(
                    lat,
                    lng,
                    schoolLat,
                    schoolLng
                );


            distanceText.textContent =
                Math.round(
                    currentDistance
                )
                +
                ' meter';


            if (
                currentDistance
                <=
                schoolRadius
            ) {

                gpsStatus.innerHTML =
                    '✅ Di dalam area sekolah';

                gpsStatus.style.color =
                    '#198754';

            } else {

                gpsStatus.innerHTML =
                    '❌ Di luar area sekolah';

                gpsStatus.style.color =
                    '#dc3545';

            }

        },

        function(error) {

            gpsStatus.innerHTML =
                '❌ GPS gagal: '
                +
                error.message;

            gpsStatus.style.color =
                '#dc3545';

        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }

    );

}


/* Jalankan GPS */

getLocation();


/* =====================================================
   UPLOAD FOTO
===================================================== */

fotoFile.addEventListener(
    'change',
    function() {

        const file =
            this.files[0];

        if (!file) {
            return;
        }


        const allowed = [
            'image/jpeg',
            'image/jpg',
            'image/png'
        ];


        if (
            !allowed.includes(
                file.type
            )
        ) {

            alert(
                'Foto harus JPG, JPEG, atau PNG.'
            );

            this.value = '';

            return;
        }


        if (
            file.size
            >
            5 * 1024 * 1024
        ) {

            alert(
                'Ukuran foto maksimal 5 MB.'
            );

            this.value = '';

            return;
        }


        const reader =
            new FileReader();


        reader.onload =
            function(event) {

                foto.value =
                    event.target.result;

                preview.src =
                    event.target.result;

                preview.style.display =
                    'block';

            };


        reader.readAsDataURL(file);

    }
);


/* =====================================================
   BUKA KAMERA
===================================================== */

btnCamera.addEventListener(
    'click',
    async function() {

        try {

            stream =
                await navigator
                .mediaDevices
                .getUserMedia({

                    video: {
                        facingMode: 'user'
                    },

                    audio: false

                });


            video.srcObject =
                stream;

            video.style.display =
                'block';

            btnCamera.style.display =
                'none';

            btnCapture.style.display =
                'inline-block';

            btnStop.style.display =
                'inline-block';

        } catch (error) {

            alert(
                'Kamera tidak dapat digunakan.\n\n'
                +
                error.message
                +
                '\n\nSilakan gunakan upload foto.'
            );

        }

    }
);


/* =====================================================
   AMBIL FOTO KAMERA
===================================================== */

btnCapture.addEventListener(
    'click',
    function() {

        if (!stream) {
            return;
        }


        canvas.width =
            video.videoWidth;

        canvas.height =
            video.videoHeight;


        const ctx =
            canvas.getContext(
                '2d'
            );


        ctx.drawImage(
            video,
            0,
            0,
            canvas.width,
            canvas.height
        );


        const image =
            canvas.toDataURL(
                'image/jpeg',
                0.9
            );


        foto.value =
            image;


        preview.src =
            image;

        preview.style.display =
            'block';


        stopCamera();


        alert(
            'Foto berhasil diambil.'
        );

    }
);


/* =====================================================
   STOP CAMERA
===================================================== */

btnStop.addEventListener(
    'click',
    function() {

        stopCamera();

    }
);


function stopCamera() {

    if (stream) {

        stream
            .getTracks()
            .forEach(
                function(track) {

                    track.stop();

                }
            );

        stream = null;

    }


    video.srcObject = null;

    video.style.display =
        'none';

    btnCamera.style.display =
        'inline-block';

    btnCapture.style.display =
        'none';

    btnStop.style.display =
        'none';

}


/* =====================================================
   FUNGSI SUBMIT
===================================================== */

function kirimPresensi(
    jenis
) {

    /* ---------------------------------------------
       CEK FOTO
    --------------------------------------------- */

    if (!foto.value) {

        alert(
            'Silakan upload atau ambil foto wajah terlebih dahulu.'
        );

        return;
    }


    /* ---------------------------------------------
       CEK GPS
    --------------------------------------------- */

    if (
        !latitude.value
        ||
        !longitude.value
    ) {

        alert(
            'Lokasi GPS belum tersedia. Tunggu sebentar lalu coba lagi.'
        );

        getLocation();

        return;
    }


    /* ---------------------------------------------
       CEK RADIUS
    --------------------------------------------- */

    if (
        currentDistance !== null
        &&
        currentDistance > schoolRadius
    ) {

        alert(
            'Kamu berada di luar radius sekolah.'
        );

        return;
    }


    /* ---------------------------------------------
       SET AKSI
    --------------------------------------------- */

    aksi.value =
        jenis;


    /* ---------------------------------------------
       TOMBOL
    --------------------------------------------- */

    btnHadir.disabled =
        true;

    btnPulang.disabled =
        true;


    /* ---------------------------------------------
       LANGSUNG POST KE PROSES ABSEN
    --------------------------------------------- */

    formAbsen.submit();

}


/* =====================================================
   ABSEN MASUK
===================================================== */

btnHadir.addEventListener(
    'click',
    function() {

        kirimPresensi(
            'hadir'
        );

    }
);


/* =====================================================
   ABSEN PULANG
===================================================== */

btnPulang.addEventListener(
    'click',
    function() {

        kirimPresensi(
            'pulang'
        );

    }
);

</script>

</body>

</html>