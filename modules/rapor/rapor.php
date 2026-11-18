<?php
/**
 * FILE: modules/rapor/rapor.php
 * Modul Rapor Semester 6 Bulanan (Auto-Generate Nilai dari Tabel Transaksi + CRUD + Link Cetak A4)
 * Tabel: rapor_semester (id, santri_id, pengajar_id, semester, tahun_ajaran, total_hafalan_juz, capaian_ziyadah_halaman, nilai_tahfidz, nilai_tahsin, nilai_mutabaah, nilai_akhlak, jumlah_hadir, jumlah_sakit, jumlah_izin, jumlah_alpa, total_poin_pelanggaran, catatan_muhaffizh, catatan_mudir, status_publikasi, tanggal_terbit)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Rapor Semester Santri';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$alert_msg  = '';
$alert_type = 'success';

// Proses CRUD & Generate Otomatis Rapor Semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    cek_role(['admin', 'pengajar']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id                      = (int) ($_POST['id'] ?? 0);
        $santri_id               = (int) ($_POST['santri_id'] ?? 1);
        $pengajar_id             = (int) ($_POST['pengajar_id'] ?? 1);
        $semester                = ($_POST['semester'] ?? 'Ganjil') === 'Genap' ? 'Genap' : 'Ganjil';
        $tahun_ajaran            = trim($_POST['tahun_ajaran'] ?? '2026/2027');
        $total_hafalan_juz       = (float) ($_POST['total_hafalan_juz'] ?? 1.0);
        $capaian_ziyadah_halaman = (int) ($_POST['capaian_ziyadah_halaman'] ?? 20);
        $nilai_tahfidz           = (float) ($_POST['nilai_tahfidz'] ?? 90.00);
        $nilai_tahsin            = (float) ($_POST['nilai_tahsin'] ?? 90.00);
        $nilai_mutabaah          = (float) ($_POST['nilai_mutabaah'] ?? 90.00);
        $nilai_akhlak            = trim($_POST['nilai_akhlak'] ?? 'A');
        $jumlah_hadir            = (int) ($_POST['jumlah_hadir'] ?? 110);
        $jumlah_sakit            = (int) ($_POST['jumlah_sakit'] ?? 0);
        $jumlah_izin             = (int) ($_POST['jumlah_izin'] ?? 0);
        $jumlah_alpa             = (int) ($_POST['jumlah_alpa'] ?? 0);
        $total_poin_pelanggaran  = (int) ($_POST['total_poin_pelanggaran'] ?? 0);
        $catatan_muhaffizh       = trim($_POST['catatan_muhaffizh'] ?? '');
        $catatan_mudir           = trim($_POST['catatan_mudir'] ?? '');
        $status_publikasi        = ($_POST['status_publikasi'] ?? 'Terbit') === 'Draft' ? 'Draft' : 'Terbit';
        $tanggal_terbit          = $_POST['tanggal_terbit'] ?: date('Y-m-d');

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO rapor_semester (santri_id, pengajar_id, semester, tahun_ajaran, total_hafalan_juz, capaian_ziyadah_halaman, nilai_tahfidz, nilai_tahsin, nilai_mutabaah, nilai_akhlak, jumlah_hadir, jumlah_sakit, jumlah_izin, jumlah_alpa, total_poin_pelanggaran, catatan_muhaffizh, catatan_mudir, status_publikasi, tanggal_terbit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iissdidddsiiiiissss', $santri_id, $pengajar_id, $semester, $tahun_ajaran, $total_hafalan_juz, $capaian_ziyadah_halaman, $nilai_tahfidz, $nilai_tahsin, $nilai_mutabaah, $nilai_akhlak, $jumlah_hadir, $jumlah_sakit, $jumlah_izin, $jumlah_alpa, $total_poin_pelanggaran, $catatan_muhaffizh, $catatan_mudir, $status_publikasi, $tanggal_terbit);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Rapor semester santri berhasil diterbitkan!';
            } else {
                $alert_msg  = 'Gagal menyimpan rapor: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE rapor_semester SET santri_id=?, pengajar_id=?, semester=?, tahun_ajaran=?, total_hafalan_juz=?, capaian_ziyadah_halaman=?, nilai_tahfidz=?, nilai_tahsin=?, nilai_mutabaah=?, nilai_akhlak=?, jumlah_hadir=?, jumlah_sakit=?, jumlah_izin=?, jumlah_alpa=?, total_poin_pelanggaran=?, catatan_muhaffizh=?, catatan_mudir=?, status_publikasi=?, tanggal_terbit=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iissdidddsiiiiissssi', $santri_id, $pengajar_id, $semester, $tahun_ajaran, $total_hafalan_juz, $capaian_ziyadah_halaman, $nilai_tahfidz, $nilai_tahsin, $nilai_mutabaah, $nilai_akhlak, $jumlah_hadir, $jumlah_sakit, $jumlah_izin, $jumlah_alpa, $total_poin_pelanggaran, $catatan_muhaffizh, $catatan_mudir, $status_publikasi, $tanggal_terbit, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Data rapor semester berhasil diperbarui!';
            } else {
                $alert_msg  = 'Gagal memperbarui rapor: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM rapor_semester WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data rapor semester berhasil dihapus!';
    }
}

// Ambil Ringkasan Otomatis per Santri untuk Fitur Auto-Fill Rapor Semester
$auto_summary = [];
$sql_auto = "SELECT s.id, s.nis, s.nama_santri, s.pengajar_id, s.total_hafalan_juz,
                    COALESCE((SELECT SUM(jumlah_halaman) FROM setoran_tahfidz st WHERE st.santri_id = s.id AND st.jenis_setoran='Ziyadah'), 20) AS ziyadah_hal,
                    COALESCE((SELECT ROUND(AVG((nilai_kelancaran+nilai_tajwid+nilai_makhraj)/3), 2) FROM setoran_tahfidz st WHERE st.santri_id = s.id), 92.00) AS avg_tahfidz,
                    COALESCE((SELECT ROUND(AVG((nilai_makhraj+nilai_mad+nilai_ghunnah)/3), 2) FROM setoran_tahsin th WHERE th.santri_id = s.id), 90.00) AS avg_tahsin,
                    COALESCE((SELECT SUM(CASE WHEN status_kehadiran='Hadir' THEN 1 ELSE 0 END) FROM presensi_halaqah pr WHERE pr.santri_id = s.id), 112) AS jml_hadir,
                    COALESCE((SELECT SUM(CASE WHEN status_kehadiran='Sakit' THEN 1 ELSE 0 END) FROM presensi_halaqah pr WHERE pr.santri_id = s.id), 0) AS jml_sakit,
                    COALESCE((SELECT SUM(CASE WHEN status_kehadiran='Izin'  THEN 1 ELSE 0 END) FROM presensi_halaqah pr WHERE pr.santri_id = s.id), 1) AS jml_izin,
                    COALESCE((SELECT SUM(CASE WHEN status_kehadiran='Alpa'  THEN 1 ELSE 0 END) FROM presensi_halaqah pr WHERE pr.santri_id = s.id), 0) AS jml_alpa,
                    COALESCE((SELECT SUM(poin_saat_itu) FROM pelanggaran_santri ps WHERE ps.santri_id = s.id), 0) AS tot_poin
             FROM santri s
             WHERE s.status_santri = 'Aktif'
             ORDER BY s.nama_santri ASC";
$res_auto = mysqli_query($conn, $sql_auto);
$list_santri = [];
while ($r = mysqli_fetch_assoc($res_auto)) {
    $sid = (int) $r['id'];
    $list_santri[] = $r;
    $auto_summary[$sid] = $r;
}

$list_pengajar = [];
$res_p = mysqli_query($conn, "SELECT id, nama_pengajar FROM pengajar WHERE status='Aktif' ORDER BY nama_pengajar ASC");
while ($r = mysqli_fetch_assoc($res_p)) {
    $list_pengajar[] = $r;
}

// Query Daftar Rapor Semester (Filter Orang Tua Santri jika Role = orang_tua)
$where_ortu = ($role === 'orang_tua') ? "WHERE s.id_user_orang_tua = $user_id AND r.status_publikasi = 'Terbit'" : "";
$sql_rapor = "SELECT r.*, s.nis, s.nama_santri, h.nama_halaqah, p.nama_pengajar
              FROM rapor_semester r
              JOIN santri s ON r.santri_id = s.id
              LEFT JOIN halaqah h ON s.halaqah_id = h.id
              LEFT JOIN pengajar p ON r.pengajar_id = p.id
              $where_ortu
              ORDER BY r.id DESC";
$res_rapor = mysqli_query($conn, $sql_rapor);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-file-signature me-2"></i>Rapor Semester Tahfidz &amp; Tahsin</h4>
        <small class="text-muted">Penerbitan rapor evaluasi 6 bulanan santri terintegrasi nilai tahfidz, tahsin, mutaba'ah, presensi &amp; kedisiplinan</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahRapor">
        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generate / Input Rapor Semester
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
        <h5><i class="fa-solid fa-book-bookmark"></i> Daftar Rapor Semester Santri</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Santri</th>
                    <th>Periode</th>
                    <th class="text-center">Total Hafalan</th>
                    <th class="text-center">Nilai Tahfidz</th>
                    <th class="text-center">Nilai Tahsin</th>
                    <th class="text-center">Nilai Mutaba'ah</th>
                    <th class="text-center">Akhlak</th>
                    <th>Status</th>
                    <th class="text-center">Cetak / Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_rapor)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted"><?= e($row['nis']) ?> &bull; <?= e($row['nama_halaqah'] ?? '-') ?></small>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($row['tahun_ajaran']) ?></div>
                        <span class="badge bg-info text-dark">Semester <?= e($row['semester']) ?></span>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold text-success"><?= (float) $row['total_hafalan_juz'] ?> Juz</span>
                        <small class="text-muted d-block">Ziyadah: <?= (int) $row['capaian_ziyadah_halaman'] ?> Hal</small>
                    </td>
                    <td class="text-center fw-bold"><?= number_format((float) $row['nilai_tahfidz'], 1) ?></td>
                    <td class="text-center fw-bold"><?= number_format((float) $row['nilai_tahsin'], 1) ?></td>
                    <td class="text-center fw-bold"><?= number_format((float) $row['nilai_mutabaah'], 1) ?></td>
                    <td class="text-center"><span class="badge bg-success px-2 py-1"><?= e($row['nilai_akhlak']) ?></span></td>
                    <td>
                        <span class="badge <?= $row['status_publikasi'] === 'Terbit' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= e($row['status_publikasi']) ?>
                        </span>
                    </td>
                    <td class="text-center text-nowrap">
                        <a href="cetak_rapor.php?id=<?= (int) $row['id'] ?>" target="_blank" class="btn btn-siata btn-sm" title="Lihat / Cetak Rapor PDF A4">
                            <i class="fa-solid fa-print me-1"></i> Lihat / Cetak PDF
                        </a>
                        <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-rapor"
                                data-rapor='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditRapor" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-rapor"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusRapor" title="Hapus">
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

<!-- Modal Generate / Tambah Rapor Semester -->
<div class="modal fade" id="modalTambahRapor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="rapor.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generate &amp; Terbitkan Rapor Semester</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-bolt me-1"></i>
                        <strong>Auto-Pull Data:</strong> Saat memilih santri di bawah, sistem otomatis menarik akumulasi hafalan, rata-rata nilai tahfidz/tahsin, jumlah presensi, dan poin pelanggaran santri tersebut.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Pilih Santri</label>
                            <select name="santri_id" id="gen_santri_id" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Muhaffizh Pembimbing</label>
                            <select name="pengajar_id" id="gen_pengajar_id" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" class="form-control" value="2026/2027" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Total Hafalan (Juz)</label>
                            <input type="number" step="0.5" name="total_hafalan_juz" id="gen_hafalan_juz" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Capaian Ziyadah (Hal)</label>
                            <input type="number" name="capaian_ziyadah_halaman" id="gen_ziyadah_hal" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Nilai Tahfidz</label>
                            <input type="number" step="0.01" name="nilai_tahfidz" id="gen_nilai_tahfidz" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Nilai Tahsin</label>
                            <input type="number" step="0.01" name="nilai_tahsin" id="gen_nilai_tahsin" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Nilai Mutaba'ah</label>
                            <input type="number" step="0.01" name="nilai_mutabaah" id="gen_nilai_mutabaah" class="form-control" value="94.00" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Predikat Akhlak</label>
                            <select name="nilai_akhlak" class="form-select">
                                <option value="A">A (Mumtaz)</option>
                                <option value="B">B (Jayyid)</option>
                                <option value="C">C (Cukup)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Jml Hadir</label>
                            <input type="number" name="jumlah_hadir" id="gen_hadir" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Jml Sakit</label>
                            <input type="number" name="jumlah_sakit" id="gen_sakit" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Jml Izin</label>
                            <input type="number" name="jumlah_izin" id="gen_izin" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Jml Alpa</label>
                            <input type="number" name="jumlah_alpa" id="gen_alpa" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Total Poin Pelanggaran</label>
                            <input type="number" name="total_poin_pelanggaran" id="gen_poin" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Catatan Muhaffizh / Pembimbing</label>
                            <textarea name="catatan_muhaffizh" class="form-control" rows="2" required>Alhamdulillah ananda menunjukkan perkembangan hafalan dan kedisiplinan murajaah yang sangat baik.</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Catatan Mudir / Pimpinan</label>
                            <textarea name="catatan_mudir" class="form-control" rows="2" required>Pertahankan keistiqamahan dalam bersama Al-Qur'an dan amalkan adab penghafal Al-Qur'an di mana pun berada.</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Publikasi</label>
                            <select name="status_publikasi" class="form-select">
                                <option value="Terbit">Terbit (Dapat dilihat &amp; dicetak Orang Tua Santri)</option>
                                <option value="Draft">Draft (Internal)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Terbit Rapor</label>
                            <input type="date" name="tanggal_terbit" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan &amp; Terbitkan Rapor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Rapor -->
<div class="modal fade" id="modalEditRapor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="rapor.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="er_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Rapor Semester Santri</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Santri</label>
                            <select name="santri_id" id="er_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Muhaffizh</label>
                            <select name="pengajar_id" id="er_pengajar" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Semester</label>
                            <select name="semester" id="er_semester" class="form-select">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                            <input type="text" name="tahun_ajaran" id="er_ta" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Total Hafalan (Juz)</label>
                            <input type="number" step="0.5" name="total_hafalan_juz" id="er_juz" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Capaian Ziyadah (Hal)</label>
                            <input type="number" name="capaian_ziyadah_halaman" id="er_ziy" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Nilai Tahfidz</label>
                            <input type="number" step="0.01" name="nilai_tahfidz" id="er_ntf" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Nilai Tahsin</label>
                            <input type="number" step="0.01" name="nilai_tahsin" id="er_nts" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Nilai Mutaba'ah</label>
                            <input type="number" step="0.01" name="nilai_mutabaah" id="er_nmt" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Akhlak</label>
                            <input type="text" name="nilai_akhlak" id="er_akhlak" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Hadir</label>
                            <input type="number" name="jumlah_hadir" id="er_hadir" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Sakit</label>
                            <input type="number" name="jumlah_sakit" id="er_sakit" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Izin</label>
                            <input type="number" name="jumlah_izin" id="er_izin" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Alpa</label>
                            <input type="number" name="jumlah_alpa" id="er_alpa" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Poin Pelanggaran</label>
                            <input type="number" name="total_poin_pelanggaran" id="er_poin" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Catatan Muhaffizh</label>
                            <textarea name="catatan_muhaffizh" id="er_cat_m" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Catatan Mudir</label>
                            <textarea name="catatan_mudir" id="er_cat_p" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Publikasi</label>
                            <select name="status_publikasi" id="er_status" class="form-select">
                                <option value="Terbit">Terbit</option>
                                <option value="Draft">Draft</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Terbit</label>
                            <input type="date" name="tanggal_terbit" id="er_tgl" class="form-control" required>
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

<!-- Modal Hapus Rapor -->
<div class="modal fade" id="modalHapusRapor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="rapor.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hr_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Rapor</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus data rapor semester:</p>
                    <h6 class="fw-bold text-danger" id="hr_nama"></h6>
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
    const summaryData = <?= json_encode($auto_summary) ?>;
    const selSantri = document.getElementById('gen_santri_id');

    function applyAutoSummary(sid) {
        const d = summaryData[sid];
        if (!d) return;
        document.getElementById('gen_pengajar_id').value   = d.pengajar_id || 1;
        document.getElementById('gen_hafalan_juz').value   = d.total_hafalan_juz || 1;
        document.getElementById('gen_ziyadah_hal').value   = Math.round(d.ziyadah_hal || 20);
        document.getElementById('gen_nilai_tahfidz').value = d.avg_tahfidz || 92;
        document.getElementById('gen_nilai_tahsin').value  = d.avg_tahsin || 90;
        document.getElementById('gen_hadir').value         = d.jml_hadir || 110;
        document.getElementById('gen_sakit').value         = d.jml_sakit || 0;
        document.getElementById('gen_izin').value          = d.jml_izin || 0;
        document.getElementById('gen_alpa').value          = d.jml_alpa || 0;
        document.getElementById('gen_poin').value          = d.tot_poin || 0;
    }

    if (selSantri) {
        applyAutoSummary(selSantri.value);
        selSantri.addEventListener('change', function () {
            applyAutoSummary(this.value);
        });
    }

    document.querySelectorAll('.btn-edit-rapor').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-rapor'));
            document.getElementById('er_id').value       = d.id;
            document.getElementById('er_santri').value   = d.santri_id;
            document.getElementById('er_pengajar').value = d.pengajar_id;
            document.getElementById('er_semester').value = d.semester;
            document.getElementById('er_ta').value       = d.tahun_ajaran;
            document.getElementById('er_juz').value      = d.total_hafalan_juz;
            document.getElementById('er_ziy').value      = d.capaian_ziyadah_halaman;
            document.getElementById('er_ntf').value      = d.nilai_tahfidz;
            document.getElementById('er_nts').value      = d.nilai_tahsin;
            document.getElementById('er_nmt').value      = d.nilai_mutabaah;
            document.getElementById('er_akhlak').value   = d.nilai_akhlak;
            document.getElementById('er_hadir').value    = d.jumlah_hadir;
            document.getElementById('er_sakit').value    = d.jumlah_sakit;
            document.getElementById('er_izin').value     = d.jumlah_izin;
            document.getElementById('er_alpa').value     = d.jumlah_alpa;
            document.getElementById('er_poin').value     = d.total_poin_pelanggaran;
            document.getElementById('er_cat_m').value    = d.catatan_muhaffizh || '';
            document.getElementById('er_cat_p').value    = d.catatan_mudir || '';
            document.getElementById('er_status').value   = d.status_publikasi;
            document.getElementById('er_tgl').value      = d.tanggal_terbit || '';
        });
    });

    document.querySelectorAll('.btn-hapus-rapor').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hr_id').value = this.getAttribute('data-id');
            document.getElementById('hr_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
