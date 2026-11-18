<?php
/**
 * FILE: modules/tahsin/ujian_tahsin.php
 * Modul Ujian Kenaikan Jilid Tahsin (CRUD + Kalkulasi Otomatis Nilai Rata-rata & Sinkronisasi Jilid Santri)
 * Tabel: ujian_tahsin (id, santri_id, penguji_id, tanggal_ujian, jilid_asal, jilid_tujuan, nilai_makharijul_huruf, nilai_shifatul_huruf, nilai_ahkamul_huruf, nilai_mad_waqaf, nilai_rata_rata, keputusan, catatan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Ujian Kenaikan Jilid Tahsin';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$alert_msg  = '';
$alert_type = 'success';

// Proses CRUD Ujian Kenaikan Jilid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    cek_role(['admin', 'pengajar']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id                     = (int) ($_POST['id'] ?? 0);
        $santri_id              = (int) ($_POST['santri_id'] ?? 1);
        $penguji_id             = (int) ($_POST['penguji_id'] ?? 1);
        $tanggal_ujian          = $_POST['tanggal_ujian'] ?: date('Y-m-d');
        $jilid_asal             = trim($_POST['jilid_asal'] ?? 'Qurtuba Jilid 5');
        $jilid_tujuan           = trim($_POST['jilid_tujuan'] ?? 'Qurtuba Jilid 6');
        $nilai_makharijul_huruf = (int) ($_POST['nilai_makharijul_huruf'] ?? 85);
        $nilai_shifatul_huruf   = (int) ($_POST['nilai_shifatul_huruf'] ?? 85);
        $nilai_ahkamul_huruf    = (int) ($_POST['nilai_ahkamul_huruf'] ?? 85);
        $nilai_mad_waqaf        = (int) ($_POST['nilai_mad_waqaf'] ?? 85);
        $nilai_rata_rata        = round(($nilai_makharijul_huruf + $nilai_shifatul_huruf + $nilai_ahkamul_huruf + $nilai_mad_waqaf) / 4, 2);
        $keputusan              = ($_POST['keputusan'] ?? 'Naik Jilid') === 'Belum Naik / Mengulang' ? 'Belum Naik / Mengulang' : 'Naik Jilid';
        $catatan                = trim($_POST['catatan'] ?? '');

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO ujian_tahsin (santri_id, penguji_id, tanggal_ujian, jilid_asal, jilid_tujuan, nilai_makharijul_huruf, nilai_shifatul_huruf, nilai_ahkamul_huruf, nilai_mad_waqaf, nilai_rata_rata, keputusan, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iisssiiiidss', $santri_id, $penguji_id, $tanggal_ujian, $jilid_asal, $jilid_tujuan, $nilai_makharijul_huruf, $nilai_shifatul_huruf, $nilai_ahkamul_huruf, $nilai_mad_waqaf, $nilai_rata_rata, $keputusan, $catatan);
            if (mysqli_stmt_execute($stmt)) {
                // Jika keputusan Naik Jilid, perbarui jilid_tahsin di tabel santri secara otomatis
                if ($keputusan === 'Naik Jilid') {
                    $up_s = mysqli_prepare($conn, "UPDATE santri SET jilid_tahsin=? WHERE id=?");
                    mysqli_stmt_bind_param($up_s, 'si', $jilid_tujuan, $santri_id);
                    mysqli_stmt_execute($up_s);
                    mysqli_stmt_close($up_s);
                }
                $alert_msg = "Data ujian kenaikan jilid berhasil disimpan! Nilai rata-rata: $nilai_rata_rata ($keputusan).";
            } else {
                $alert_msg  = 'Gagal menyimpan ujian tahsin: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE ujian_tahsin SET santri_id=?, penguji_id=?, tanggal_ujian=?, jilid_asal=?, jilid_tujuan=?, nilai_makharijul_huruf=?, nilai_shifatul_huruf=?, nilai_ahkamul_huruf=?, nilai_mad_waqaf=?, nilai_rata_rata=?, keputusan=?, catatan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iisssiiiidssi', $santri_id, $penguji_id, $tanggal_ujian, $jilid_asal, $jilid_tujuan, $nilai_makharijul_huruf, $nilai_shifatul_huruf, $nilai_ahkamul_huruf, $nilai_mad_waqaf, $nilai_rata_rata, $keputusan, $catatan, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Data ujian kenaikan jilid berhasil diperbarui!';
            } else {
                $alert_msg  = 'Gagal memperbarui ujian tahsin: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM ujian_tahsin WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data ujian kenaikan jilid berhasil dihapus!';
    }
}

$list_santri = [];
$res_s = mysqli_query($conn, "SELECT id, nis, nama_santri, jilid_tahsin FROM santri WHERE status_santri='Aktif' ORDER BY nama_santri ASC");
while ($r = mysqli_fetch_assoc($res_s)) {
    $list_santri[] = $r;
}

$list_pengajar = [];
$res_p = mysqli_query($conn, "SELECT id, nama_pengajar FROM pengajar WHERE status='Aktif' ORDER BY nama_pengajar ASC");
while ($r = mysqli_fetch_assoc($res_p)) {
    $list_pengajar[] = $r;
}

$where_ortu = ($role === 'orang_tua') ? "WHERE s.id_user_orang_tua = $user_id" : "";
$sql_ujian = "SELECT ut.*, s.nis, s.nama_santri, p.nama_pengajar AS nama_penguji
              FROM ujian_tahsin ut
              JOIN santri s ON ut.santri_id = s.id
              LEFT JOIN pengajar p ON ut.penguji_id = p.id
              $where_ortu
              ORDER BY ut.tanggal_ujian DESC, ut.id DESC";
$res_ujian = mysqli_query($conn, $sql_ujian);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-graduation-cap me-2"></i>Ujian Kenaikan Jilid Tahsin</h4>
        <small class="text-muted">Penilaian komprehensif Makharijul Huruf, Shifatul Huruf, Ahkamul Huruf, serta Mad &amp; Waqaf</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahUjianTahsin">
        <i class="fa-solid fa-plus-circle me-1"></i> Input Ujian Kenaikan Jilid
    </button>
    <?php endif; ?>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-award"></i> Daftar Hasil Ujian Kenaikan Jilid Santri</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Ujian</th>
                    <th>Nama Santri</th>
                    <th>Jilid Asal &rarr; Tujuan</th>
                    <th class="text-center">Makhraj</th>
                    <th class="text-center">Shifat</th>
                    <th class="text-center">Ahkam</th>
                    <th class="text-center">Mad/Waqaf</th>
                    <th class="text-center">Rata-Rata</th>
                    <th>Keputusan</th>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_ujian)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($row['tanggal_ujian']) ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted">Penguji: <?= e($row['nama_penguji'] ?? '-') ?></small>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= e($row['jilid_asal']) ?></span>
                        <i class="fa-solid fa-arrow-right mx-1 text-muted"></i>
                        <span class="badge bg-primary"><?= e($row['jilid_tujuan']) ?></span>
                    </td>
                    <td class="text-center"><?= (int) $row['nilai_makharijul_huruf'] ?></td>
                    <td class="text-center"><?= (int) $row['nilai_shifatul_huruf'] ?></td>
                    <td class="text-center"><?= (int) $row['nilai_ahkamul_huruf'] ?></td>
                    <td class="text-center"><?= (int) $row['nilai_mad_waqaf'] ?></td>
                    <td class="text-center"><span class="fw-bold text-dark"><?= number_format((float) $row['nilai_rata_rata'], 2) ?></span></td>
                    <td>
                        <span class="badge <?= $row['keputusan'] === 'Naik Jilid' ? 'bg-success' : 'bg-danger' ?>">
                            <?= e($row['keputusan']) ?>
                        </span>
                        <?php if ($row['catatan']): ?>
                            <small class="text-muted d-block mt-1"><?= e($row['catatan']) ?></small>
                        <?php endif; ?>
                    </td>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-ujtahsin"
                                data-ujtahsin='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditUjianTahsin">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-ujtahsin"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusUjianTahsin">
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

<!-- Modal Tambah Ujian Tahsin -->
<div class="modal fade" id="modalTambahUjianTahsin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ujian_tahsin.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-graduation-cap me-2"></i>Input Nilai Ujian Kenaikan Jilid</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri'] . ' (' . $s['jilid_tahsin'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penguji Tahsin</label>
                            <select name="penguji_id" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Ujian</label>
                            <input type="date" name="tanggal_ujian" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jilid Asal</label>
                            <input type="text" name="jilid_asal" class="form-control" value="Qurtuba Jilid 5" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jilid Tujuan</label>
                            <input type="text" name="jilid_tujuan" class="form-control" value="Qurtuba Jilid 6" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Makharijul Huruf</label>
                            <input type="number" name="nilai_makharijul_huruf" class="form-control" value="88" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Shifatul Huruf</label>
                            <input type="number" name="nilai_shifatul_huruf" class="form-control" value="86" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ahkamul Huruf</label>
                            <input type="number" name="nilai_ahkamul_huruf" class="form-control" value="87" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Mad &amp; Waqaf</label>
                            <input type="number" name="nilai_mad_waqaf" class="form-control" value="90" min="0" max="100" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Keputusan</label>
                            <select name="keputusan" class="form-select">
                                <option value="Naik Jilid">Naik Jilid</option>
                                <option value="Belum Naik / Mengulang">Belum Naik / Mengulang</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Catatan Penguji</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Selamat naik ke jilid berikutnya...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Nilai Ujian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Ujian Tahsin -->
<div class="modal fade" id="modalEditUjianTahsin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ujian_tahsin.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="eut_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Ujian Kenaikan Jilid</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" id="eut_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penguji Tahsin</label>
                            <select name="penguji_id" id="eut_penguji" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Ujian</label>
                            <input type="date" name="tanggal_ujian" id="eut_tgl" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jilid Asal</label>
                            <input type="text" name="jilid_asal" id="eut_asal" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jilid Tujuan</label>
                            <input type="text" name="jilid_tujuan" id="eut_tujuan" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Makharijul Huruf</label>
                            <input type="number" name="nilai_makharijul_huruf" id="eut_n1" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Shifatul Huruf</label>
                            <input type="number" name="nilai_shifatul_huruf" id="eut_n2" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ahkamul Huruf</label>
                            <input type="number" name="nilai_ahkamul_huruf" id="eut_n3" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Mad &amp; Waqaf</label>
                            <input type="number" name="nilai_mad_waqaf" id="eut_n4" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Keputusan</label>
                            <select name="keputusan" id="eut_keputusan" class="form-select">
                                <option value="Naik Jilid">Naik Jilid</option>
                                <option value="Belum Naik / Mengulang">Belum Naik / Mengulang</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Catatan Penguji</label>
                            <input type="text" name="catatan" id="eut_catatan" class="form-control">
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

<!-- Modal Hapus Ujian Tahsin -->
<div class="modal fade" id="modalHapusUjianTahsin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ujian_tahsin.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hut2_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Data</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus data ujian kenaikan jilid:</p>
                    <h6 class="fw-bold text-danger" id="hut2_nama"></h6>
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
    document.querySelectorAll('.btn-edit-ujtahsin').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-ujtahsin'));
            document.getElementById('eut_id').value = d.id;
            document.getElementById('eut_santri').value = d.santri_id;
            document.getElementById('eut_penguji').value = d.penguji_id;
            document.getElementById('eut_tgl').value = d.tanggal_ujian;
            document.getElementById('eut_asal').value = d.jilid_asal;
            document.getElementById('eut_tujuan').value = d.jilid_tujuan;
            document.getElementById('eut_n1').value = d.nilai_makharijul_huruf;
            document.getElementById('eut_n2').value = d.nilai_shifatul_huruf;
            document.getElementById('eut_n3').value = d.nilai_ahkamul_huruf;
            document.getElementById('eut_n4').value = d.nilai_mad_waqaf;
            document.getElementById('eut_keputusan').value = d.keputusan;
            document.getElementById('eut_catatan').value = d.catatan || '';
        });
    });

    document.querySelectorAll('.btn-hapus-ujtahsin').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hut2_id').value = this.getAttribute('data-id');
            document.getElementById('hut2_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
