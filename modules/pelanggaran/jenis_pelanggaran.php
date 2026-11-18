<?php
/**
 * FILE: modules/pelanggaran/jenis_pelanggaran.php
 * Modul Master Jenis Pelanggaran & Bobot Poin Kedisiplinan (CRUD + DataTables)
 * Tabel: jenis_pelanggaran (id, kode_pelanggaran, nama_pelanggaran, kategori, poin, sanksi_pembinaan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin', 'pengajar']);

$page_title = 'Master Jenis Pelanggaran & Poin';
$alert_msg  = '';
$alert_type = 'success';

// Proses CRUD Jenis Pelanggaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id               = (int) ($_POST['id'] ?? 0);
        $kode_pelanggaran = trim($_POST['kode_pelanggaran'] ?? '');
        $nama_pelanggaran = trim($_POST['nama_pelanggaran'] ?? '');
        $kategori         = in_array($_POST['kategori'] ?? '', ['Ringan', 'Sedang', 'Berat'], true) ? $_POST['kategori'] : 'Ringan';
        $poin             = max(1, (int) ($_POST['poin'] ?? 5));
        $sanksi_pembinaan = trim($_POST['sanksi_pembinaan'] ?? '');

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO jenis_pelanggaran (kode_pelanggaran, nama_pelanggaran, kategori, poin, sanksi_pembinaan) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssis', $kode_pelanggaran, $nama_pelanggaran, $kategori, $poin, $sanksi_pembinaan);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Jenis pelanggaran '$nama_pelanggaran' berhasil ditambahkan!";
            } else {
                $alert_msg  = 'Gagal menambah jenis pelanggaran: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE jenis_pelanggaran SET kode_pelanggaran=?, nama_pelanggaran=?, kategori=?, poin=?, sanksi_pembinaan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssisi', $kode_pelanggaran, $nama_pelanggaran, $kategori, $poin, $sanksi_pembinaan, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Jenis pelanggaran '$nama_pelanggaran' berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal memperbarui jenis pelanggaran: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM jenis_pelanggaran WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Master jenis pelanggaran berhasil dihapus!';
    }
}

$res_jenis = mysqli_query($conn, "SELECT * FROM jenis_pelanggaran ORDER BY kode_pelanggaran ASC");

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-scale-balanced me-2"></i>Master Jenis Pelanggaran &amp; Poin</h4>
        <small class="text-muted">Aturan tata tertib santri, klasifikasi kategori pelanggaran, bobot poin, dan sanksi pembinaan</small>
    </div>
    <div class="d-flex gap-2">
        <a href="pelanggaran_input.php" class="btn btn-gold-siata">
            <i class="fa-solid fa-file-circle-exclamation me-1"></i> Input Kejadian Pelanggaran
        </a>
        <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahJenis">
            <i class="fa-solid fa-plus-circle me-1"></i> Tambah Jenis Pelanggaran
        </button>
    </div>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-gavel"></i> Daftar Tata Tertib &amp; Bobot Poin Pelanggaran</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Pelanggaran Tata Tertib</th>
                    <th>Kategori</th>
                    <th class="text-center">Bobot Poin</th>
                    <th>Sanksi Pembinaan / Ta'zir</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_jenis)): ?>
                <?php
                    $badge_kat = match ($row['kategori']) {
                        'Ringan' => 'bg-info text-dark',
                        'Sedang' => 'bg-warning text-dark',
                        default  => 'bg-danger'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge bg-dark"><?= e($row['kode_pelanggaran']) ?></span></td>
                    <td class="fw-bold text-dark"><?= e($row['nama_pelanggaran']) ?></td>
                    <td><span class="badge <?= $badge_kat ?>"><?= e($row['kategori']) ?></span></td>
                    <td class="text-center"><span class="badge bg-danger px-2 py-1">+<?= (int) $row['poin'] ?> Poin</span></td>
                    <td><?= e($row['sanksi_pembinaan']) ?></td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-jenis"
                                data-jenis='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditJenis">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-jenis"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_pelanggaran']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusJenis">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Jenis Pelanggaran -->
<div class="modal fade" id="modalTambahJenis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="jenis_pelanggaran.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2"></i>Tambah Jenis Pelanggaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode</label>
                            <input type="text" name="kode_pelanggaran" class="form-control" placeholder="PLG-06" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" class="form-select" required>
                                <option value="Ringan">Ringan</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Berat">Berat</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bobot Poin</label>
                            <input type="number" name="poin" class="form-control" value="10" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Pelanggaran</label>
                            <input type="text" name="nama_pelanggaran" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Sanksi Pembinaan / Ta'zir</label>
                            <textarea name="sanksi_pembinaan" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Jenis Pelanggaran -->
<div class="modal fade" id="modalEditJenis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="jenis_pelanggaran.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="ej_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Jenis Pelanggaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode</label>
                            <input type="text" name="kode_pelanggaran" id="ej_kode" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori</label>
                            <select name="kategori" id="ej_kategori" class="form-select" required>
                                <option value="Ringan">Ringan</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Berat">Berat</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bobot Poin</label>
                            <input type="number" name="poin" id="ej_poin" class="form-control" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Pelanggaran</label>
                            <input type="text" name="nama_pelanggaran" id="ej_nama" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Sanksi Pembinaan / Ta'zir</label>
                            <textarea name="sanksi_pembinaan" id="ej_sanksi" class="form-control" rows="2" required></textarea>
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

<!-- Modal Hapus Jenis Pelanggaran -->
<div class="modal fade" id="modalHapusJenis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="jenis_pelanggaran.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hj_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Jenis Pelanggaran</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus jenis pelanggaran:</p>
                    <h6 class="fw-bold text-danger" id="hj_nama"></h6>
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
    document.querySelectorAll('.btn-edit-jenis').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-jenis'));
            document.getElementById('ej_id').value = d.id;
            document.getElementById('ej_kode').value = d.kode_pelanggaran;
            document.getElementById('ej_nama').value = d.nama_pelanggaran;
            document.getElementById('ej_kategori').value = d.kategori;
            document.getElementById('ej_poin').value = d.poin;
            document.getElementById('ej_sanksi').value = d.sanksi_pembinaan;
        });
    });

    document.querySelectorAll('.btn-hapus-jenis').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hj_id').value = this.getAttribute('data-id');
            document.getElementById('hj_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
