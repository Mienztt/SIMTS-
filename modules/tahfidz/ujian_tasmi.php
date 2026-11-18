<?php
/**
 * FILE: modules/tahfidz/ujian_tasmi.php
 * Modul Ujian 10 Halaman & Tasmi' Al-Qur'an (CRUD + Kalkulasi Otomatis Nilai & Status Lulus)
 * Tabel: ujian_tasmi (id, santri_id, penguji_id, tanggal_ujian, jenis_ujian, juz_diujikan, halaman_awal, halaman_akhir, kesalahan_fathah_lupa, kesalahan_tajwid, nilai_akhir, predikat, status_lulus, catatan_penguji)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Evaluasi 10 Hal & Tasmi\' Kelipatan 5 Juz';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$alert_msg  = '';
$alert_type = 'success';

$daftar_jenis_ujian = [
    'Evaluasi Tertutup 10 Halaman',
    'Ujian 10 Halaman',
    'Tasmi 5 Juz',
    'Tasmi 10 Juz',
    'Tasmi 15 Juz',
    'Tasmi 20 Juz',
    'Tasmi 25 Juz',
    'Tasmi 30 Juz'
];

// Proses CRUD Ujian Tasmi'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    cek_role(['admin', 'pengajar']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id                    = (int) ($_POST['id'] ?? 0);
        $santri_id             = (int) ($_POST['santri_id'] ?? 1);
        $penguji_id            = (int) ($_POST['penguji_id'] ?? 1);
        $tanggal_ujian         = $_POST['tanggal_ujian'] ?: date('Y-m-d');
        $jenis_ujian           = in_array($_POST['jenis_ujian'] ?? '', $daftar_jenis_ujian, true) ? $_POST['jenis_ujian'] : 'Evaluasi Tertutup 10 Halaman';
        $juz_diujikan          = trim($_POST['juz_diujikan'] ?? 'Juz 30');
        $halaman_awal          = (int) ($_POST['halaman_awal'] ?? 582);
        $halaman_akhir         = (int) ($_POST['halaman_akhir'] ?? 591);
        $kesalahan_fathah_lupa = (int) ($_POST['kesalahan_fathah_lupa'] ?? 0);
        $kesalahan_tajwid      = (int) ($_POST['kesalahan_tajwid'] ?? 0);
        $nilai_akhir           = (float) ($_POST['nilai_akhir'] ?? 95.0);
        $predikat              = in_array($_POST['predikat'] ?? '', ['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul', 'Mengulang'], true) ? $_POST['predikat'] : 'Mumtaz';
        $status_lulus          = ($_POST['status_lulus'] ?? 'Lulus') === 'Mengulang' ? 'Mengulang' : 'Lulus';
        $catatan_penguji       = trim($_POST['catatan_penguji'] ?? '');

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO ujian_tasmi (santri_id, penguji_id, tanggal_ujian, jenis_ujian, juz_diujikan, halaman_awal, halaman_akhir, kesalahan_fathah_lupa, kesalahan_tajwid, nilai_akhir, predikat, status_lulus, catatan_penguji) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iisssiiiidsss', $santri_id, $penguji_id, $tanggal_ujian, $jenis_ujian, $juz_diujikan, $halaman_awal, $halaman_akhir, $kesalahan_fathah_lupa, $kesalahan_tajwid, $nilai_akhir, $predikat, $status_lulus, $catatan_penguji);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Data $jenis_ujian berhasil disimpan!";
            } else {
                $alert_msg  = 'Gagal menyimpan ujian tasmi: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE ujian_tasmi SET santri_id=?, penguji_id=?, tanggal_ujian=?, jenis_ujian=?, juz_diujikan=?, halaman_awal=?, halaman_akhir=?, kesalahan_fathah_lupa=?, kesalahan_tajwid=?, nilai_akhir=?, predikat=?, status_lulus=?, catatan_penguji=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iisssiiiidsssi', $santri_id, $penguji_id, $tanggal_ujian, $jenis_ujian, $juz_diujikan, $halaman_awal, $halaman_akhir, $kesalahan_fathah_lupa, $kesalahan_tajwid, $nilai_akhir, $predikat, $status_lulus, $catatan_penguji, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Data $jenis_ujian berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal mengupdate ujian tasmi: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM ujian_tasmi WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data ujian tasmi berhasil dihapus!';
    }
}

// Ambil Daftar Santri & Penguji
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

$preselect_santri = (int) ($_GET['santri_id'] ?? 0);

$where_ortu = ($role === 'orang_tua') ? "WHERE s.id_user_orang_tua = $user_id" : "";

// Query Data Ujian Tasmi
$sql_tasmi = "SELECT ut.*, s.nis, s.nama_santri, p.nama_pengajar AS nama_penguji
              FROM ujian_tasmi ut
              JOIN santri s ON ut.santri_id = s.id
              LEFT JOIN pengajar p ON ut.penguji_id = p.id
              $where_ortu
              ORDER BY ut.tanggal_ujian DESC, ut.id DESC";
$res_tasmi = mysqli_query($conn, $sql_tasmi);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-award me-2"></i>Ujian 10 Halaman &amp; Tasmi' Al-Qur'an</h4>
        <small class="text-muted">Evaluasi mutqin hafalan per 10 halaman serta sertifikasi Tasmi' 1, 5, 10, hingga 30 Juz</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahTasmi">
        <i class="fa-solid fa-plus-circle me-1"></i> Input Nilai Ujian / Tasmi'
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
        <h5><i class="fa-solid fa-scroll"></i> Daftar Hasil Ujian 10 Halaman &amp; Tasmi'</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Ujian</th>
                    <th>Nama Santri</th>
                    <th>Jenis Ujian</th>
                    <th>Juz &amp; Rentang Halaman</th>
                    <th class="text-center">Teguran (Lupa / Tajwid)</th>
                    <th class="text-center">Nilai Akhir</th>
                    <th>Predikat</th>
                    <th>Status Kelulusan</th>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_tasmi)): ?>
                <?php
                    $badge_lulus = $row['status_lulus'] === 'Lulus' ? 'bg-success' : 'bg-danger';
                    $badge_pred  = match ($row['predikat']) {
                        'Mumtaz'        => 'bg-success-subtle text-success border border-success',
                        'Jayyid Jiddan' => 'bg-primary-subtle text-primary border border-primary',
                        'Jayyid'        => 'bg-info-subtle text-info-emphasis',
                        default         => 'bg-warning-subtle text-warning-emphasis'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($row['tanggal_ujian']) ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted">Penguji: <?= e($row['nama_penguji'] ?? '-') ?></small>
                    </td>
                    <td><span class="badge bg-dark"><?= e($row['jenis_ujian']) ?></span></td>
                    <td>
                        <div class="fw-semibold text-success"><?= e($row['juz_diujikan']) ?></div>
                        <small class="text-muted">Hal. <?= (int) $row['halaman_awal'] ?> s/d <?= (int) $row['halaman_akhir'] ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            Fathah: <?= (int) $row['kesalahan_fathah_lupa'] ?> | Tajwid: <?= (int) $row['kesalahan_tajwid'] ?>
                        </span>
                    </td>
                    <td class="text-center"><span class="fs-6 fw-bold text-dark"><?= number_format((float) $row['nilai_akhir'], 1) ?></span></td>
                    <td><span class="badge <?= $badge_pred ?>"><?= e($row['predikat']) ?></span></td>
                    <td>
                        <span class="badge <?= $badge_lulus ?> px-3 py-1">
                            <i class="fa-solid <?= $row['status_lulus'] === 'Lulus' ? 'fa-check-circle' : 'fa-rotate-right' ?> me-1"></i>
                            <?= e($row['status_lulus']) ?>
                        </span>
                        <?php if ($row['catatan_penguji']): ?>
                            <small class="text-muted d-block mt-1"><?= e($row['catatan_penguji']) ?></small>
                        <?php endif; ?>
                    </td>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-tasmi"
                                data-tasmi='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditTasmi">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-tasmi"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusTasmi">
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

<!-- Modal Tambah Ujian Tasmi -->
<div class="modal fade" id="modalTambahTasmi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ujian_tasmi.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-award me-2"></i>Input Nilai Ujian 10 Halaman / Tasmi'</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>" <?= ($preselect_santri === (int) $s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['nis'] . ' - ' . $s['nama_santri']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penguji / Muhaffizh</label>
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
                            <label class="form-label small fw-semibold">Jenis Ujian</label>
                            <select name="jenis_ujian" class="form-select" required>
                                <?php foreach ($daftar_jenis_ujian as $ju): ?>
                                    <option value="<?= e($ju) ?>" <?= $ju === 'Evaluasi Tertutup 10 Halaman' ? 'selected' : '' ?>><?= e($ju) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Juz yang Diujikan</label>
                            <input type="text" name="juz_diujikan" class="form-control" placeholder="Juz 29 (Hal 562-571)" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Halaman Awal</label>
                            <input type="number" name="halaman_awal" class="form-control" value="582" min="1" max="604" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Halaman Akhir</label>
                            <input type="number" name="halaman_akhir" class="form-control" value="591" min="1" max="604" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Salah Fathah / Lupa</label>
                            <input type="number" name="kesalahan_fathah_lupa" id="in_lupa" class="form-control" value="1" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Salah Tajwid</label>
                            <input type="number" name="kesalahan_tajwid" id="in_tajwid" class="form-control" value="1" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nilai Akhir</label>
                            <input type="number" step="0.5" name="nilai_akhir" id="in_nilai" class="form-control" value="95.0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Predikat</label>
                            <select name="predikat" class="form-select">
                                <option value="Mumtaz">Mumtaz</option>
                                <option value="Jayyid Jiddan">Jayyid Jiddan</option>
                                <option value="Jayyid">Jayyid</option>
                                <option value="Maqbul">Maqbul</option>
                                <option value="Mengulang">Mengulang</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Kelulusan</label>
                            <select name="status_lulus" class="form-select">
                                <option value="Lulus">Lulus</option>
                                <option value="Mengulang">Mengulang</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan Penguji</label>
                            <textarea name="catatan_penguji" class="form-control" rows="2" placeholder="Lulus dengan predikat Mumtaz, lanjut ke 10 halaman berikutnya..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Hasil Ujian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Ujian Tasmi -->
<div class="modal fade" id="modalEditTasmi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ujian_tasmi.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="ut_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Hasil Ujian / Tasmi'</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" id="ut_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penguji / Muhaffizh</label>
                            <select name="penguji_id" id="ut_penguji" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Ujian</label>
                            <input type="date" name="tanggal_ujian" id="ut_tgl" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis Ujian</label>
                            <select name="jenis_ujian" id="ut_jenis" class="form-select" required>
                                <?php foreach ($daftar_jenis_ujian as $ju): ?>
                                    <option value="<?= e($ju) ?>"><?= e($ju) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Juz yang Diujikan</label>
                            <input type="text" name="juz_diujikan" id="ut_juz" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Halaman Awal</label>
                            <input type="number" name="halaman_awal" id="ut_h_awal" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Halaman Akhir</label>
                            <input type="number" name="halaman_akhir" id="ut_h_akhir" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Salah Fathah / Lupa</label>
                            <input type="number" name="kesalahan_fathah_lupa" id="ut_lupa" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Salah Tajwid</label>
                            <input type="number" name="kesalahan_tajwid" id="ut_tajwid" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nilai Akhir</label>
                            <input type="number" step="0.5" name="nilai_akhir" id="ut_nilai" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Predikat</label>
                            <select name="predikat" id="ut_predikat" class="form-select">
                                <option value="Mumtaz">Mumtaz</option>
                                <option value="Jayyid Jiddan">Jayyid Jiddan</option>
                                <option value="Jayyid">Jayyid</option>
                                <option value="Maqbul">Maqbul</option>
                                <option value="Mengulang">Mengulang</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Kelulusan</label>
                            <select name="status_lulus" id="ut_status" class="form-select">
                                <option value="Lulus">Lulus</option>
                                <option value="Mengulang">Mengulang</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan Penguji</label>
                            <textarea name="catatan_penguji" id="ut_catatan" class="form-control" rows="2"></textarea>
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

<!-- Modal Hapus Tasmi -->
<div class="modal fade" id="modalHapusTasmi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ujian_tasmi.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hut_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Data Ujian</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus data ujian santri:</p>
                    <h6 class="fw-bold text-danger" id="hut_nama"></h6>
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
    document.querySelectorAll('.btn-edit-tasmi').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-tasmi'));
            document.getElementById('ut_id').value = d.id;
            document.getElementById('ut_santri').value = d.santri_id;
            document.getElementById('ut_penguji').value = d.penguji_id;
            document.getElementById('ut_tgl').value = d.tanggal_ujian;
            document.getElementById('ut_jenis').value = d.jenis_ujian;
            document.getElementById('ut_juz').value = d.juz_diujikan;
            document.getElementById('ut_h_awal').value = d.halaman_awal;
            document.getElementById('ut_h_akhir').value = d.halaman_akhir;
            document.getElementById('ut_lupa').value = d.kesalahan_fathah_lupa;
            document.getElementById('ut_tajwid').value = d.kesalahan_tajwid;
            document.getElementById('ut_nilai').value = d.nilai_akhir;
            document.getElementById('ut_predikat').value = d.predikat;
            document.getElementById('ut_status').value = d.status_lulus;
            document.getElementById('ut_catatan').value = d.catatan_penguji || '';
        });
    });

    document.querySelectorAll('.btn-hapus-tasmi').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hut_id').value = this.getAttribute('data-id');
            document.getElementById('hut_nama').textContent = this.getAttribute('data-nama');
        });
    });

    <?php if ($preselect_santri > 0 && in_array($role, ['admin', 'pengajar'], true)): ?>
    const modalTambah = new bootstrap.Modal(document.getElementById('modalTambahTasmi'));
    modalTambah.show();
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
