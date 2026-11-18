<?php
/**
 * FILE: modules/master/santri.php
 * Modul Master Data Santri (Form Simpel Tanpa NIS/NISN Manual + Sesuai Berkas Rumah Quran Qurthuba)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin', 'pengajar']);

$page_title = 'Data Santri';
$alert_msg  = '';
$alert_type = 'success';

$daftar_angkatan = [
    '2021/2022',
    '2022/2023',
    '2023/2024',
    '2024/2025',
    '2025/2026',
];

$daftar_kategori_halaqah = ['Tahsin Dasar', 'Tahfidz Lanjutan'];
$daftar_status_santri    = ['Aktif', 'Alumni', 'Keluar'];
$daftar_status_berkas    = [
    'Lengkap (7 Berkas)',
    'Sebagian / Menyusul',
    'Belum Lengkap',
];

// Ambil Daftar Halaqah (beserta pengajar_id & target_program)
$list_halaqah = [];
$map_halaqah  = [];
$res_hlq = mysqli_query($conn, "SELECT h.*, p.nama_pengajar FROM halaqah h LEFT JOIN pengajar p ON h.pengajar_id = p.id ORDER BY h.id ASC");
while ($r = mysqli_fetch_assoc($res_hlq)) {
    $list_halaqah[] = $r;
    $map_halaqah[(int) $r['id']] = $r;
}

$list_ortu = [];
$res_ortu = mysqli_query($conn, "SELECT id, nama_lengkap, username FROM users WHERE role='orang_tua' AND status_aktif='Aktif' ORDER BY id ASC");
while ($r = mysqli_fetch_assoc($res_ortu)) {
    $list_ortu[] = $r;
}

// Proses CRUD Santri (Simpel & Otomatis tanpa input NIS/NISN manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id            = (int) ($_POST['id'] ?? 0);
        $nama_santri   = trim($_POST['nama_santri'] ?? '');
        $jenis_kelamin = ($_POST['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L';
        $tempat_lahir  = trim($_POST['tempat_lahir'] ?? 'Bandung') ?: 'Bandung';
        $tanggal_lahir = !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : '2011-01-01';

        // Hitung usia otomatis dari tanggal lahir jika tersedia
        $usia_input = (int) ($_POST['usia'] ?? 0);
        if ($usia_input > 0) {
            $usia = max(10, min(22, $usia_input));
        } else {
            $tahun_lhr = (int) substr($tanggal_lahir, 0, 4);
            $usia      = max(10, min(22, (int) date('Y') - $tahun_lhr));
        }

        $jenjang        = ($_POST['jenjang'] ?? 'SMP') === 'SMA' ? 'SMA' : 'SMP';
        $asal_sekolah   = trim($_POST['asal_sekolah'] ?? '') ?: ($jenjang === 'SMA' ? 'SMA / MA Sederajat' : 'SMP / MTs Sederajat');
        $tahun_angkatan = in_array($_POST['tahun_angkatan'] ?? '', $daftar_angkatan, true) ? $_POST['tahun_angkatan'] : '2025/2026';

        // Kelompok Halaqah otomatis mengisi Musyrif & Kategori Halaqah jika tidak diubah manual
        $halaqah_id       = (int) ($_POST['halaqah_id'] ?? 1);
        $hlq_info         = $map_halaqah[$halaqah_id] ?? null;
        $kelompok_musyrif = $hlq_info['nama_halaqah'] ?? 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)';
        $pengajar_id      = (int) ($_POST['pengajar_id'] ?? ($hlq_info['pengajar_id'] ?? 1));
        $kategori_input   = $_POST['kategori_halaqah'] ?? ($hlq_info['target_program'] ?? 'Tahsin Dasar');
        $kategori_halaqah = in_array($kategori_input, $daftar_kategori_halaqah, true) ? $kategori_input : 'Tahsin Dasar';

        $kelas_program     = trim($_POST['kelas_program'] ?? 'Program Tahfidz Qurthuba');
        $jilid_tahsin      = trim($_POST['jilid_tahsin'] ?? "Al-Qur'an / Tajwid");
        $target_juz        = max(1, min(30, (int) ($_POST['target_juz'] ?? 5)));
        $total_hafalan_juz = max(0, min(30, (float) ($_POST['total_hafalan_juz'] ?? 0)));

        $nama_orang_tua    = trim($_POST['nama_orang_tua'] ?? '');
        $no_hp_orang_tua   = trim($_POST['no_hp_orang_tua'] ?? '');
        $alamat            = trim($_POST['alamat'] ?? '');
        $status_berkas     = in_array($_POST['status_berkas'] ?? '', $daftar_status_berkas, true) ? $_POST['status_berkas'] : 'Lengkap (7 Berkas)';
        $id_user_orang_tua = (int) ($_POST['id_user_orang_tua'] ?? 3);
        if ($id_user_orang_tua <= 0) {
            $id_user_orang_tua = 3;
        }
        $tanggal_masuk = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : date('Y-m-d');
        $status_santri = in_array($_POST['status_santri'] ?? '', $daftar_status_santri, true) ? $_POST['status_santri'] : 'Aktif';

        if ($aksi === 'tambah') {
            // Buat nomor urut otomatis (1, 2, 3, ...) tanpa perlu ketik NIS/NISN
            $next_no = hitung_total($conn, "SELECT COALESCE(MAX(id), 0) + 1 FROM santri", 1);
            $nis_auto  = (string) $next_no;
            $nisn_auto = '-';

            $stmt = mysqli_prepare($conn, "INSERT INTO santri (nis, nisn, nama_santri, jenis_kelamin, tempat_lahir, tanggal_lahir, usia, jenjang, asal_sekolah, tahun_angkatan, kategori_halaqah, kelompok_musyrif, kelas_program, jilid_tahsin, target_juz, total_hafalan_juz, halaqah_id, pengajar_id, id_user_orang_tua, nama_orang_tua, no_hp_orang_tua, alamat, status_berkas, tanggal_masuk, status_santri) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssssssisssssssidiiissssss', $nis_auto, $nisn_auto, $nama_santri, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $usia, $jenjang, $asal_sekolah, $tahun_angkatan, $kategori_halaqah, $kelompok_musyrif, $kelas_program, $jilid_tahsin, $target_juz, $total_hafalan_juz, $halaqah_id, $pengajar_id, $id_user_orang_tua, $nama_orang_tua, $no_hp_orang_tua, $alamat, $status_berkas, $tanggal_masuk, $status_santri);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = (int) mysqli_insert_id($conn);
                if ($new_id > 0) {
                    mysqli_query($conn, "UPDATE santri SET nis = '$new_id' WHERE id = $new_id");
                }
                $alert_msg = "Data santri \"$nama_santri\" (No. Urut #$new_id) berhasil ditambahkan!";
            } else {
                $alert_msg  = 'Gagal menambah santri: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            // Pastikan nomor tetap berupa angka ID urut sederhana
            $nis_simple = (string) $id;
            $stmt = mysqli_prepare($conn, "UPDATE santri SET nis=?, nama_santri=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, usia=?, jenjang=?, asal_sekolah=?, tahun_angkatan=?, kategori_halaqah=?, kelompok_musyrif=?, kelas_program=?, jilid_tahsin=?, target_juz=?, total_hafalan_juz=?, halaqah_id=?, pengajar_id=?, id_user_orang_tua=?, nama_orang_tua=?, no_hp_orang_tua=?, alamat=?, status_berkas=?, status_santri=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssisssssssidiiisssssi', $nis_simple, $nama_santri, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $usia, $jenjang, $asal_sekolah, $tahun_angkatan, $kategori_halaqah, $kelompok_musyrif, $kelas_program, $jilid_tahsin, $target_juz, $total_hafalan_juz, $halaqah_id, $pengajar_id, $id_user_orang_tua, $nama_orang_tua, $no_hp_orang_tua, $alamat, $status_berkas, $status_santri, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Data santri \"$nama_santri\" (No. #$id) berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal mengupdate santri: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM santri WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $alert_msg = 'Data santri berhasil dihapus!';
        } else {
            $alert_msg  = 'Gagal menghapus santri: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// 4 Filter Dropdown: Tahun Angkatan, Kelompok Musyrif, Kategori Halaqah & Status Santri
$filter_angkatan = trim($_GET['angkatan'] ?? '');
$filter_halaqah  = (int) ($_GET['halaqah_id'] ?? 0);
$filter_kategori = trim($_GET['kategori_halaqah'] ?? '');
$filter_status   = trim($_GET['status'] ?? '');

$where_clauses = ["1=1"];
if (in_array($filter_angkatan, $daftar_angkatan, true)) {
    $where_clauses[] = "s.tahun_angkatan = '" . mysqli_real_escape_string($conn, $filter_angkatan) . "'";
}
if ($filter_halaqah > 0) {
    $where_clauses[] = "s.halaqah_id = " . $filter_halaqah;
}
if (in_array($filter_kategori, $daftar_kategori_halaqah, true)) {
    $where_clauses[] = "s.kategori_halaqah = '" . mysqli_real_escape_string($conn, $filter_kategori) . "'";
}
if (in_array($filter_status, $daftar_status_santri, true)) {
    $where_clauses[] = "s.status_santri = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}
$where_sql = implode(' AND ', $where_clauses);

$sql_santri = "SELECT s.*, h.nama_halaqah, p.nama_pengajar
               FROM santri s
               LEFT JOIN halaqah h ON s.halaqah_id = h.id
               LEFT JOIN pengajar p ON s.pengajar_id = p.id
               WHERE $where_sql
               ORDER BY s.id ASC";
$res_santri = mysqli_query($conn, $sql_santri);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-users me-2"></i>Data Santri Rumah Quran Qurthuba</h4>
        <small class="text-muted">Penomoran otomatis (1, 2, 3...) &amp; sesuai Formulir Riwayat Hidup / Surat Izin Orang Tua</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalInfoBerkas">
            <i class="fa-solid fa-file-circle-check me-1"></i> Syarat 7 Berkas Santri
        </button>
        <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahSantri">
            <i class="fa-solid fa-user-plus me-1"></i> + Tambah Santri Baru
        </button>
    </div>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-info me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Bar 4 Dropdown -->
<div class="card-siata p-3 mb-3">
    <form method="GET" action="santri.php" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Angkatan</label>
            <select name="angkatan" class="form-select form-select-sm">
                <option value="">-- Semua Angkatan --</option>
                <?php foreach ($daftar_angkatan as $idx => $angk): ?>
                    <option value="<?= e($angk) ?>" <?= ($filter_angkatan === $angk) ? 'selected' : '' ?>>
                        Angkatan <?= $idx + 1 ?> (<?= e($angk) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Kelompok Halaqah</label>
            <select name="halaqah_id" class="form-select form-select-sm">
                <option value="0">-- Semua Kelompok --</option>
                <?php foreach ($list_halaqah as $hlq): ?>
                    <option value="<?= (int) $hlq['id'] ?>" <?= ($filter_halaqah === (int) $hlq['id']) ? 'selected' : '' ?>>
                        <?= e($hlq['nama_halaqah']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Kategori Program</label>
            <select name="kategori_halaqah" class="form-select form-select-sm">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($daftar_kategori_halaqah as $kh): ?>
                    <option value="<?= e($kh) ?>" <?= ($filter_kategori === $kh) ? 'selected' : '' ?>><?= e($kh) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status Santri</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">-- Semua Status --</option>
                <?php foreach ($daftar_status_santri as $st): ?>
                    <option value="<?= e($st) ?>" <?= ($filter_status === $st) ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-siata btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <a href="santri.php" class="btn btn-outline-secondary btn-sm" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- Tabel DataTables Santri (Simpel: No 1, 2, 3... Tanpa NIS/NISN QRT) -->
<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-table-list"></i> Daftar Santri Rumah Quran Qurthuba</h5>
        <span class="badge bg-warning text-dark">Total Tampil: <?= mysqli_num_rows($res_santri) ?> Santri</span>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th style="width: 55px;" class="text-center">No</th>
                    <th>Nama Santri &amp; TTL</th>
                    <th>Pendidikan &amp; Asal Sekolah</th>
                    <th>Orang Tua / Wali &amp; Alamat</th>
                    <th>Kelompok Halaqah</th>
                    <th>Hafalan</th>
                    <th>Berkas</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_santri)): ?>
                <?php
                    $badge_status = match ($row['status_santri']) {
                        'Aktif'  => 'bg-success',
                        'Alumni' => 'bg-primary',
                        default  => 'bg-danger'
                    };
                    $badge_kat = ($row['kategori_halaqah'] === 'Tahfidz Lanjutan')
                        ? 'bg-success-subtle text-success border border-success'
                        : 'bg-info-subtle text-info-emphasis border border-info';
                    $st_berkas = $row['status_berkas'] ?? 'Lengkap (7 Berkas)';
                    $badge_berkas = ($st_berkas === 'Lengkap (7 Berkas)')
                        ? 'bg-success-subtle text-success'
                        : (($st_berkas === 'Sebagian / Menyusul') ? 'bg-warning-subtle text-dark' : 'bg-danger-subtle text-danger');
                ?>
                <tr>
                    <td class="text-center fw-bold text-dark"><?= $no++ ?></td>
                    <td>
                        <div class="fw-bold text-dark">
                            <?= e($row['nama_santri']) ?>
                            <span class="badge <?= $row['jenis_kelamin'] === 'L' ? 'bg-info text-dark' : 'bg-danger-subtle text-danger' ?> ms-1">
                                <?= e($row['jenis_kelamin']) ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?= e($row['tempat_lahir']) ?>, <?= e($row['tanggal_lahir']) ?> (<?= (int) $row['usia'] ?> Thn)
                        </small>
                    </td>
                    <td>
                        <div>
                            <span class="badge bg-dark"><?= e($row['jenjang']) ?></span>
                            <span class="fw-semibold text-dark small ms-1"><?= e($row['asal_sekolah'] ?? 'SMP / MTs Sederajat') ?></span>
                        </div>
                        <small class="text-muted">Angkatan: <?= e($row['tahun_angkatan']) ?></small>
                    </td>
                    <td>
                        <div class="small fw-semibold text-dark"><?= e($row['nama_orang_tua'] ?: '-') ?></div>
                        <small class="text-success fw-medium"><i class="fa-solid fa-phone me-1"></i><?= e($row['no_hp_orang_tua'] ?: '-') ?></small>
                        <?php if (!empty($row['alamat'])): ?>
                            <small class="text-muted d-block text-truncate" style="max-width: 190px;" title="<?= e($row['alamat']) ?>">
                                <i class="fa-solid fa-location-dot me-1"></i><?= e($row['alamat']) ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $badge_kat ?> mb-1"><?= e($row['kategori_halaqah']) ?></span>
                        <div class="fw-semibold text-success small"><?= e($row['kelompok_musyrif'] ?: ($row['nama_halaqah'] ?? '-')) ?></div>
                        <small class="text-muted"><i class="fa-solid fa-user-tie me-1"></i><?= e($row['nama_pengajar'] ?? '-') ?></small>
                    </td>
                    <td>
                        <span class="fw-bold text-dark"><?= e((string) $row['total_hafalan_juz']) ?> Juz</span>
                        <small class="text-muted d-block">Target: <?= (int) $row['target_juz'] ?> Juz</small>
                    </td>
                    <td>
                        <span class="badge <?= $badge_berkas ?>"><?= e($st_berkas) ?></span>
                    </td>
                    <td><span class="badge <?= $badge_status ?>"><?= e($row['status_santri']) ?></span></td>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-santri"
                                data-santri='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditSantri" title="Edit Data Santri">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-santri"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusSantri" title="Hapus">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Info 7 Berkas Kelengkapan Penerimaan Santri (Sesuai Undangan Rumah Quran Qurthuba) -->
<div class="modal fade" id="modalInfoBerkas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-siata">
                <h5 class="modal-title"><i class="fa-solid fa-clipboard-check me-2"></i>Berkas Kelengkapan Penerimaan Santri</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Sesuai dokumen resmi <strong>Yayasan Rumah Quran Qurthuba</strong> (Perum. Griya Ranca Indah 1 Blok B1 No. 7, Rancaekek, Kab. Bandung), berikut 7 berkas administrasi santri:
                </p>
                <ol class="list-group list-group-numbered small">
                    <li class="list-group-item"><strong>CV Santri / Daftar Riwayat Hidup</strong> (Nama, Alamat, No. Telp Ortu/Wali, JK, TTL, Jenjang &amp; Riwayat Sekolah)</li>
                    <li class="list-group-item"><strong>Foto Copy Akta Lahir</strong></li>
                    <li class="list-group-item"><strong>Foto Copy Ijazah Terakhir</strong></li>
                    <li class="list-group-item"><strong>Foto Copy KTP Orang Tua</strong></li>
                    <li class="list-group-item"><strong>Foto Copy Kartu Keluarga (KK)</strong></li>
                    <li class="list-group-item"><strong>Surat Pernyataan &amp; Izin Orang Tua/Wali</strong> menyerahkan anaknya ke Pondok (Bertandatangan &amp; Bermaterai 10.000)</li>
                    <li class="list-group-item"><strong>Photo Santri 2x3 dan 3x4</strong> hitam putih (masing-masing 2 lembar)</li>
                </ol>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-siata btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Santri Baru (Simpel & Sesuai Daftar Riwayat Hidup + Surat Izin Orang Tua) -->
<div class="modal fade" id="modalTambahSantri" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="santri.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>Tambah Santri Baru (Simpel &amp; Otomatis)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success py-2 px-3 small mb-3 border-0">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                        <strong>Nomor Urut Santri dibuat otomatis (1, 2, 3...)</strong> — Anda hanya perlu mengisi biodata santri &amp; orang tua di bawah ini.
                    </div>

                    <!-- BAGIAN 1: DATA PRIBADI & PENDIDIKAN (Sesuai Daftar Riwayat Hidup) -->
                    <h6 class="fw-bold text-success border-bottom pb-1 mb-3">
                        <i class="fa-solid fa-id-card me-1"></i> 1. Data Pribadi &amp; Riwayat Pendidikan Santri
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Lengkap Santri <span class="text-danger">*</span></label>
                            <input type="text" name="nama_santri" class="form-control" placeholder="Contoh: Muhammad Raihan" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenjang Pendidikan</label>
                            <select name="jenjang" class="form-select" required>
                                <option value="SMP">SMP / MTs</option>
                                <option value="SMA">SMA / MA / SMK</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control" value="Bandung" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="2011-05-10" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Asal Sekolah</label>
                            <input type="text" name="asal_sekolah" class="form-control" placeholder="Contoh: SMPN 1 Rancaekek">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Alamat Lengkap</label>
                            <input type="text" name="alamat" class="form-control" placeholder="Contoh: Perum. Griya Ranca Indah, Rancaekek, Kab. Bandung">
                        </div>
                    </div>

                    <!-- BAGIAN 2: DATA ORANG TUA / WALI & BERKAS -->
                    <h6 class="fw-bold text-success border-bottom pb-1 mb-3 mt-4">
                        <i class="fa-solid fa-people-roof me-1"></i> 2. Data Orang Tua / Wali &amp; Kelengkapan Berkas
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nama Orang Tua / Wali <span class="text-danger">*</span></label>
                            <input type="text" name="nama_orang_tua" class="form-control" placeholder="Contoh: Bpk. H. Sulaiman" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">No. Telepon / WA Orang Tua <span class="text-danger">*</span></label>
                            <input type="text" name="no_hp_orang_tua" class="form-control" placeholder="Contoh: 0812-xxxx-xxxx" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kelengkapan 7 Berkas</label>
                            <select name="status_berkas" class="form-select">
                                <?php foreach ($daftar_status_berkas as $sb): ?>
                                    <option value="<?= e($sb) ?>"><?= e($sb) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- BAGIAN 3: PENEMPATAN HALAQAH & TARGET -->
                    <h6 class="fw-bold text-success border-bottom pb-1 mb-3 mt-4">
                        <i class="fa-solid fa-book-quran me-1"></i> 3. Kelompok Halaqah &amp; Target Tahfidz
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kelompok Halaqah / Musyrif</label>
                            <select name="halaqah_id" class="form-select" required>
                                <?php foreach ($list_halaqah as $hlq): ?>
                                    <option value="<?= (int) $hlq['id'] ?>"><?= e($hlq['nama_halaqah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tahun Angkatan</label>
                            <select name="tahun_angkatan" class="form-select" required>
                                <?php foreach ($daftar_angkatan as $idx => $angk): ?>
                                    <option value="<?= e($angk) ?>" <?= $angk === '2025/2026' ? 'selected' : '' ?>>
                                        Angkatan <?= $idx + 1 ?> (<?= e($angk) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Target Hafalan (Juz)</label>
                            <input type="number" name="target_juz" class="form-control" value="5" min="1" max="30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Data Santri</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Santri (Simpel & Tanpa Input NIS/NISN) -->
<div class="modal fade" id="modalEditSantri" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="santri.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="kelas_program" id="edit_kelas_program">
                <input type="hidden" name="jilid_tahsin" id="edit_jilid_tahsin">
                <input type="hidden" name="id_user_orang_tua" id="edit_id_user_orang_tua">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-user-pen me-2"></i>Edit Data Santri (<span id="edit_no_label">#</span>)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-success border-bottom pb-1 mb-3">
                        <i class="fa-solid fa-id-card me-1"></i> 1. Data Pribadi &amp; Riwayat Pendidikan Santri
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Lengkap Santri <span class="text-danger">*</span></label>
                            <input type="text" name="nama_santri" id="edit_nama_santri" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenjang Pendidikan</label>
                            <select name="jenjang" id="edit_jenjang" class="form-select" required>
                                <option value="SMP">SMP / MTs</option>
                                <option value="SMA">SMA / MA / SMK</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="edit_tempat_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="edit_tanggal_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Asal Sekolah</label>
                            <input type="text" name="asal_sekolah" id="edit_asal_sekolah" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Alamat Lengkap</label>
                            <input type="text" name="alamat" id="edit_alamat" class="form-control">
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-1 mb-3 mt-4">
                        <i class="fa-solid fa-people-roof me-1"></i> 2. Data Orang Tua / Wali &amp; Kelengkapan Berkas
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nama Orang Tua / Wali <span class="text-danger">*</span></label>
                            <input type="text" name="nama_orang_tua" id="edit_nama_orang_tua" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">No. Telepon / WA Orang Tua <span class="text-danger">*</span></label>
                            <input type="text" name="no_hp_orang_tua" id="edit_no_hp_orang_tua" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kelengkapan 7 Berkas</label>
                            <select name="status_berkas" id="edit_status_berkas" class="form-select">
                                <?php foreach ($daftar_status_berkas as $sb): ?>
                                    <option value="<?= e($sb) ?>"><?= e($sb) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-1 mb-3 mt-4">
                        <i class="fa-solid fa-book-quran me-1"></i> 3. Kelompok Halaqah, Hafalan &amp; Status
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Kelompok Halaqah / Musyrif</label>
                            <select name="halaqah_id" id="edit_halaqah_id" class="form-select" required>
                                <?php foreach ($list_halaqah as $hlq): ?>
                                    <option value="<?= (int) $hlq['id'] ?>"><?= e($hlq['nama_halaqah']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tahun Angkatan</label>
                            <select name="tahun_angkatan" id="edit_tahun_angkatan" class="form-select" required>
                                <?php foreach ($daftar_angkatan as $idx => $angk): ?>
                                    <option value="<?= e($angk) ?>">Angkatan <?= $idx + 1 ?> (<?= e($angk) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Target (Juz)</label>
                            <input type="number" name="target_juz" id="edit_target_juz" class="form-control" min="1" max="30">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Hafalan (Juz)</label>
                            <input type="number" step="0.5" name="total_hafalan_juz" id="edit_total_hafalan_juz" class="form-control" min="0" max="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori Program</label>
                            <select name="kategori_halaqah" id="edit_kategori_halaqah" class="form-select">
                                <option value="Tahsin Dasar">Tahsin Dasar</option>
                                <option value="Tahfidz Lanjutan">Tahfidz Lanjutan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Status Santri</label>
                            <select name="status_santri" id="edit_status_santri" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Alumni">Alumni</option>
                                <option value="Keluar">Keluar</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Santri -->
<div class="modal fade" id="modalHapusSantri" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="santri.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hapus_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Konfirmasi Hapus</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Yakin ingin menghapus data santri:</p>
                    <h6 class="fw-bold text-danger" id="hapus_nama"></h6>
                    <small class="text-muted">Seluruh riwayat setoran terkait juga akan terhapus.</small>
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
    document.querySelectorAll('.btn-edit-santri').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-santri'));
            document.getElementById('edit_id').value = d.id;
            document.getElementById('edit_no_label').textContent = 'No. Urut #' + d.id;
            document.getElementById('edit_nama_santri').value = d.nama_santri;
            document.getElementById('edit_jenis_kelamin').value = d.jenis_kelamin;
            document.getElementById('edit_tempat_lahir').value = d.tempat_lahir || 'Bandung';
            document.getElementById('edit_tanggal_lahir').value = d.tanggal_lahir || '2011-01-01';
            document.getElementById('edit_jenjang').value = d.jenjang || 'SMP';
            document.getElementById('edit_asal_sekolah').value = d.asal_sekolah || 'SMP / MTs Sederajat';
            document.getElementById('edit_tahun_angkatan').value = d.tahun_angkatan || '2025/2026';
            document.getElementById('edit_kategori_halaqah').value = d.kategori_halaqah || 'Tahsin Dasar';
            document.getElementById('edit_kelas_program').value = d.kelas_program || 'Program Tahfidz Qurthuba';
            document.getElementById('edit_jilid_tahsin').value = d.jilid_tahsin || "Al-Qur'an / Tajwid";
            document.getElementById('edit_target_juz').value = d.target_juz || 5;
            document.getElementById('edit_total_hafalan_juz').value = d.total_hafalan_juz || 0;
            document.getElementById('edit_halaqah_id').value = d.halaqah_id || 1;
            document.getElementById('edit_id_user_orang_tua').value = d.id_user_orang_tua || 3;
            document.getElementById('edit_nama_orang_tua').value = d.nama_orang_tua || '';
            document.getElementById('edit_no_hp_orang_tua').value = d.no_hp_orang_tua || '';
            document.getElementById('edit_status_berkas').value = d.status_berkas || 'Lengkap (7 Berkas)';
            document.getElementById('edit_status_santri').value = d.status_santri || 'Aktif';
            document.getElementById('edit_alamat').value = d.alamat || '';
        });
    });

    document.querySelectorAll('.btn-hapus-santri').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hapus_id').value = this.getAttribute('data-id');
            document.getElementById('hapus_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
