<?php
require_once '../../config/database.php';
require_once '../../config/helper.php';
check_admin();

$page_title = 'Data Jabatan';
$msg = $msg_type = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM jabatan WHERE id='$id'");
    $msg = 'Jabatan berhasil dihapus.'; $msg_type = 'success';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama_jabatan']));
    mysqli_query($conn, "INSERT INTO jabatan (nama_jabatan) VALUES ('$nama')");
    $msg = 'Jabatan berhasil ditambahkan!'; $msg_type = 'success';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
    $id   = (int)$_POST['id'];
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama_jabatan']));
    mysqli_query($conn, "UPDATE jabatan SET nama_jabatan='$nama' WHERE id='$id'");
    $msg = 'Jabatan berhasil diperbarui!'; $msg_type = 'success';
}

$res = mysqli_query($conn, "SELECT * FROM jabatan ORDER BY nama_jabatan");
require_once '../../config/header_admin.php';
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">💼 Data Jabatan</h2>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modal-add').classList.add('show')">+ Tambah Jabatan</button>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>#</th><th>Nama Jabatan</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php $no=1; while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($row['nama_jabatan']) ?></strong></td>
                    <td><div class="btn-group">
                        <button class="btn btn-secondary btn-sm" onclick="openEdit(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_jabatan'], ENT_QUOTES) ?>')">✏️ Edit</button>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus jabatan ini?')">🗑️ Hapus</a>
                    </div></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-backdrop" id="modal-add">
    <div class="modal">
        <div class="modal-header"><h3 class="modal-title">Tambah Jabatan</h3>
            <button class="modal-close" onclick="document.getElementById('modal-add').classList.remove('show')">✕</button></div>
        <form method="POST"><input type="hidden" name="action" value="add">
            <div class="modal-body"><div class="form-group"><label class="form-label">Nama Jabatan</label>
                <input type="text" name="nama_jabatan" class="form-control" placeholder="contoh: Siswa" required></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div></form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-backdrop" id="modal-edit">
    <div class="modal">
        <div class="modal-header"><h3 class="modal-title">Edit Jabatan</h3>
            <button class="modal-close" onclick="document.getElementById('modal-edit').classList.remove('show')">✕</button></div>
        <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit-id">
            <div class="modal-body"><div class="form-group"><label class="form-label">Nama Jabatan</label>
                <input type="text" name="nama_jabatan" id="edit-nama" class="form-control" required></div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-edit').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div></form>
    </div>
</div>
<script>
function openEdit(id, nama) {
    document.getElementById('edit-id').value   = id;
    document.getElementById('edit-nama').value = nama;
    document.getElementById('modal-edit').classList.add('show');
}
</script>

<?php require_once '../../config/footer_admin.php'; ?>
