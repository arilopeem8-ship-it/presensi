
<?php
require_once '../config/database.php';
require_once '../config/helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   CEK SESSION LOGIN
========================= */

if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        redirect('admin/index.php');
    } else {
        redirect('user/index.php');
    }
    exit;
}

/* =========================
   INISIALISASI
========================= */

$error = '';
$type = $_POST['type'] ?? 'siswa';

if (!in_array($type, ['siswa', 'admin'], true)) {
    $type = 'siswa';
}

/* =========================
   PROSES LOGIN
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* LOGIN SISWA */
    if ($type === 'siswa') {

        $nisn = trim($_POST['nisn'] ?? '');
        $tgl = trim($_POST['tanggal_lahir'] ?? '');

        if ($nisn === '' || $tgl === '') {
            $error = 'NISN dan tanggal lahir wajib diisi.';
        } else {
            $tanggal = DateTime::createFromFormat('!Y-m-d', $tgl);
            $tanggalValid = $tanggal &&
                $tanggal->format('Y-m-d') === $tgl;

            if (!$tanggalValid) {
                $error = 'Format tanggal lahir tidak valid.';
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT id, nisn, nama_lengkap, role
                     FROM users
                     WHERE nisn = ?
                       AND tanggal_lahir = ?
                       AND role = 'user'
                     LIMIT 1"
                );

                if (!$stmt) {
                    $error = 'Terjadi kesalahan pada database.';
                } else {
                    mysqli_stmt_bind_param($stmt, 'ss', $nisn, $tgl);
                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);
                    $user = mysqli_fetch_assoc($result);

                    mysqli_stmt_close($stmt);

                    if ($user) {
                        session_regenerate_id(true);

                        $_SESSION['login'] = true;
                        $_SESSION['user_id'] = (int) $user['id'];
                        $_SESSION['id'] = (int) $user['id'];
                        $_SESSION['nama'] = $user['nama_lengkap'];
                        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                        $_SESSION['nisn'] = $user['nisn'];
                        $_SESSION['role'] = 'user';

                        redirect('user/index.php');
                        exit;
                    } else {
                        $error = 'NISN atau tanggal lahir salah.';
                    }
                }
            }
        }

    } else {

        /* LOGIN ADMIN */

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password admin wajib diisi.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, username, password, nama_lengkap, role
                 FROM users
                 WHERE username = ?
                   AND role = 'admin'
                 LIMIT 1"
            );

            if (!$stmt) {
                $error = 'Terjadi kesalahan pada database.';
            } else {
                mysqli_stmt_bind_param($stmt, 's', $username);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if (
                    $user &&
                    password_verify($password, $user['password'])
                ) {
                    session_regenerate_id(true);

                    $_SESSION['login'] = true;
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['id'] = (int) $user['id'];
                    $_SESSION['admin_id'] = (int) $user['id'];
                    $_SESSION['nama'] = $user['nama_lengkap'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role'] = 'admin';

                    redirect('admin/index.php');
                    exit;
                } else {
                    $error = 'Username atau password admin salah.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Sistem Presensi</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --navy: #102a56;
    --blue: #2563eb;
    --light-blue: #eff6ff;
    --text: #172b4d;
    --muted: #718096;
    --border: #dce4ef;
}

body {
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    color: var(--text);
    background: #f3f7fd;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    position: relative;
    overflow-x: hidden;
}

body::before {
    content: "";
    position: fixed;
    width: 520px;
    height: 520px;
    background: linear-gradient(135deg, #1d4ed8, #60a5fa);
    border-radius: 50%;
    top: -270px;
    right: -150px;
    opacity: .12;
    pointer-events: none;
}

body::after {
    content: "";
    position: fixed;
    width: 420px;
    height: 420px;
    background: #93c5fd;
    border-radius: 50%;
    bottom: -250px;
    left: -100px;
    opacity: .18;
    pointer-events: none;
}

.login-wrapper {
    width: 100%;
    max-width: 980px;
    min-height: 590px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 24px 70px rgba(16, 42, 86, .13);
    position: relative;
    z-index: 1;
}

.welcome-panel {
    background: linear-gradient(145deg, #102a56 0%, #174a91 55%, #2563eb 100%);
    color: white;
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.welcome-panel::before {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 50%;
    right: -130px;
    top: 90px;
    box-shadow: 0 0 0 35px rgba(255,255,255,.035),
                0 0 0 70px rgba(255,255,255,.025);
}

.brand {
    display: flex;
    align-items: center;
    gap: 13px;
    position: relative;
    z-index: 1;
}

.brand-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
}

.brand-name {
    font-size: 17px;
    font-weight: 700;
    letter-spacing: .2px;
}

.brand-subtitle {
    margin-top: 4px;
    font-size: 12px;
    color: #dbeafe;
}

.welcome-content {
    position: relative;
    z-index: 1;
    margin: 55px 0;
}

.welcome-tag {
    display: inline-block;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.17);
    border-radius: 30px;
    padding: 8px 13px;
    font-size: 11px;
    letter-spacing: 1.1px;
    font-weight: 700;
    margin-bottom: 24px;
}

.welcome-content h1 {
    color: white;
    font-size: clamp(30px, 3.5vw, 40px);
    line-height: 1.2;
    letter-spacing: -.8px;
    margin-bottom: 18px;
}

.welcome-content p {
    color: #dbeafe;
    line-height: 1.8;
    font-size: 14px;
    max-width: 340px;
}

.feature {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 28px;
    font-size: 13px;
    color: #eff6ff;
}

.feature-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgba(255,255,255,.13);
    font-size: 17px;
}

.welcome-footer {
    color: #bfdbfe;
    font-size: 11px;
    position: relative;
    z-index: 1;
}

.form-panel {
    padding: 55px 45px 35px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-heading {
    margin-bottom: 32px;
}

.form-heading .small-label {
    color: var(--blue);
    text-transform: uppercase;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 1.6px;
    margin-bottom: 12px;
}

.form-heading h2 {
    color: var(--navy);
    font-size: 30px;
    letter-spacing: -.6px;
    margin-bottom: 10px;
}

.form-heading p {
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

.alert {
    padding: 13px 14px;
    margin-bottom: 22px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.5;
}

.alert.error {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #be123c;
}

.form-group {
    margin-bottom: 21px;
}

.form-group label {
    display: block;
    color: #344563;
    font-size: 13px;
    font-weight: 650;
    margin-bottom: 9px;
}

.input-wrap {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 17px;
    color: #8293ad;
    pointer-events: none;
}

.form-group input {
    width: 100%;
    height: 51px;
    border: 1px solid var(--border);
    border-radius: 11px;
    background: #fbfdff;
    color: var(--text);
    font-family: inherit;
    font-size: 14px;
    padding: 0 45px;
    outline: none;
    transition: border-color .2s, box-shadow .2s, background .2s;
}

.form-group input::placeholder {
    color: #a0aec0;
}

.form-group input:focus {
    border-color: var(--blue);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(37,99,235,.10);
}

.role-switch {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
}

.role-btn {
    flex: 1;
    padding: 13px;
    border: 1px solid var(--border);
    background: #f8fafc;
    color: var(--text);
    border-radius: 10px;
    cursor: pointer;
    font-weight: 650;
    font-family: inherit;
}

.role-btn.active {
    background: var(--light-blue);
    color: var(--blue);
    border-color: var(--blue);
}

.role-btn:focus-visible {
    outline: 3px solid #93c5fd;
    outline-offset: 2px;
}

.login-btn {
    width: 100%;
    min-height: 52px;
    border: none;
    border-radius: 11px;
    background: linear-gradient(100deg, #1d4ed8, #2563eb);
    color: white;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 5px;
    box-shadow: 0 8px 18px rgba(37,99,235,.22);
    transition: transform .2s, box-shadow .2s;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 23px rgba(37,99,235,.3);
}

.secure-note {
    color: #8795aa;
    font-size: 11px;
    margin-top: 24px;
    text-align: center;
}

.form-footer {
    text-align: center;
    color: #9aa7b8;
    font-size: 11px;
    margin-top: 36px;
    line-height: 1.7;
}

[hidden] {
    display: none !important;
}

@media (max-width: 760px) {
    body {
        padding: 16px;
    }

    .login-wrapper {
        max-width: 440px;
        min-height: auto;
        grid-template-columns: 1fr;
        border-radius: 20px;
    }

    .welcome-panel {
        padding: 25px 27px;
    }

    .welcome-content {
        margin: 28px 0 10px;
    }

    .welcome-content h1 {
        font-size: 27px;
    }

    .welcome-content p {
        font-size: 13px;
    }

    .feature {
        margin-top: 18px;
    }

    .welcome-footer {
        margin-top: 28px;
    }

    .form-panel {
        padding: 34px 27px 28px;
    }

    .form-heading {
        margin-bottom: 25px;
    }

    .form-heading h2 {
        font-size: 27px;
    }

    .form-footer {
        margin-top: 27px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition: none !important;
    }
}
</style>
</head>

<body class="login-page">

<main class="login-wrapper">

    <section class="welcome-panel">
        <div class="brand">
            <div class="brand-icon">📋</div>
            <div>
                <div class="brand-name">Sistem Presensi</div>
                <div class="brand-subtitle">Presensi Sekolah Digital</div>
            </div>
        </div>

        <div class="welcome-content">
            <span class="welcome-tag">SELAMAT DATANG</span>
            <h1>Presensi jadi lebih mudah.</h1>
            <p>
                Masuk ke sistem untuk mengakses layanan presensi sekolah
                dengan cepat dan praktis.
            </p>

            <div class="feature">
                <span class="feature-icon">✓</span>
                Akses untuk siswa dan admin
            </div>

            <div class="feature">
                <span class="feature-icon">🔒</span>
                Sistem presensi sekolah
            </div>
        </div>

        <p class="welcome-footer">
            © <?= date('Y') ?> Sistem Presensi Sekolah
        </p>
    </section>

    <section class="form-panel">

        <div class="form-heading">
            <p class="small-label">AKUN PENGGUNA</p>
            <h2>Masuk Akun</h2>
            <p>Silakan pilih jenis akun dan isi data login kamu.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert error" role="alert">
                ⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <input
                type="hidden"
                name="type"
                id="type"
                value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
            >

            <div class="form-group">
                <label>Masuk sebagai</label>

                <div class="role-switch">
                    <button
                        type="button"
                        id="btn-siswa"
                        class="role-btn"
                        onclick="pilihType('siswa')"
                        aria-pressed="false"
                    >👤 Siswa</button>

                    <button
                        type="button"
                        id="btn-admin"
                        class="role-btn"
                        onclick="pilihType('admin')"
                        aria-pressed="false"
                    >👑 Admin</button>
                </div>
            </div>

            <div id="box-siswa">
                <div class="form-group">
                    <label for="nisn">NISN</label>
                    <div class="input-wrap">
                        <span class="input-icon">🪪</span>
                        <input
                            type="text"
                            id="nisn"
                            name="nisn"
                            placeholder="Masukkan NISN..."
                            inputmode="numeric"
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['nisn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="tanggal_lahir">
                        Tanggal Lahir (sebagai password)
                    </label>
                    <div class="input-wrap">
                        <span class="input-icon">🎂</span>
                        <input
                            type="date"
                            id="tanggal_lahir"
                            name="tanggal_lahir"
                            autocomplete="bday"
                            value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>
                </div>
            </div>

            <div id="box-admin" hidden>
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon">👤</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Masukkan username..."
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">🔐</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password..."
                            autocomplete="current-password"
                        >
                    </div>
                </div>
            </div>

            <button type="submit" class="login-btn">
                🚀 Masuk
            </button>
        </form>

        <p class="secure-note">🔒 Sistem Presensi Sekolah</p>

        <p class="form-footer">
            Gunakan akun yang telah terdaftar untuk melanjutkan.
        </p>

    </section>
</main>

<script>
function pilihType(t) {
    const siswa = t === 'siswa';

    document.getElementById('type').value = siswa ? 'siswa' : 'admin';

    document.getElementById('box-siswa').hidden = !siswa;
    document.getElementById('box-admin').hidden = siswa;

    document.getElementById('btn-siswa').classList.toggle('active', siswa);
    document.getElementById('btn-admin').classList.toggle('active', !siswa);

    document.getElementById('btn-siswa').setAttribute('aria-pressed', String(siswa));
    document.getElementById('btn-admin').setAttribute('aria-pressed', String(!siswa));

    const nisn = document.getElementById('nisn');
    const tanggal = document.getElementById('tanggal_lahir');
    const username = document.getElementById('username');
    const password = document.getElementById('password');

    nisn.disabled = !siswa;
    tanggal.disabled = !siswa;
    username.disabled = siswa;
    password.disabled = siswa;

    nisn.required = siswa;
    tanggal.required = siswa;
    username.required = !siswa;
    password.required = !siswa;
}

pilihType(document.getElementById('type').value);
</script>

</body>
</html>