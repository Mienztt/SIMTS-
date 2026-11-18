<?php
/**
 * FILE: modules/tahfidz/target_tahfidz.php
 * Modul Penetapan & Monitoring Target Tahfidz Santri (CRUD + Visual Progress Bar)
 * Tabel: target_tahfidz (id, santri_id, semester, tahun_ajaran, juz_target, target_halaman, tercapai_halaman, persentase_capaian, batas_waktu, status_target, keterangan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Target Tahfidz Santri';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$alert_msg  = '';
$alert_type = 'success';

// Proses CRUD Target Tahfidz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    cek_role(['admin', 'pengajar']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id               = (int) ($_POST['id'] ?? 0);
        $santri_id        = (int) ($_POST['santri_id'] ?? 1);
        $semester         = ($_POST['semester'] ?? 'Ganjil') === 'Genap' ? 'Genap' : 'Ganjil';
        $tahun_ajaran     = trim($_POST['tahun_ajaran'] ?? '2026/2027');
        $juz_target       = trim($_POST['juz_target'] ?? 'Juz 30');
        $target_halaman   = max(1, (int) ($_POST['target_halaman'] ?? 20));
        $tercapai_halaman = max(0, (int) ($_POST['tercapai_halaman'] ?? 0));
        $batas_waktu      = $_POST['batas_waktu'] ?: date('Y-12-20');
        $keterangan       = trim($_POST['keterangan'] ?? '');

        $persentase_capaian = min(100, round(($tercapai_halaman / $target_halaman) * 100, 2));
        if ($persentase_capaian >= 100) {
            $status_target = 'Tercapai';
        } elseif ($persentase_capaian > 0) {
            $status_target = 'Proses';
        } else {
            $status_target = 'Belum Tercapai';
        }

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO target_tahfidz (santri_id, semester, tahun_ajaran, juz_target, target_halaman, tercapai_halaman, persentase_capaian, batas_waktu, status_target, keterangan) VALUES (?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isssiidsss', $santri_id, $semester, $tahun_ajaran, $juz_target, $target_halaman, $tercapai_halaman, $persentase_capaian, $batas_waktu, $status_target, $keterangan);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Target tahfidz santri berhasil ditambahkan!';
            } else {
                $alert_msg  = 'Gagal menyimpan target: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE target_tahfidz SET santri_id=?, semester=?, tahun_ajaran=?, juz_target=?, target_halaman=?, tercapai_halaman=?, persentase_capaian=?, batas_waktu=?, status_target=?, keterangan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'isssiidsssi', $santri_id, $semester, $tahun_ajaran, $juz_target, $target_halaman, $tercapai_halaman, $persentase_capaian, $batas_waktu, $status_target, $keterangan, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Target tahfidz santri berhasil diperbarui!';
            } else {
                $alert_msg  = 'Gagal mengupdate target: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM target_tahfidz WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data target tahfidz berhasil dihapus!';
    }
}

// Ambil Daftar Santri Aktif untuk Modal
$list_santri = [];
$res_s = mysqli_query($conn, "SELECT id, nis, nama_santri FROM santri WHERE status_santri='Aktif' ORDER BY nama_santri ASC");
while ($r = mysqli_fetch_assoc($res_s)) {
    $list_santri[] = $r;
}

$where_ortu = ($role === 'orang_tua') ? "WHERE s.id_user_orang_tua = $user_id" : "";

// Query Data Target Tahfidz
$sql_target = "SELECT t.*, s.nis, s.nama_santri, h.nama_halaqah
               FROM target_tahfidz t
               JOIN santri s ON t.santri_id = s.id
               LEFT JOIN halaqah h ON s.halaqah_id = h.id
               $where_ortu
               ORDER BY t.id DESC";
$res_target = mysqli_query($conn, $sql_target);

// Hitung Ringkasan Capaian
$rata_capaian = 0;
$sql_avg = "SELECT AVG(t.persentase_capaian) FROM target_tahfidz t JOIN santri s ON t.santri_id = s.id $where_ortu";
$res_avg = mysqli_query($conn, $sql_avg);
if ($res_avg && $r_avg = mysqli_fetch_row($res_avg)) {
    $rata_capaian = round((float) $r_avg[0], 1);
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-bullseye me-2"></i>Target Tahfidz &amp; Persentase Capaian</h4>
        <small class="text-muted">Perencanaan target hafalan semester dan pemantauan persentase pencapaian santri</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahTarget">
        <i class="fa-solid fa-plus me-1"></i> Tetapkan Target Baru
    </button>
    <?php endif; ?>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Banner Ringkasan Rata-Rata Pencapaian -->
<div class="card-siata p-3 mb-3 bg-white border-start border-4 border-success">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-line text-success me-2"></i>Rata-Rata Pencapaian Target Hafalan Semester Ini</h6>
            <div class="progress" style="height: 12px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: <?= $rata_capaian ?>%"></div>
            </div>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <span class="fs-3 fw-bold text-success"><?= $rata_capaian ?>%</span>
            <span class="small text-muted d-block">Rata-rata Realisasi Target</span>
        </div>
    </div>
</div>

<!-- Tabel DataTables Target Tahfidz -->
<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-list-ol"></i> Daftar Target Hafalan Santri</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Santri</th>
                    <th>Periode</th>
                    <th>Target Juz</th>
                    <th class="text-center">Target vs Tercapai</th>
                    <th style="width: 210px;">Progres Capaian</th>
                    <th>Batas Waktu</th>
                    <th>Status</th>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_target)): ?>
                <?php
                    $pct = (float) $row['persentase_capaian'];
                    $bar_color = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-info' : 'bg-warning');
                    $badge_st  = match ($row['status_target']) {
                        'Tercapai'       => 'bg-success',
                        'Proses'         => 'bg-primary',
                        default          => 'bg-secondary'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted"><?= e($row['nis']) ?> &bull; <?= e($row['nama_halaqah'] ?? '-') ?></small>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($row['tahun_ajaran']) ?></div>
                        <small class="badge bg-light text-dark border"><?= e($row['semester']) ?></small>
                    </td>
                    <td class="fw-semibold text-success"><?= e($row['juz_target']) ?></td>
                    <td class="text-center">
                        <span class="fw-bold text-dark"><?= (int) $row['tercapai_halaman'] ?></span> / <?= (int) $row['target_halaman'] ?> Hal
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar <?= $bar_color ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%"></div>
                            </div>
                            <span class="small fw-bold"><?= number_format($pct, 0) ?>%</span>
                        </div>
                        <?php if ($row['keterangan']): ?>
                            <small class="text-muted d-block mt-1"><?= e($row['keterangan']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($row['batas_waktu']) ?></td>
                    <td><span class="badge <?= $badge_st ?>"><?= e($row['status_target']) ?></span></td>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-target"
                                data-target='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditTarget">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-target"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusTarget">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Target -->
<div class="modal fade" id="modalTambahTarget" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="target_tahfidz.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-bullseye me-2"></i>Tetapkan Target Tahfidz</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pilih Santri</label>
                            <select name="santri_id" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" class="form-control" value="2026/2027" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Juz</label>
                            <input type="text" name="juz_target" class="form-control" placeholder="Juz 29 & Juz 30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Batas Waktu</label>
                            <input type="date" name="batas_waktu" class="form-control" value="<?= date('Y-12-20') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Halaman</label>
                            <input type="number" name="target_halaman" class="form-control" value="20" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tercapai Saat Ini (Halaman)</label>
                            <input type="number" name="tercapai_halaman" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan / Catatan</label>
                            <textarea name="keterangan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Target -->
<div class="modal fade" id="modalEditTarget" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="target_tahfidz.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="et_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Update Target &amp; Capaian</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pilih Santri</label>
                            <select name="santri_id" id="et_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" id="et_ta" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" id="et_sem" class="form-select">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Juz</label>
                            <input type="text" name="juz_target" id="et_juz" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Batas Waktu</label>
                            <input type="date" name="batas_waktu" id="et_batas" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Halaman</label>
                            <input type="number" name="target_halaman" id="et_target_hal" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tercapai (Halaman)</label>
                            <input type="number" name="tercapai_halaman" id="et_tercapai_hal" class="form-control" min="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Keterangan</label>
                            <textarea name="keterangan" id="et_ket" class="form-control" rows="2"></textarea>
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

<!-- Modal Hapus Target -->
<div class="modal fade" id="modalHapusTarget" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="target_tahfidz.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="ht_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Target</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus target tahfidz santri:</p>
                    <h6 class="fw-bold text-danger" id="ht_nama"></h6>
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
    document.querySelectorAll('.btn-edit-target').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-target'));
            document.getElementById('et_id').value = d.id;
            document.getElementById('et_santri').value = d.santri_id;
            document.getElementById('et_ta').value = d.tahun_ajaran;
            document.getElementById('et_sem').value = d.semester;
            document.getElementById('et_juz').value = d.juz_target;
            document.getElementById('et_batas').value = d.batas_waktu;
            document.getElementById('et_target_hal').value = d.target_halaman;
            document.getElementById('et_tercapai_hal').value = d.tercapai_halaman;
            document.getElementById('et_ket').value = d.keterangan || '';
        });
    });

    document.querySelectorAll('.btn-hapus-target').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('ht_id').value = this.getAttribute('data-id');
            document.getElementById('ht_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
