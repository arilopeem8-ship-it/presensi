<?php
require_once '../config/database.php';
require_once '../config/helper.php';

check_login();

$uid = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT nama_lengkap, face_vector FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$u = mysqli_fetch_assoc($result);

if (!$u) {
    die("Data pengguna tidak ditemukan.");
}

$nama = htmlspecialchars($u['nama_lengkap'] ?? 'Pengguna');
$vectorAda = !empty($u['face_vector']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Wajah - Presensi</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #222;
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
            text-align: center;
        }

        .nama {
            text-align: center;
            margin-bottom: 25px;
            color: #555;
        }

        .status {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            background: #fff3cd;
            color: #856404;
        }

        .status.sudah {
            background: #d1e7dd;
            color: #0f5132;
        }

        .pilihan {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .pilihan button {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            background: #e9ecef;
        }

        .pilihan button.active {
            background: #0d6efd;
            color: white;
        }

        .mode {
            display: none;
        }

        .mode.active {
            display: block;
        }

        video,
        #preview {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 12px;
            background: #111;
        }

        #preview {
            display: none;
        }

        .file-box {
            border: 2px dashed #bbb;
            border-radius: 12px;
            padding: 35px 20px;
            text-align: center;
            background: #fafafa;
        }

        .file-box input {
            margin-top: 15px;
        }

        .btn {
            width: 100%;
            margin-top: 15px;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #0d6efd;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:disabled {
            background: #aaa;
            cursor: not-allowed;
        }

        .info {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            background: #eef5ff;
            color: #174a8b;
            font-size: 14px;
            line-height: 1.5;
        }

        #pesan {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }

        canvas {
            display: none;
        }

        @media (max-width: 600px) {
            .container {
                margin: 15px auto;
                padding: 10px;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h2>📸 Pendaftaran Wajah</h2>

        <div class="nama">
            Akun: <strong><?= $nama ?></strong>
        </div>

        <?php if ($vectorAda): ?>
            <div class="status sudah">
                ✅ Wajah sudah terdaftar
            </div>
        <?php else: ?>
            <div class="status">
                ⚠️ Wajah belum terdaftar
            </div>
        <?php endif; ?>

        <div class="pilihan">
            <button type="button" id="btnKamera" class="active">
                📷 Gunakan Kamera
            </button>

            <button type="button" id="btnUpload">
                🖼️ Upload Foto
            </button>
        </div>

        <!-- ================= KAMERA ================= -->
        <div id="modeKamera" class="mode active">

            <video id="video" autoplay playsinline></video>

            <canvas id="canvas"></canvas>

            <button type="button" class="btn" id="btnAmbil">
                📸 Ambil Foto
            </button>

        </div>

        <!-- ================= UPLOAD ================= -->
        <div id="modeUpload" class="mode">

            <div class="file-box">

                <div>
                    Pilih foto wajah yang jelas
                </div>

                <input
                    type="file"
                    id="fileFoto"
                    accept="image/jpeg,image/jpg"
                >

            </div>

            <img id="preview" alt="Preview foto">

            <button type="button" class="btn" id="btnUploadFoto" disabled>
                ✅ Gunakan Foto Ini
            </button>

        </div>

        <div class="info">
            <strong>Tips:</strong><br>
            • Pastikan hanya ada satu wajah.<br>
            • Wajah menghadap kamera.<br>
            • Gunakan pencahayaan yang cukup.<br>
            • Jangan menggunakan foto yang terlalu buram.
        </div>

        <div id="pesan"></div>

    </div>

</div>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');

const btnKamera = document.getElementById('btnKamera');
const btnUpload = document.getElementById('btnUpload');

const modeKamera = document.getElementById('modeKamera');
const modeUpload = document.getElementById('modeUpload');

const btnAmbil = document.getElementById('btnAmbil');

const fileFoto = document.getElementById('fileFoto');
const preview = document.getElementById('preview');
const btnUploadFoto = document.getElementById('btnUploadFoto');

const pesan = document.getElementById('pesan');

let stream = null;


/* =====================================================
   PINDAH KE MODE KAMERA
===================================================== */

btnKamera.addEventListener('click', async function () {

    btnKamera.classList.add('active');
    btnUpload.classList.remove('active');

    modeKamera.classList.add('active');
    modeUpload.classList.remove('active');

    stopCamera();

    try {

        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user'
            },
            audio: false
        });

        video.srcObject = stream;

        pesan.innerHTML = '';

    } catch (error) {

        pesan.style.color = 'red';
        pesan.innerHTML =
            '❌ Kamera tidak dapat digunakan. Silakan gunakan Upload Foto.';

    }

});


