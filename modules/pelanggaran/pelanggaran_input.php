<?php
/**
 * FILE: modules/pelanggaran/pelanggaran_input.php
 * Modul Input Kejadian Pelanggaran & Riwayat Kedisiplinan Santri (Auto-Fill Poin & Sanksi)
 * Tabel: pelanggaran_santri (id, santri_id, jenis_pelanggaran_id, pelapor_id, tanggal, kronologi, poin_saat_itu, tindakan_pembinaan, status_penanganan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin', 'pengajar']);

$page_title = 'Input Pelanggaran Santri';
$alert_msg  = '';
$alert_type = 'success';

// Proses CRUD Pelanggaran Santri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id                   = (int) ($_POST['id'] ?? 0);
        $santri_id            = (int) ($_POST['santri_id'] ?? 1);
        $jenis_pelanggaran_id = (int) ($_POST['jenis_pelanggaran_id'] ?? 1);
        $pelapor_id           = (int) ($_POST['pelapor_id'] ?? 1);
        $tanggal              = $_POST['tanggal'] ?: date('Y-m-d');
        $kronologi            = trim($_POST['kronologi'] ?? '');
        $poin_saat_itu        = (int) ($_POST['poin_saat_itu'] ?? 5);
        $tindakan_pembinaan   = trim($_POST['tindakan_pembinaan'] ?? '');
        $status_penanganan    = in_array($_POST['status_penanganan'] ?? '', ['Proses', 'Selesai', 'Panggilan Orang Tua Santri'], true) ? $_POST['status_penanganan'] : 'Selesai';

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO pelanggaran_santri (santri_id, jenis_pelanggaran_id, pelapor_id, tanggal, kronologi, poin_saat_itu, tindakan_pembinaan, status_penanganan) VALUES (?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iiississ', $santri_id, $jenis_pelanggaran_id, $pelapor_id, $tanggal, $kronologi, $poin_saat_itu, $tindakan_pembinaan, $status_penanganan);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Catatan kejadian pelanggaran santri berhasil disimpan!';
            } else {
                $alert_msg  = 'Gagal menyimpan pelanggaran: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE pelanggaran_santri SET santri_id=?, jenis_pelanggaran_id=?, pelapor_id=?, tanggal=?, kronologi=?, poin_saat_itu=?, tindakan_pembinaan=?, status_penanganan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iiississi', $santri_id, $jenis_pelanggaran_id, $pelapor_id, $tanggal, $kronologi, $poin_saat_itu, $tindakan_pembinaan, $status_penanganan, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Data pelanggaran santri berhasil diperbarui!';
            } else {
                $alert_msg  = 'Gagal memperbarui pelanggaran: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM pelanggaran_santri WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data pelanggaran santri berhasil dihapus!';
    }
}

// Ambil Daftar Santri, Pengajar & Master Jenis Pelanggaran
$list_santri = [];
$res_s = mysqli_query($conn, "SELECT id, nis, nama_santri FROM santri WHERE status_santri='Aktif' ORDER BY nama_santri ASC");
while ($r = mysqli_fetch_assoc($res_s)) {
    $list_santri[] = $r;
}

$list_pengajar = [];
$res_p = mysqli_query($conn, "SELECT id, nama_pengajar FROM pengajar WHERE status='Aktif' ORDER BY nama_pengajar ASC");
while ($r = mysqli_fetch_assoc($res_p)) {
    $list_pengajar[] = $r;
}

$list_jenis = [];
$res_j = mysqli_query($conn, "SELECT * FROM jenis_pelanggaran ORDER BY kode_pelanggaran ASC");
while ($r = mysqli_fetch_assoc($res_j)) {
    $list_jenis[] = $r;
}

$sql_riwayat = "SELECT ps.*, s.nis, s.nama_santri, jp.kode_pelanggaran, jp.nama_pelanggaran, jp.kategori, p.nama_pengajar AS nama_pelapor
                FROM pelanggaran_santri ps
                JOIN santri s ON ps.santri_id = s.id
                JOIN jenis_pelanggaran jp ON ps.jenis_pelanggaran_id = jp.id
                LEFT JOIN pengajar p ON ps.pelapor_id = p.id
                ORDER BY ps.tanggal DESC, ps.id DESC";
$res_riwayat = mysqli_query($conn, $sql_riwayat);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Input &amp; Riwayat Pelanggaran Santri</h4>
        <small class="text-muted">Pencatatan kejadian pelanggaran tata tertib dan tindak lanjut pembinaan</small>
    </div>
    <div class="d-flex gap-2">
        <a href="pelanggaran_rekap.php" class="btn btn-gold-siata">
            <i class="fa-solid fa-shield-halved me-1"></i> Lihat Rekap Akumulasi Poin
        </a>
        <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahPelanggaran">
            <i class="fa-solid fa-plus-circle me-1"></i> Input Pelanggaran Baru
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
        <h5><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Kejadian Pelanggaran Santri</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Santri</th>
                    <th>Jenis Pelanggaran</th>
                    <th>Kronologi &amp; Pembinaan</th>
                    <th class="text-center">Poin</th>
                    <th>Status Penanganan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_riwayat)): ?>
                <?php
                    $badge_st = match ($row['status_penanganan']) {
                        'Selesai'        => 'bg-success',
                        'Proses'         => 'bg-warning text-dark',
                        default          => 'bg-danger'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($row['tanggal']) ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted">Pelapor: <?= e($row['nama_pelapor'] ?? '-') ?></small>
                    </td>
                    <td>
                        <span class="badge bg-dark"><?= e($row['kode_pelanggaran']) ?></span>
                        <div class="fw-semibold text-dark mt-1"><?= e($row['nama_pelanggaran']) ?></div>
                    </td>
                    <td>
                        <div class="small"><strong>Kronologi:</strong> <?= e($row['kronologi'] ?: '-') ?></div>
                        <div class="small text-success"><strong>Ta'zir:</strong> <?= e($row['tindakan_pembinaan'] ?: '-') ?></div>
                    </td>
                    <td class="text-center"><span class="badge bg-danger px-2 py-1">+<?= (int) $row['poin_saat_itu'] ?> Poin</span></td>
                    <td><span class="badge <?= $badge_st ?>"><?= e($row['status_penanganan']) ?></span></td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-plg"
                                data-plg='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditPelanggaran">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-plg"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusPelanggaran">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pelanggaran (Auto-fill Poin & Sanksi dari Master) -->
<div class="modal fade" id="modalTambahPelanggaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pelanggaran_input.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Input Kejadian Pelanggaran Santri</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Asatidz Pelapor</label>
                            <select name="pelapor_id" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Pilih Jenis Pelanggaran (Otomatis Mengisi Poin &amp; Sanksi)</label>
                            <select name="jenis_pelanggaran_id" id="add_jenis_id" class="form-select" required>
                                <?php foreach ($list_jenis as $j): ?>
                                    <option value="<?= (int) $j['id'] ?>"
                                            data-poin="<?= (int) $j['poin'] ?>"
                                            data-sanksi="<?= e($j['sanksi_pembinaan']) ?>">
                                        [<?= e($j['kode_pelanggaran']) ?>] <?= e($j['nama_pelanggaran']) ?> (+<?= (int) $j['poin'] ?> Poin)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Kejadian</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Poin Saat Itu</label>
                            <input type="number" name="poin_saat_itu" id="add_poin" class="form-control fw-bold text-danger"
                                   value="<?= (int) ($list_jenis[0]['poin'] ?? 5) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Tindakan Pembinaan / Sanksi</label>
                            <input type="text" name="tindakan_pembinaan" id="add_tindakan" class="form-control"
                                   value="<?= e($list_jenis[0]['sanksi_pembinaan'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Penanganan</label>
                            <select name="status_penanganan" class="form-select">
                                <option value="Selesai">Selesai</option>
                                <option value="Proses">Proses</option>
                                <option value="Panggilan Orang Tua Santri">Panggilan Orang Tua Santri</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kronologi Kejadian</label>
                            <textarea name="kronologi" class="form-control" rows="2" placeholder="Uraikan kronologi singkat kejadian..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Pelanggaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pelanggaran -->
<div class="modal fade" id="modalEditPelanggaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pelanggaran_input.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="ep_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Catatan Pelanggaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" id="ep_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Asatidz Pelapor</label>
                            <select name="pelapor_id" id="ep_pelapor" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Jenis Pelanggaran</label>
                            <select name="jenis_pelanggaran_id" id="ep_jenis" class="form-select" required>
                                <?php foreach ($list_jenis as $j): ?>
                                    <option value="<?= (int) $j['id'] ?>"
                                            data-poin="<?= (int) $j['poin'] ?>"
                                            data-sanksi="<?= e($j['sanksi_pembinaan']) ?>">
                                        [<?= e($j['kode_pelanggaran']) ?>] <?= e($j['nama_pelanggaran']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Kejadian</label>
                            <input type="date" name="tanggal" id="ep_tanggal" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Poin Saat Itu</label>
                            <input type="number" name="poin_saat_itu" id="ep_poin" class="form-control fw-bold text-danger" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Tindakan Pembinaan</label>
                            <input type="text" name="tindakan_pembinaan" id="ep_tindakan" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Penanganan</label>
                            <select name="status_penanganan" id="ep_status" class="form-select">
                                <option value="Selesai">Selesai</option>
                                <option value="Proses">Proses</option>
                                <option value="Panggilan Orang Tua Santri">Panggilan Orang Tua Santri</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Kronologi Kejadian</label>
                            <textarea name="kronologi" id="ep_kronologi" class="form-control" rows="2" required></textarea>
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

<!-- Modal Hapus Pelanggaran -->
<div class="modal fade" id="modalHapusPelanggaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pelanggaran_input.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hpl_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Catatan Pelanggaran</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus catatan pelanggaran santri:</p>
                    <h6 class="fw-bold text-danger" id="hpl_nama"></h6>
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
    // Auto-fill Poin & Sanksi saat memilih Jenis Pelanggaran di Modal Tambah
    const selAdd = document.getElementById('add_jenis_id');
    if (selAdd) {
        selAdd.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('add_poin').value = opt.getAttribute('data-poin') || 5;
            document.getElementById('add_tindakan').value = opt.getAttribute('data-sanksi') || '';
        });
    }

    // Auto-fill Poin & Sanksi di Modal Edit
    const selEdit = document.getElementById('ep_jenis');
    if (selEdit) {
        selEdit.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('ep_poin').value = opt.getAttribute('data-poin') || 5;
            document.getElementById('ep_tindakan').value = opt.getAttribute('data-sanksi') || '';
        });
    }

    document.querySelectorAll('.btn-edit-plg').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-plg'));
            document.getElementById('ep_id').value = d.id;
            document.getElementById('ep_santri').value = d.santri_id;
            document.getElementById('ep_pelapor').value = d.pelapor_id;
            document.getElementById('ep_jenis').value = d.jenis_pelanggaran_id;
            document.getElementById('ep_tanggal').value = d.tanggal;
            document.getElementById('ep_poin').value = d.poin_saat_itu;
            document.getElementById('ep_tindakan').value = d.tindakan_pembinaan || '';
            document.getElementById('ep_status').value = d.status_penanganan;
            document.getElementById('ep_kronologi').value = d.kronologi || '';
        });
    });

    document.querySelectorAll('.btn-hapus-plg').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hpl_id').value = this.getAttribute('data-id');
            document.getElementById('hpl_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
