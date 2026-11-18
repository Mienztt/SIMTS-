<?php
/**
 * FILE: modules/mutabaah/master_mutabaah.php
 * Modul Kelola Master Butir Amalan Mutaba'ah Yaumiyah (CRUD + DataTables)
 * Tabel: master_mutabaah (id, kode_amalan, nama_amalan, kategori, target_harian, poin_amalan, status_aktif)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin', 'pengajar']);

$page_title = 'Master Data Mutaba\'ah Yaumiyah';
$alert_msg  = '';
$alert_type = 'success';

$daftar_kategori = [
    'Ibadah Wajib',
    'Ibadah Sunnah',
    'Tilawah & Dzikir',
    'Adab & Akhlak'
];

// Proses CRUD Master Mutaba'ah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id            = (int) ($_POST['id'] ?? 0);
        $kode_amalan   = trim($_POST['kode_amalan'] ?? '');
        $nama_amalan   = trim($_POST['nama_amalan'] ?? '');
        $kategori      = in_array($_POST['kategori'] ?? '', $daftar_kategori, true) ? $_POST['kategori'] : 'Ibadah Sunnah';
        $target_harian = trim($_POST['target_harian'] ?? 'Dilaksanakan setiap hari');
        $poin_amalan   = max(1, (int) ($_POST['poin_amalan'] ?? 10));
        $status_aktif  = ($_POST['status_aktif'] ?? 'Aktif') === 'Nonaktif' ? 'Nonaktif' : 'Aktif';

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO master_mutabaah (kode_amalan, nama_amalan, kategori, target_harian, poin_amalan, status_aktif) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssssis', $kode_amalan, $nama_amalan, $kategori, $target_harian, $poin_amalan, $status_aktif);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Butir amalan '$nama_amalan' berhasil ditambahkan!";
            } else {
                $alert_msg  = 'Gagal menambah amalan: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE master_mutabaah SET kode_amalan=?, nama_amalan=?, kategori=?, target_harian=?, poin_amalan=?, status_aktif=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssisi', $kode_amalan, $nama_amalan, $kategori, $target_harian, $poin_amalan, $status_aktif, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Butir amalan '$nama_amalan' berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal memperbarui amalan: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM master_mutabaah WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $alert_msg = 'Butir amalan mutaba\'ah berhasil dihapus!';
        } else {
            $alert_msg  = 'Gagal menghapus amalan: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

$res_mutabaah = mysqli_query($conn, "SELECT * FROM master_mutabaah ORDER BY kode_amalan ASC");

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-list-check me-2"></i>Kelola Master Mutaba'ah Yaumiyah</h4>
        <small class="text-muted">Daftar butir ibadah harian santri beserta target dan bobot poin amalan</small>
    </div>
    <div class="d-flex gap-2">
        <a href="mutabaah_santri.php" class="btn btn-gold-siata">
            <i class="fa-solid fa-square-check me-1"></i> Isi Checklist Mutaba'ah Santri
        </a>
        <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahAmalan">
            <i class="fa-solid fa-plus-circle me-1"></i> Tambah Butir Amalan
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
        <h5><i class="fa-solid fa-hands-praying"></i> Daftar Butir Amalan Mutaba'ah Harian</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Amalan / Ibadah Harian</th>
                    <th>Kategori</th>
                    <th>Target Harian</th>
                    <th class="text-center">Bobot Poin</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_mutabaah)): ?>
                <?php
                    $badge_kat = match ($row['kategori']) {
                        'Ibadah Wajib'     => 'bg-success',
                        'Ibadah Sunnah'    => 'bg-primary',
                        'Tilawah & Dzikir' => 'bg-info text-dark',
                        default            => 'bg-warning text-dark'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge bg-dark"><?= e($row['kode_amalan']) ?></span></td>
                    <td class="fw-bold text-dark"><?= e($row['nama_amalan']) ?></td>
                    <td><span class="badge <?= $badge_kat ?>"><?= e($row['kategori']) ?></span></td>
                    <td><?= e($row['target_harian']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success border border-success fw-bold px-2 py-1">
                            +<?= (int) $row['poin_amalan'] ?> Poin
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $row['status_aktif'] === 'Aktif' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= e($row['status_aktif']) ?>
                        </span>
                    </td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-amalan"
                                data-amalan='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditAmalan">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-amalan"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_amalan']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusAmalan">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Amalan -->
<div class="modal fade" id="modalTambahAmalan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="master_mutabaah.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2"></i>Tambah Butir Amalan Mutaba'ah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Amalan</label>
                            <input type="text" name="kode_amalan" class="form-control" placeholder="MTB-08" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Kategori Ibadah</label>
                            <select name="kategori" class="form-select" required>
                                <?php foreach ($daftar_kategori as $k): ?>
                                    <option value="<?= e($k) ?>"><?= e($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Amalan / Ibadah Harian</label>
                            <input type="text" name="nama_amalan" class="form-control" placeholder="Puasa Sunnah Senin & Kamis" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Harian</label>
                            <input type="text" name="target_harian" class="form-control" value="Setiap Hari" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Bobot Poin</label>
                            <input type="number" name="poin_amalan" class="form-control" value="15" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status_aktif" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Amalan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Amalan -->
<div class="modal fade" id="modalEditAmalan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="master_mutabaah.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="em_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Butir Amalan Mutaba'ah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Amalan</label>
                            <input type="text" name="kode_amalan" id="em_kode" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Kategori Ibadah</label>
                            <select name="kategori" id="em_kat" class="form-select" required>
                                <?php foreach ($daftar_kategori as $k): ?>
                                    <option value="<?= e($k) ?>"><?= e($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Amalan / Ibadah Harian</label>
                            <input type="text" name="nama_amalan" id="em_nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Harian</label>
                            <input type="text" name="target_harian" id="em_target" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Bobot Poin</label>
                            <input type="number" name="poin_amalan" id="em_poin" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status_aktif" id="em_status" class="form-select">
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

<!-- Modal Hapus Amalan -->
<div class="modal fade" id="modalHapusAmalan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="master_mutabaah.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hm_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Butir Amalan</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus butir amalan:</p>
                    <h6 class="fw-bold text-danger" id="hm_nama"></h6>
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
    document.querySelectorAll('.btn-edit-amalan').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-amalan'));
            document.getElementById('em_id').value = d.id;
            document.getElementById('em_kode').value = d.kode_amalan;
            document.getElementById('em_nama').value = d.nama_amalan;
            document.getElementById('em_kat').value = d.kategori;
            document.getElementById('em_target').value = d.target_harian;
            document.getElementById('em_poin').value = d.poin_amalan;
            document.getElementById('em_status').value = d.status_aktif;
        });
    });

    document.querySelectorAll('.btn-hapus-amalan').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hm_id').value = this.getAttribute('data-id');
            document.getElementById('hm_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
