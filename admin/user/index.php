<?php
require_once '../../config/database.php';
require_once '../../config/helper.php';
check_admin();

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['csrf_user'])) $_SESSION['csrf_user'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_user'];
$error = '';
$success = $_SESSION['user_success'] ?? '';
unset($_SESSION['user_success']);
$edit = null;

function user_stmt(mysqli $conn, string $sql): mysqli_stmt {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) throw new RuntimeException(mysqli_error($conn));
    return $stmt;
}
function user_date_valid(string $date): bool {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf_token, (string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman.');
        }
        if (isset($_POST['hapus_id'])) {
            $id = filter_var($_POST['hapus_id'], FILTER_VALIDATE_INT);
            if (!$id || $id < 1) throw new RuntimeException('ID user tidak valid.');
            $admin_id = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
            if ($id === $admin_id) throw new RuntimeException('Akun yang sedang login tidak boleh dihapus.');
            $stmt = user_stmt($conn, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (!mysqli_stmt_execute($stmt)) throw new RuntimeException(mysqli_stmt_error($stmt));
            if (mysqli_stmt_affected_rows($stmt) < 1) throw new RuntimeException('User tidak ditemukan.');
            $_SESSION['user_success'] = 'User berhasil dihapus.';
            header('Location: index.php'); exit;
        }
        if (isset($_POST['simpan'])) {
            $id = (int) ($_POST['id'] ?? 0);
            $nama = trim((string) ($_POST['nama_lengkap'] ?? ''));
            $role = (string) ($_POST['role'] ?? 'user');
            $nisn = trim((string) ($_POST['nisn'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $tanggal = trim((string) ($_POST['tanggal_lahir'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $kelas = (int) ($_POST['id_kelas'] ?? 0);
            if ($id < 0) throw new RuntimeException('ID tidak valid.');
            if ($nama === '') throw new RuntimeException('Nama lengkap wajib diisi.');
            if (!in_array($role, ['admin','user'], true)) throw new RuntimeException('Role tidak valid.');
            $old = null;
            if ($id > 0) {
                $st = user_stmt($conn, 'SELECT * FROM users WHERE id = ?');
                mysqli_stmt_bind_param($st, 'i', $id);
                mysqli_stmt_execute($st);
                $old = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
                if (!$old) throw new RuntimeException('User yang diedit tidak ditemukan.');
            }
            if ($role === 'user') {
                if ($nisn === '' || !user_date_valid($tanggal)) throw new RuntimeException('NISN dan tanggal lahir valid wajib diisi.');
                $username = $nisn;
                $nisn_db = $nisn;
                $tgl_db = $tanggal;
                // Password siswa mengikuti tanggal lahir jika akun baru atau tanggal lahir diubah.
                $update_password = !$old || $old['role'] !== 'user' || ($old['tanggal_lahir'] ?? '') !== $tanggal;
                if ($update_password) $password = $tanggal;
            } else {
                if ($username === '') throw new RuntimeException('Username admin wajib diisi.');
                if (!$old && $password === '') throw new RuntimeException('Password admin wajib diisi.');
                $nisn_db = null;
                $tgl_db = null;
                $kelas = 0;
                $update_password = !$old || $password !== '';
            }
            $kelas_db = $kelas > 0 ? $kelas : null;
            if ($kelas_db !== null) {
                $st = user_stmt($conn, 'SELECT id FROM kelas WHERE id = ?');
                mysqli_stmt_bind_param($st, 'i', $kelas_db);
                mysqli_stmt_execute($st);
                if (!mysqli_fetch_assoc(mysqli_stmt_get_result($st))) throw new RuntimeException('Kelas tidak ditemukan.');
            }
            $st = user_stmt($conn, "SELECT id FROM users WHERE (username = ? OR (nisn IS NOT NULL AND nisn = ?)) AND id <> ? LIMIT 1");
            mysqli_stmt_bind_param($st, 'ssi', $username, $nisn_db, $id);
            mysqli_stmt_execute($st);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($st))) throw new RuntimeException('Username atau NISN sudah digunakan.');
            if ($old) {
                if ($update_password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $st = user_stmt($conn, 'UPDATE users SET username=?, nisn=?, password=?, nama_lengkap=?, tanggal_lahir=?, role=?, id_kelas=? WHERE id=?');
                    mysqli_stmt_bind_param($st, 'ssssssii', $username, $nisn_db, $hash, $nama, $tgl_db, $role, $kelas_db, $id);
                } else {
                    $st = user_stmt($conn, 'UPDATE users SET username=?, nisn=?, nama_lengkap=?, tanggal_lahir=?, role=?, id_kelas=? WHERE id=?');
                    mysqli_stmt_bind_param($st, 'sssssii', $username, $nisn_db, $nama, $tgl_db, $role, $kelas_db, $id);
                }
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st = user_stmt($conn, 'INSERT INTO users (username,nisn,password,nama_lengkap,tanggal_lahir,role,id_kelas) VALUES (?,?,?,?,?,?,?)');
                mysqli_stmt_bind_param($st, 'ssssssi', $username, $nisn_db, $hash, $nama, $tgl_db, $role, $kelas_db);
            }
            if (!mysqli_stmt_execute($st)) throw new RuntimeException(mysqli_stmt_error($st));
            $_SESSION['user_success'] = $old ? 'Data user berhasil diperbarui.' : 'User berhasil ditambahkan.';
            header('Location: index.php'); exit;
        }
    }
    if (isset($_GET['edit'])) {
        $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
        if (!$id || $id < 1) throw new RuntimeException('ID edit tidak valid.');
        $st = user_stmt($conn, 'SELECT * FROM users WHERE id = ?');
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        if (!$edit) throw new RuntimeException('User tidak ditemukan.');
    }
} catch (Throwable $ex) {
    $error = 'Operasi gagal: ' . $ex->getMessage();
}
$kelas_result = mysqli_query($conn, 'SELECT id,nama_kelas FROM kelas ORDER BY nama_kelas ASC');
$users_result = mysqli_query($conn, 'SELECT u.*, k.nama_kelas FROM users u LEFT JOIN kelas k ON k.id = u.id_kelas ORDER BY u.id DESC');
if (!$kelas_result || !$users_result) $error = 'Gagal mengambil data: ' . mysqli_error($conn);
$jumlah_user = $users_result ? mysqli_num_rows($users_result) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data User | Sistem Presensi</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<!-- SIDEBAR -->
<header class="topbar">
    <div class="brand">
        <div class="brand-logo">
            <i class="fa-solid fa-school"></i>
        </div>

        <div class="brand-text">
            <strong>Sistem Presensi</strong>
            <span>Panel Administrator</span>
        </div>
    </div>

    <nav class="nav">
        <a href="../index.php">
            <i class="fa-solid fa-gauge"></i>
            Dashboard
        </a>

        <a href="../presensi/index.php">
            <i class="fa-solid fa-calendar-check"></i>
            Presensi
        </a>

        <a href="index.php" class="active">
            <i class="fa-solid fa-users"></i>
            Data User
        </a>

        <a href="../kelas/index.php">
            <i class="fa-solid fa-school"></i>
            Data Kelas
        </a>

        <a href="../jam_kerja/index.php">
            <i class="fa-solid fa-clock"></i>
            Jadwal Presensi
        </a>

        <a href="../perizinan/index.php">
            <i class="fa-solid fa-envelope"></i>
            Perizinan
        </a>

        <a href="../laporan/index.php">
            <i class="fa-solid fa-file-lines"></i>
            Laporan
        </a>

        <a href="../../auth/logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </nav>
</header>

<!-- KONTEN UTAMA -->
<main class="container">

    <section class="hero">
        <div>
            <h1>Data User</h1>
            <p>Kelola akun admin dan siswa pada sistem presensi.</p>
        </div>

    </section>

    <?php if ($success !== ''): ?>
        <div class="alert" role="status"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT -->
    <section class="card">
        <h2>
            <i class="fa-solid fa-user-plus"></i>
            <?= $edit ? 'Edit Data User' : 'Tambah User' ?>
        </h2>

        <p class="muted">
            <?= $edit
                ? 'Ubah informasi akun pada formulir berikut.'
                : 'Masukkan informasi akun baru pada formulir berikut.' ?>
        </p>

        <form method="POST" action="index.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="id"
                   value="<?= (int) ($edit['id'] ?? 0) ?>">

            <div class="grid form-grid">

                <div class="form-group">
                    <label for="role">Role Akun</label>
                    <select name="role" id="role" onchange="toggleRole()">
                        <option value="user"
                            <?= ($edit['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>
                            Siswa
                        </option>

                        <option value="admin"
                            <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                            Admin
                        </option>
                    </select>
                </div>

                <div class="form-group field-siswa">
                    <label for="nisn">NISN</label>
                    <input type="text" name="nisn" id="nisn"
                           value="<?= htmlspecialchars($edit['nisn'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Masukkan NISN">
                </div>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap"
                           value="<?= htmlspecialchars($edit['nama_lengkap'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Masukkan nama lengkap" required>
                </div>

           <div class="form-group field-siswa">
                <label>Tanggal Lahir (password otomatis)</label>
                <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="<?= e($edit['tanggal_lahir'] ?? '') ?>">
            </div>

                <div class="form-group field-admin">
                    <label for="username">Username Admin</label>
                    <input type="text" name="username" id="username"
                           value="<?= htmlspecialchars(
                               ($edit['role'] ?? '') === 'admin'
                                   ? ($edit['username'] ?? '')
                                   : '',
                               ENT_QUOTES,
                               'UTF-8'
                           ) ?>"
                           placeholder="Masukkan username admin">
                </div>

                <div class="form-group">
                    <label for="id_kelas">Kelas</label>
                    <select name="id_kelas" id="id_kelas">
                        <option value="0">-- Pilih Kelas --</option>

                        <?php if ($kelas_result): ?>
                            <?php while ($kelas = mysqli_fetch_assoc($kelas_result)): ?>
                                <option value="<?= (int) $kelas['id'] ?>"
                                    <?= (int) ($edit['id_kelas'] ?? 0) === (int) $kelas['id']
                                        ? 'selected'
                                        : '' ?>>
                                    <?= htmlspecialchars($kelas['nama_kelas'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

              <div class="form-group field-admin">
                <label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '' ?></label>
                <input type="password" name="password" id="password" placeholder="Masukkan password...">
            </div>


            <div class="actions">
                <button type="submit" name="simpan" value="1" class="btn btn-add">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?= $edit ? 'Simpan Perubahan' : 'Tambah User' ?>
                </button>
                
        <div class="actions">
            <a href="index.php" class="btn btn-add">
                <i class="fa-solid fa-rotate"></i>
                Refresh Data
            </a>
        </div>

                <?php if ($edit): ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark"></i>
                        Batal
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- TABEL USER -->
    <section class="card" style="margin-top: 24px;">
        <div class="hero">
            <div>
                <h2>Daftar User</h2>
                <p>Total data: <strong><?= $jumlah_user ?></strong> akun</p>
            </div>
        </div>

        <div class="table-responsive table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>Role</th>
                        <th>Tanggal Lahir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($users_result && mysqli_num_rows($users_result) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($user['nama_lengkap'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['username'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['nisn'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['nama_kelas'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </td>

                                <td>
                                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                                        <span class="badge badge-info">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Siswa</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= !empty($user['tanggal_lahir'])
                                        ? htmlspecialchars($user['tanggal_lahir'], ENT_QUOTES, 'UTF-8')
                                        : '-' ?>
                                </td>

                                <td>
                                    <div class="actions">
                                        <a href="index.php?edit=<?= (int) $user['id'] ?>"
                                           class="btn btn-edit">
                                            <i class="fa-solid fa-pen"></i>
                                            Edit
                                        </a>

                                        <?php if ((int) $user['id'] !== (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0)): ?>
                                        <form method="POST" action="index.php" style="display:inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                         <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                         <input type="hidden" name="hapus_id" value="<?= (int) $user['id'] ?>">
                                         <button type="submit" class="btn btn-delete">
                                            <i class="fa-solid fa-trash"></i>
                                            Hapus
                                         </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">
                                Belum ada data user.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
function toggleRole() {
    const role = document.getElementById('role').value;
    const siswa = document.querySelectorAll('.field-siswa');
    const admin = document.querySelectorAll('.field-admin');

    siswa.forEach(function (field) {
        field.style.display = role === 'user' ? '' : 'none';
    });

    admin.forEach(function (field) {
        field.style.display = role === 'admin' ? '' : 'none';
    });

    document.getElementById('nisn').required = role === 'user';
    document.getElementById('tanggal_lahir').required = role === 'user';
    document.getElementById('username').required = role === 'admin';

    const password = document.getElementById('password');
    password.required = role === 'admin'
        && document.querySelector('input[name="id"]').value === '0';
}

document.addEventListener('DOMContentLoaded', toggleRole);
</script>

</body>
</html>