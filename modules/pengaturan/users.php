<?php
/**
 * FILE: modules/pengaturan/users.php
 * Modul Manajemen Data User & Hak Akses Role (Admin, Pengajar, Orang Tua Santri)
 * Tabel: users (id, nama_lengkap, username, password, role, jabatan_label, no_hp, foto, status_aktif)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin']);

$page_title = 'Manajemen Data User';
$alert_msg  = '';
$alert_type = 'success';

// Proses CRUD User & Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id            = (int) ($_POST['id'] ?? 0);
        $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
        $username      = trim($_POST['username'] ?? '');
        $password_raw  = trim($_POST['password'] ?? '');
        $role_u        = in_array($_POST['role'] ?? '', ['admin', 'pengajar', 'orang_tua'], true) ? $_POST['role'] : 'orang_tua';
        $jabatan_label = trim($_POST['jabatan_label'] ?? ($role_u === 'orang_tua' ? 'Orang Tua Santri' : ucfirst($role_u)));
        $no_hp         = trim($_POST['no_hp'] ?? '');
        $status_aktif  = ($_POST['status_aktif'] ?? 'Aktif') === 'Nonaktif' ? 'Nonaktif' : 'Aktif';

        if ($aksi === 'tambah') {
            $pass_hash = password_hash($password_raw ?: ($username . '123'), PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (nama_lengkap, username, password, role, jabatan_label, no_hp, status_aktif) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssssss', $nama_lengkap, $username, $pass_hash, $role_u, $jabatan_label, $no_hp, $status_aktif);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Akun pengguna '$username' berhasil ditambahkan!";
            } else {
                $alert_msg  = 'Gagal menambah user (username mungkin sudah digunakan): ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            if ($password_raw !== '') {
                $pass_hash = password_hash($password_raw, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap=?, username=?, password=?, role=?, jabatan_label=?, no_hp=?, status_aktif=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'sssssssi', $nama_lengkap, $username, $pass_hash, $role_u, $jabatan_label, $no_hp, $status_aktif, $id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap=?, username=?, role=?, jabatan_label=?, no_hp=?, status_aktif=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'ssssssi', $nama_lengkap, $username, $role_u, $jabatan_label, $no_hp, $status_aktif, $id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Data akun '$username' berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal memperbarui akun: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            $alert_msg  = 'Anda tidak dapat menghapus akun yang sedang digunakan login!';
            $alert_type = 'warning';
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $alert_msg = 'Akun pengguna berhasil dihapus!';
        }
    }
}

$res_users = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-users-gear me-2"></i>Manajemen Data User &amp; Role</h4>
        <small class="text-muted">Kelola akun login Admin Lembaga, Asatidz/Musyrif, dan Orang Tua Santri</small>
    </div>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
        <i class="fa-solid fa-user-plus me-1"></i> Tambah User Baru
    </button>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-info me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-user-shield"></i> Daftar Akun Pengguna Sistem SIMTS</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role Sistem</th>
                    <th>Label Jabatan</th>
                    <th>No. HP / WhatsApp</th>
                    <th>Status Akun</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_users)): ?>
                <?php
                    $badge_role = match ($row['role']) {
                        'admin'    => 'bg-dark',
                        'pengajar' => 'bg-primary',
                        default    => 'bg-info text-dark'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-bold text-dark"><?= e($row['nama_lengkap']) ?></td>
                    <td><code><?= e($row['username']) ?></code></td>
                    <td><span class="badge <?= $badge_role ?>"><?= e(strtoupper($row['role'])) ?></span></td>
                    <td><?= e($row['jabatan_label']) ?></td>
                    <td><?= e($row['no_hp'] ?: '-') ?></td>
                    <td>
                        <span class="badge <?= $row['status_aktif'] === 'Aktif' ? 'bg-success' : 'bg-danger' ?>">
                            <?= e($row['status_aktif']) ?>
                        </span>
                    </td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-user"
                                data-user='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditUser" title="Edit / Reset Password">
                            <i class="fa-solid fa-key me-1"></i> Edit
                        </button>
                        <?php if ((int) $row['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-user"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_lengkap']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusUser">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="users.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>Tambah Akun Pengguna Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username Login</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin Lembaga (admin)</option>
                                <option value="pengajar">Asatidz / Musyrif (pengajar)</option>
                                <option value="orang_tua">Orang Tua Santri (orang_tua)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Label Jabatan</label>
                            <input type="text" name="jabatan_label" class="form-control" value="Orang Tua Santri" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">No. HP / WhatsApp</label>
                            <input type="text" name="no_hp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Akun</label>
                            <select name="status_aktif" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User & Reset Password -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="users.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="eu_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-user-pen me-2"></i>Edit Akun &amp; Reset Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="eu_nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Username Login</label>
                            <input type="text" name="username" id="eu_username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role Akses</label>
                            <select name="role" id="eu_role" class="form-select" required>
                                <option value="admin">Admin Lembaga (admin)</option>
                                <option value="pengajar">Asatidz / Musyrif (pengajar)</option>
                                <option value="orang_tua">Orang Tua Santri (orang_tua)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Label Jabatan</label>
                            <input type="text" name="jabatan_label" id="eu_jabatan" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">No. HP / WhatsApp</label>
                            <input type="text" name="no_hp" id="eu_hp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Akun</label>
                            <select name="status_aktif" id="eu_status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus User -->
<div class="modal fade" id="modalHapusUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="users.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hu_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Akun</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus akun pengguna:</p>
                    <h6 class="fw-bold text-danger" id="hu_nama"></h6>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-user'));
            document.getElementById('eu_id').value       = d.id;
            document.getElementById('eu_nama').value     = d.nama_lengkap;
            document.getElementById('eu_username').value = d.username;
            document.getElementById('eu_role').value     = d.role;
            document.getElementById('eu_jabatan').value  = d.jabatan_label || '';
            document.getElementById('eu_hp').value       = d.no_hp || '';
            document.getElementById('eu_status').value   = d.status_aktif;
        });
    });

    document.querySelectorAll('.btn-hapus-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hu_id').value = this.getAttribute('data-id');
            document.getElementById('hu_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
