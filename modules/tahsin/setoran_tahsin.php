<?php
/**
 * FILE: modules/tahsin/setoran_tahsin.php
 * Modul Setoran Harian Tahsin / Perbaikan Bacaan Jilid Qurtuba & Tajwid (CRUD + DataTables)
 * Tabel: setoran_tahsin (id, santri_id, pengajar_id, tanggal, metode_jilid, halaman, baris, nilai_makhraj, nilai_mad, nilai_ghunnah, predikat, catatan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Setoran Tahsin';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$alert_msg  = '';
$alert_type = 'success';

$daftar_jilid = [
    'Iqra / Pra-Tahsin Dasar',
    'Qurtuba Jilid 1',
    'Qurtuba Jilid 2',
    'Qurtuba Jilid 3',
    'Qurtuba Jilid 4',
    'Qurtuba Jilid 5',
    'Qurtuba Jilid 6',
    'Al-Qur\'an / Gharib',
    'Al-Qur\'an / Tajwid'
];

// Proses CRUD Setoran Tahsin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    cek_role(['admin', 'pengajar']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id            = (int) ($_POST['id'] ?? 0);
        $santri_id     = (int) ($_POST['santri_id'] ?? 1);
        $pengajar_id   = (int) ($_POST['pengajar_id'] ?? 1);
        $tanggal       = $_POST['tanggal'] ?: date('Y-m-d');
        $metode_jilid  = trim($_POST['metode_jilid'] ?? 'Qurtuba Jilid 1');
        $halaman       = trim($_POST['halaman'] ?? '1');
        $baris         = trim($_POST['baris'] ?? '1-15');
        $nilai_makhraj = (int) ($_POST['nilai_makhraj'] ?? 85);
        $nilai_mad     = (int) ($_POST['nilai_mad'] ?? 85);
        $nilai_ghunnah = (int) ($_POST['nilai_ghunnah'] ?? 85);
        $predikat      = in_array($_POST['predikat'] ?? '', ['Lancar', 'Kurang Lancar', 'Mengulang'], true) ? $_POST['predikat'] : 'Lancar';
        $catatan       = trim($_POST['catatan'] ?? '');

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO setoran_tahsin (santri_id, pengajar_id, tanggal, metode_jilid, halaman, baris, nilai_makhraj, nilai_mad, nilai_ghunnah, predikat, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iissssiiiss', $santri_id, $pengajar_id, $tanggal, $metode_jilid, $halaman, $baris, $nilai_makhraj, $nilai_mad, $nilai_ghunnah, $predikat, $catatan);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Setoran tahsin santri berhasil disimpan!';
            } else {
                $alert_msg  = 'Gagal menyimpan setoran tahsin: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE setoran_tahsin SET santri_id=?, pengajar_id=?, tanggal=?, metode_jilid=?, halaman=?, baris=?, nilai_makhraj=?, nilai_mad=?, nilai_ghunnah=?, predikat=?, catatan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iissssiiissi', $santri_id, $pengajar_id, $tanggal, $metode_jilid, $halaman, $baris, $nilai_makhraj, $nilai_mad, $nilai_ghunnah, $predikat, $catatan, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Data setoran tahsin berhasil diperbarui!';
            } else {
                $alert_msg  = 'Gagal mengupdate setoran tahsin: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM setoran_tahsin WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data setoran tahsin berhasil dihapus!';
    }
}

// Ambil Daftar Santri & Pengajar
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
$sql_tahsin = "SELECT st.*, s.nis, s.nama_santri, p.nama_pengajar
               FROM setoran_tahsin st
               JOIN santri s ON st.santri_id = s.id
               LEFT JOIN pengajar p ON st.pengajar_id = p.id
               $where_ortu
               ORDER BY st.tanggal DESC, st.id DESC";
$res_tahsin = mysqli_query($conn, $sql_tahsin);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-spell-check me-2"></i>Setoran Harian Tahsin Tilawah</h4>
        <small class="text-muted">Pencatatan evaluasi bacaan jilid Qurtuba, Makharijul Huruf, Mad, dan Ghunnah</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahTahsin">
        <i class="fa-solid fa-plus-circle me-1"></i> Input Setoran Tahsin
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
        <h5><i class="fa-solid fa-book-open"></i> Riwayat Setoran Tahsin Santri</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Santri</th>
                    <th>Metode / Jilid</th>
                    <th>Hal &amp; Baris</th>
                    <th class="text-center">Makhraj</th>
                    <th class="text-center">Mad</th>
                    <th class="text-center">Ghunnah</th>
                    <th>Predikat</th>
                    <th>Catatan</th>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_tahsin)): ?>
                <?php
                    $badge_pr = match ($row['predikat']) {
                        'Lancar'        => 'bg-success',
                        'Kurang Lancar' => 'bg-warning text-dark',
                        default         => 'bg-danger'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($row['tanggal']) ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted">Pengajar: <?= e($row['nama_pengajar'] ?? '-') ?></small>
                    </td>
                    <td><span class="badge bg-info text-dark"><?= e($row['metode_jilid']) ?></span></td>
                    <td>Hal. <strong><?= e($row['halaman']) ?></strong> (Baris <?= e($row['baris']) ?>)</td>
                    <td class="text-center fw-semibold"><?= (int) $row['nilai_makhraj'] ?></td>
                    <td class="text-center fw-semibold"><?= (int) $row['nilai_mad'] ?></td>
                    <td class="text-center fw-semibold"><?= (int) $row['nilai_ghunnah'] ?></td>
                    <td><span class="badge <?= $badge_pr ?>"><?= e($row['predikat']) ?></span></td>
                    <td><small class="text-muted"><?= e($row['catatan'] ?: '-') ?></small></td>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-tahsin"
                                data-tahsin='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditTahsin">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-tahsin"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusTahsin">
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

<!-- Modal Tambah Setoran Tahsin -->
<div class="modal fade" id="modalTambahTahsin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="setoran_tahsin.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-spell-check me-2"></i>Input Setoran Tahsin Harian</h5>
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
                            <label class="form-label small fw-semibold">Pengajar Tahsin</label>
                            <select name="pengajar_id" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Setoran</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Metode / Jilid</label>
                            <select name="metode_jilid" class="form-select" required>
                                <?php foreach ($daftar_jilid as $jl): ?>
                                    <option value="<?= e($jl) ?>"><?= e($jl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Halaman</label>
                            <input type="text" name="halaman" class="form-control" value="15" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Baris</label>
                            <input type="text" name="baris" class="form-control" value="1-15" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nilai Makhraj</label>
                            <input type="number" name="nilai_makhraj" class="form-control" value="88" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nilai Mad</label>
                            <input type="number" name="nilai_mad" class="form-control" value="86" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nilai Ghunnah</label>
                            <input type="number" name="nilai_ghunnah" class="form-control" value="87" min="0" max="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Predikat</label>
                            <select name="predikat" class="form-select">
                                <option value="Lancar">Lancar</option>
                                <option value="Kurang Lancar">Kurang Lancar</option>
                                <option value="Mengulang">Mengulang</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan Pengajar</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Perkuat dengung ikhfa..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Setoran Tahsin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Tahsin -->
<div class="modal fade" id="modalEditTahsin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="setoran_tahsin.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="eth_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Setoran Tahsin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" id="eth_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Pengajar Tahsin</label>
                            <select name="pengajar_id" id="eth_pengajar" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Setoran</label>
                            <input type="date" name="tanggal" id="eth_tgl" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Metode / Jilid</label>
                            <input type="text" name="metode_jilid" id="eth_jilid" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Halaman</label>
                            <input type="text" name="halaman" id="eth_hal" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Baris</label>
                            <input type="text" name="baris" id="eth_baris" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nilai Makhraj</label>
                            <input type="number" name="nilai_makhraj" id="eth_makhraj" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nilai Mad</label>
                            <input type="number" name="nilai_mad" id="eth_mad" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nilai Ghunnah</label>
                            <input type="number" name="nilai_ghunnah" id="eth_ghunnah" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Predikat</label>
                            <select name="predikat" id="eth_predikat" class="form-select">
                                <option value="Lancar">Lancar</option>
                                <option value="Kurang Lancar">Kurang Lancar</option>
                                <option value="Mengulang">Mengulang</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan Pengajar</label>
                            <textarea name="catatan" id="eth_catatan" class="form-control" rows="2"></textarea>
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

<!-- Modal Hapus Tahsin -->
<div class="modal fade" id="modalHapusTahsin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="setoran_tahsin.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hth_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Setoran Tahsin</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus data setoran tahsin:</p>
                    <h6 class="fw-bold text-danger" id="hth_nama"></h6>
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
    document.querySelectorAll('.btn-edit-tahsin').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-tahsin'));
            document.getElementById('eth_id').value = d.id;
            document.getElementById('eth_santri').value = d.santri_id;
            document.getElementById('eth_pengajar').value = d.pengajar_id;
            document.getElementById('eth_tgl').value = d.tanggal;
            document.getElementById('eth_jilid').value = d.metode_jilid;
            document.getElementById('eth_hal').value = d.halaman;
            document.getElementById('eth_baris').value = d.baris;
            document.getElementById('eth_makhraj').value = d.nilai_makhraj;
            document.getElementById('eth_mad').value = d.nilai_mad;
            document.getElementById('eth_ghunnah').value = d.nilai_ghunnah;
            document.getElementById('eth_predikat').value = d.predikat;
            document.getElementById('eth_catatan').value = d.catatan || '';
        });
    });

    document.querySelectorAll('.btn-hapus-tahsin').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hth_id').value = this.getAttribute('data-id');
            document.getElementById('hth_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