/* =====================================================
   PINDAH KE MODE UPLOAD
===================================================== */

btnUpload.addEventListener('click', function () {

    btnUpload.classList.add('active');
    btnKamera.classList.remove('active');

    modeUpload.classList.add('active');
    modeKamera.classList.remove('active');

    stopCamera();

    pesan.innerHTML = '';

});


/* =====================================================
   START CAMERA
===================================================== */

async function startCamera() {

    try {

        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user'
            },
            audio: false
        });

        video.srcObject = stream;

    } catch (error) {

        pesan.style.color = 'red';

        pesan.innerHTML =
            '❌ Kamera tidak dapat digunakan. Silakan pilih Upload Foto.';

    }

}


/* =====================================================
   STOP CAMERA
===================================================== */

function stopCamera() {

    if (stream) {

        stream.getTracks().forEach(track => track.stop());

        stream = null;

    }

    video.srcObject = null;

}


/* =====================================================
   AMBIL FOTO DARI KAMERA
===================================================== */

btnAmbil.addEventListener('click', function () {

    if (!video.videoWidth || !video.videoHeight) {

        pesan.style.color = 'red';
        pesan.innerHTML = '❌ Kamera belum siap.';

        return;

    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');

    ctx.drawImage(
        video,
        0,
        0,
        canvas.width,
        canvas.height
    );

    const foto = canvas.toDataURL('image/jpeg', 0.88);

    kirimFoto(foto);

});


/* =====================================================
   PILIH FILE FOTO
===================================================== */

fileFoto.addEventListener('change', function () {

    const file = this.files[0];

    if (!file) {
        btnUploadFoto.disabled = true;
        return;
    }

    if (!['image/jpeg', 'image/jpg'].includes(file.type)) {

        pesan.style.color = 'red';
        pesan.innerHTML = '❌ Gunakan foto JPG/JPEG.';

        this.value = '';
        btnUploadFoto.disabled = true;

        return;
    }

    if (file.size > 5 * 1024 * 1024) {

        pesan.style.color = 'red';
        pesan.innerHTML = '❌ Ukuran foto maksimal 5 MB.';

        this.value = '';
        btnUploadFoto.disabled = true;

        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {

        preview.src = e.target.result;
        preview.style.display = 'block';

        btnUploadFoto.disabled = false;

        pesan.innerHTML = '';

    };

    reader.readAsDataURL(file);

});


/* =====================================================
   KIRIM FOTO UPLOAD
===================================================== */

btnUploadFoto.addEventListener('click', function () {

    if (!preview.src) {
        return;
    }

    kirimFoto(preview.src);

});


/* =====================================================
   KIRIM FOTO KE PHP
===================================================== */

async function kirimFoto(foto) {

    pesan.style.color = '#333';

    pesan.innerHTML =
        '⏳ Sedang memproses wajah...';

    btnAmbil.disabled = true;
    btnUploadFoto.disabled = true;

    try {

        const response = await fetch('proses_wajah.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },

            body: new URLSearchParams({
                foto: foto
            })

        });

        const text = await response.text();
        let data = null;

        try {
            data = JSON.parse(text);
        } catch (error) {
            const match = text.match(/\{.*\}/s);
            if (match) {
                try {
                    data = JSON.parse(match[0]);
                } catch (parseError) {
                    data = null;
                }
            }
        }

        if (data && data.success) {

            pesan.style.color = 'green';

            pesan.innerHTML =
                '✅ Wajah berhasil didaftarkan!';

            setTimeout(function () {

                window.location.href = 'absen.php';

            }, 1200);
            return;

        } else {

            pesan.style.color = 'red';

            pesan.innerHTML =
                '❌ ' + (data && data.message
                    ? data.message
                    : 'Respons server tidak valid: ' + text.slice(0, 200));

        }

    } catch (error) {

        pesan.style.color = 'red';

        pesan.innerHTML =
            '❌ Gagal menghubungi server: ' + error.message;

        console.error(error);

    } finally {
        btnAmbil.disabled = false;
        btnUploadFoto.disabled = false;
    }

}


/* =====================================================
   START
===================================================== */

startCamera();


/* =====================================================
   STOP CAMERA SAAT MENINGGALKAN HALAMAN
===================================================== */

window.addEventListener('beforeunload', function () {

    stopCamera();

});

</script>

</body>
</html>