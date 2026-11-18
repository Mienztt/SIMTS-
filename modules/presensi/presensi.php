<?php
/**
 * FILE: modules/presensi/presensi.php
 * Modul Inovasi Presensi Halaqah (Tab 1: Input Presensi Massal/Harian & Tab 2: Rekap Absensi Santri)
 * Tabel: presensi_halaqah (id, santri_id, pengajar_id, tanggal, sesi_halaqah, status_kehadiran, waktu_hadir, keterangan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Presensi 7 Sesi Harian';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$active_tab = $_GET['tab'] ?? ($role === 'orang_tua' ? 'rekap' : 'input');
$alert_msg  = '';
$alert_type = 'success';

$daftar_sesi = [
    'Sesi 1 - Dini Hari / Tahajud (03.00 - 04.30)',
    'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)',
    'Sesi 3 - Pagi / Dhuha (08.00 - 11.00)',
    'Sesi 4 - Siang / Kondisional (13.00 - 14.30)',
    'Sesi 5 - Sore / Ashar: Halaqah Hadis (16.00 - 17.30)',
    'Sesi 6 - Maghrib: Tilawah / Kajian Jumat (18.00 - 19.30)',
    'Sesi 7 - Ba\'da Isya: Evaluasi & Mutaba\'ah (19.30 - 21.00)'
];

$waktu_default_sesi = [
    'Sesi 1 - Dini Hari / Tahajud (03.00 - 04.30)'               => '03:05',
    'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)'             => '04:30',
    'Sesi 3 - Pagi / Dhuha (08.00 - 11.00)'                      => '08:00',
    'Sesi 4 - Siang / Kondisional (13.00 - 14.30)'               => '13:00',
    'Sesi 5 - Sore / Ashar: Halaqah Hadis (16.00 - 17.30)'       => '16:00',
    'Sesi 6 - Maghrib: Tilawah / Kajian Jumat (18.00 - 19.30)'   => '18:00',
    'Sesi 7 - Ba\'da Isya: Evaluasi & Mutaba\'ah (19.30 - 21.00)' => '19:30'
];

$daftar_angkatan = [
    '2021/2022',
    '2022/2023',
    '2023/2024',
    '2024/2025',
    '2025/2026'
];

// Filter Parameter Input & Rekap
$f_tanggal     = $_GET['tanggal'] ?? date('Y-m-d');
$f_sesi        = in_array($_GET['sesi'] ?? '', $daftar_sesi, true) ? $_GET['sesi'] : 'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)';
$f_halaqah_id  = (int) ($_GET['halaqah_id'] ?? 0);
$f_angkatan    = in_array($_GET['angkatan'] ?? '', $daftar_angkatan, true) ? $_GET['angkatan'] : '';
$f_bulan       = $_GET['bulan'] ?? date('Y-m');
$f_rekap_tgl   = trim($_GET['rekap_tanggal'] ?? '');
$f_rekap_sesi  = in_array($_GET['rekap_sesi'] ?? '', $daftar_sesi, true) ? $_GET['rekap_sesi'] : '';

// Simpan Presensi Massal / Harian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'simpan_presensi') {
    cek_role(['admin', 'pengajar']);

    $p_tanggal    = $_POST['tanggal'] ?: date('Y-m-d');
    $p_sesi       = in_array($_POST['sesi_halaqah'] ?? '', $daftar_sesi, true) ? $_POST['sesi_halaqah'] : 'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)';
    $p_halaqah_id = (int) ($_POST['halaqah_id'] ?? 0);
    $p_angkatan   = in_array($_POST['angkatan'] ?? '', $daftar_angkatan, true) ? $_POST['angkatan'] : '';
    $status_arr   = $_POST['status_kehadiran'] ?? [];
    $waktu_arr    = $_POST['waktu_hadir'] ?? [];
    $ket_arr      = $_POST['keterangan'] ?? [];
    $pengajar_arr = $_POST['pengajar_id'] ?? [];

    $tersimpan = 0;
    foreach ($status_arr as $santri_id => $status_kehadiran) {
        $santri_id        = (int) $santri_id;
        $pengajar_id      = (int) ($pengajar_arr[$santri_id] ?? 1);
        $status_kehadiran = in_array($status_kehadiran, ['Hadir', 'Sakit', 'Izin', 'Alpa'], true) ? $status_kehadiran : 'Hadir';
        $default_wkt      = ($waktu_default_sesi[$p_sesi] ?? '04:30') . ':00';
        $waktu_hadir      = ($status_kehadiran === 'Hadir') ? ($waktu_arr[$santri_id] ?: $default_wkt) : null;
        $keterangan       = trim($ket_arr[$santri_id] ?? '');

        // Cek apakah sudah ada presensi untuk santri, tanggal, dan sesi tersebut
        $cek = mysqli_prepare($conn, "SELECT id FROM presensi_halaqah WHERE santri_id=? AND tanggal=? AND sesi_halaqah=? LIMIT 1");
        mysqli_stmt_bind_param($cek, 'iss', $santri_id, $p_tanggal, $p_sesi);
        mysqli_stmt_execute($cek);
        $res_cek = mysqli_stmt_get_result($cek);

        if ($row_cek = mysqli_fetch_assoc($res_cek)) {
            $id_pres = (int) $row_cek['id'];
            $upd = mysqli_prepare($conn, "UPDATE presensi_halaqah SET pengajar_id=?, status_kehadiran=?, waktu_hadir=?, keterangan=? WHERE id=?");
            mysqli_stmt_bind_param($upd, 'isssi', $pengajar_id, $status_kehadiran, $waktu_hadir, $keterangan, $id_pres);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO presensi_halaqah (santri_id, pengajar_id, tanggal, sesi_halaqah, status_kehadiran, waktu_hadir, keterangan) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($ins, 'iisssss', $santri_id, $pengajar_id, $p_tanggal, $p_sesi, $status_kehadiran, $waktu_hadir, $keterangan);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($cek);
        $tersimpan++;
    }

    $f_tanggal    = $p_tanggal;
    $f_sesi       = $p_sesi;
    $f_halaqah_id = $p_halaqah_id;
    $f_angkatan   = $p_angkatan;
    $alert_msg    = "Presensi $p_sesi tanggal $p_tanggal berhasil disimpan untuk $tersimpan santri!";
}

// Ambil Daftar Halaqah
$list_halaqah = [];
$res_hlq = mysqli_query($conn, "SELECT h.*, p.nama_pengajar FROM halaqah h LEFT JOIN pengajar p ON h.pengajar_id = p.id ORDER BY h.kode_halaqah ASC");
while ($r = mysqli_fetch_assoc($res_hlq)) {
    $list_halaqah[] = $r;
}

// Data Santri untuk Tab 1 (Input Presensi Harian)
$where_hlq  = ($f_halaqah_id > 0) ? "AND s.halaqah_id = $f_halaqah_id" : "";
$where_angk = ($f_angkatan !== '') ? "AND s.tahun_angkatan = '" . mysqli_real_escape_string($conn, $f_angkatan) . "'" : "";
$where_ortu = ($role === 'orang_tua') ? "AND s.id_user_orang_tua = $user_id" : "";
$esc_tgl    = mysqli_real_escape_string($conn, $f_tanggal);
$esc_sesi   = mysqli_real_escape_string($conn, $f_sesi);

$sql_input = "SELECT s.id AS santri_id, s.nis, s.nama_santri, s.tahun_angkatan, s.kelompok_musyrif, s.pengajar_id, h.nama_halaqah, p.nama_pengajar,
                     pr.status_kehadiran, pr.waktu_hadir, pr.keterangan
              FROM santri s
              LEFT JOIN halaqah h ON s.halaqah_id = h.id
              LEFT JOIN pengajar p ON s.pengajar_id = p.id
              LEFT JOIN presensi_halaqah pr ON (pr.santri_id = s.id AND pr.tanggal = '$esc_tgl' AND pr.sesi_halaqah = '$esc_sesi')
              WHERE s.status_santri = 'Aktif' $where_hlq $where_angk
              ORDER BY s.id ASC";
$res_input = mysqli_query($conn, $sql_input);

// Data Rekap untuk Tab 2 (Rekap Absensi Santri per Tanggal/Bulan, Sesi 1-7, Tahun Angkatan & Kelompok Musyrif)
$esc_bulan     = mysqli_real_escape_string($conn, $f_bulan);
$rekap_date_cond = ($f_rekap_tgl !== '')
    ? "pr.tanggal = '" . mysqli_real_escape_string($conn, $f_rekap_tgl) . "'"
    : "DATE_FORMAT(pr.tanggal, '%Y-%m') = '$esc_bulan'";
$rekap_sesi_cond = ($f_rekap_sesi !== '')
    ? "AND pr.sesi_halaqah = '" . mysqli_real_escape_string($conn, $f_rekap_sesi) . "'"
    : "";

$sql_rekap = "SELECT s.id, s.nis, s.nama_santri, s.tahun_angkatan, s.kelompok_musyrif, h.nama_halaqah, p.nama_pengajar,
                     SUM(CASE WHEN pr.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS jml_hadir,
                     SUM(CASE WHEN pr.status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) AS jml_sakit,
                     SUM(CASE WHEN pr.status_kehadiran = 'Izin'  THEN 1 ELSE 0 END) AS jml_izin,
                     SUM(CASE WHEN pr.status_kehadiran = 'Alpa'  THEN 1 ELSE 0 END) AS jml_alpa,
                     COUNT(pr.id) AS total_pertemuan
              FROM santri s
              LEFT JOIN halaqah h ON s.halaqah_id = h.id
              LEFT JOIN pengajar p ON s.pengajar_id = p.id
              LEFT JOIN presensi_halaqah pr ON (pr.santri_id = s.id AND $rekap_date_cond $rekap_sesi_cond)
              WHERE s.status_santri = 'Aktif' $where_ortu $where_hlq $where_angk
              GROUP BY s.id
              ORDER BY s.id ASC";
$res_rekap = mysqli_query($conn, $sql_rekap);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-calendar-check me-2"></i>Presensi 7 Sesi Kegiatan Harian Santri</h4>
        <small class="text-muted">Pencatatan kehadiran harian 7 sesi (03.00–21.00 WIB) dengan filter Angkatan &amp; Kelompok Halaqah</small>
    </div>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Navigasi 2 Tab: Input Presensi Harian & Rekap Absensi Santri -->
<ul class="nav nav-tabs nav-tabs-siata mb-3 bg-white rounded-top px-3 pt-2 border" role="tablist">
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'input' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#paneInputPresensi" type="button" role="tab">
            <i class="fa-solid fa-user-check me-1"></i> Tab 1: Input Presensi 7 Sesi
        </button>
    </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'rekap' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#paneRekapPresensi" type="button" role="tab">
            <i class="fa-solid fa-chart-simple me-1"></i> Tab 2: Rekap Absensi Santri
        </button>
    </li>
</ul>

<div class="tab-content">
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <!-- ======================================================================
         TAB 1: INPUT PRESENSI HARIAN
         ====================================================================== -->
    <div class="tab-pane fade <?= $active_tab === 'input' ? 'show active' : '' ?>" id="paneInputPresensi" role="tabpanel">
        <!-- Filter Pilih Tanggal, Sesi Halaqah, Angkatan & Kelompok -->
        <div class="card-siata p-3 mb-3">
            <form method="GET" action="presensi.php" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="input">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tanggal Presensi</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($f_tanggal) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Sesi Kegiatan (7 Sesi)</label>
                    <select name="sesi" class="form-select form-select-sm">
                        <?php foreach ($daftar_sesi as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($f_sesi === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Tahun Angkatan</label>
                    <select name="angkatan" class="form-select form-select-sm">
                        <option value="">-- Semua Angkatan --</option>
                        <?php foreach ($daftar_angkatan as $angk): ?>
                            <option value="<?= e($angk) ?>" <?= ($f_angkatan === $angk) ? 'selected' : '' ?>><?= e($angk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Kelompok Musyrif</label>
                    <select name="halaqah_id" class="form-select form-select-sm">
                        <option value="0">-- Semua Musyrif --</option>
                        <?php foreach ($list_halaqah as $hlq): ?>
                            <option value="<?= (int) $hlq['id'] ?>" <?= ($f_halaqah_id === (int) $hlq['id']) ? 'selected' : '' ?>>
                                <?= e($hlq['nama_halaqah']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-siata btn-sm w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>

        <!-- Form Tabel Input Presensi -->
        <div class="card-siata">
            <form method="POST" action="presensi.php?tab=input&tanggal=<?= e($f_tanggal) ?>&sesi=<?= urlencode($f_sesi) ?>&halaqah_id=<?= $f_halaqah_id ?>&angkatan=<?= urlencode($f_angkatan) ?>">
                <input type="hidden" name="aksi" value="simpan_presensi">
                <input type="hidden" name="tanggal" value="<?= e($f_tanggal) ?>">
                <input type="hidden" name="sesi_halaqah" value="<?= e($f_sesi) ?>">
                <input type="hidden" name="halaqah_id" value="<?= $f_halaqah_id ?>">
                <input type="hidden" name="angkatan" value="<?= e($f_angkatan) ?>">

                <div class="card-header-siata">
                    <h5>
                        <i class="fa-solid fa-clipboard-user"></i>
                        Input Presensi: <?= e($f_sesi) ?> (<?= e($f_tanggal) ?>)
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning btn-sm fw-semibold" id="btnSetSemuaHadir">
                            <i class="fa-solid fa-check-double me-1"></i> Set Semua Hadir
                        </button>
                        <button type="submit" class="btn btn-success btn-sm fw-semibold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Presensi
                        </button>
                    </div>
                </div>

                <div class="p-3 table-responsive">
                    <table class="table table-hover table-bordered table-siata align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 45px;">No</th>
                                <th>No. Santri</th>
                                <th>Nama Santri</th>
                                <th>Angkatan &amp; Halaqah</th>
                                <th class="text-center" style="width: 310px;">Status Kehadiran</th>
                                <th style="width: 130px;">Waktu Hadir</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($s = mysqli_fetch_assoc($res_input)): ?>
                            <?php
                                $sid    = (int) $s['santri_id'];
                                $st_now = $s['status_kehadiran'] ?: 'Hadir';
                                $def_wk = $waktu_default_sesi[$f_sesi] ?? '04:30';
                                $wk_now = $s['waktu_hadir'] ? substr($s['waktu_hadir'], 0, 5) : $def_wk;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-semibold"><?= e($s['nis']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($s['nama_santri']) ?></div>
                                    <input type="hidden" name="pengajar_id[<?= $sid ?>]" value="<?= (int) $s['pengajar_id'] ?>">
                                </td>
                                <td>
                                    <div class="small text-dark fw-medium"><?= e($s['tahun_angkatan'] ?? '-') ?></div>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= e($s['nama_halaqah'] ?? '-') ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check radio-hadir" name="status_kehadiran[<?= $sid ?>]" id="hadir_<?= $sid ?>" value="Hadir" <?= $st_now === 'Hadir' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success" for="hadir_<?= $sid ?>">Hadir</label>

                                        <input type="radio" class="btn-check" name="status_kehadiran[<?= $sid ?>]" id="sakit_<?= $sid ?>" value="Sakit" <?= $st_now === 'Sakit' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-warning" for="sakit_<?= $sid ?>">Sakit</label>

                                        <input type="radio" class="btn-check" name="status_kehadiran[<?= $sid ?>]" id="izin_<?= $sid ?>" value="Izin" <?= $st_now === 'Izin' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-info" for="izin_<?= $sid ?>">Izin</label>

                                        <input type="radio" class="btn-check" name="status_kehadiran[<?= $sid ?>]" id="alpa_<?= $sid ?>" value="Alpa" <?= $st_now === 'Alpa' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger" for="alpa_<?= $sid ?>">Alpa</label>
                                    </div>
                                </td>
                                <td>
                                    <input type="time" name="waktu_hadir[<?= $sid ?>]" class="form-control form-control-sm" value="<?= e($wk_now) ?>">
                                </td>
                                <td>
                                    <input type="text" name="keterangan[<?= $sid ?>]" class="form-control form-control-sm"
                                           placeholder="Catatan (opsional)" value="<?= e($s['keterangan'] ?? '') ?>">
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-light border-top text-end">
                    <button type="submit" class="btn btn-siata">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Seluruh Presensi <?= e($f_sesi) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================================================================
         TAB 2: REKAP ABSENSI SANTRI
         ====================================================================== -->
    <div class="tab-pane fade <?= $active_tab === 'rekap' ? 'show active' : '' ?>" id="paneRekapPresensi" role="tabpanel">
        <div class="card-siata p-3 mb-3">
            <form method="GET" action="presensi.php" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="rekap">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Bulan Rekap</label>
                    <input type="month" name="bulan" class="form-control form-control-sm" value="<?= e($f_bulan) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tanggal (Opsional)</label>
                    <input type="date" name="rekap_tanggal" class="form-control form-control-sm" value="<?= e($f_rekap_tgl) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Filter Sesi (1–7)</label>
                    <select name="rekap_sesi" class="form-select form-select-sm">
                        <option value="">-- Semua Sesi (1–7) --</option>
                        <?php foreach ($daftar_sesi as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($f_rekap_sesi === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($role !== 'orang_tua'): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tahun Angkatan</label>
                    <select name="angkatan" class="form-select form-select-sm">
                        <option value="">-- Semua Angkatan --</option>
                        <?php foreach ($daftar_angkatan as $angk): ?>
                            <option value="<?= e($angk) ?>" <?= ($f_angkatan === $angk) ? 'selected' : '' ?>><?= e($angk) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Kelompok Musyrif</label>
                    <select name="halaqah_id" class="form-select form-select-sm">
                        <option value="0">-- Semua Musyrif --</option>
                        <?php foreach ($list_halaqah as $hlq): ?>
                            <option value="<?= (int) $hlq['id'] ?>" <?= ($f_halaqah_id === (int) $hlq['id']) ? 'selected' : '' ?>>
                                <?= e($hlq['nama_halaqah']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-siata btn-sm w-100" title="Terapkan Filter"><i class="fa-solid fa-filter"></i></button>
                </div>
            </form>
        </div>

        <div class="card-siata">
            <div class="card-header-siata">
                <h5><i class="fa-solid fa-chart-pie"></i> Rekapitulasi Kehadiran Halaqah Santri (Periode: <?= e($f_bulan) ?>)</h5>
            </div>
            <div class="p-3 table-responsive">
                <table class="table table-hover table-striped table-siata datatable-export align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Santri</th>
                            <th>Nama Santri</th>
                            <th>Kelompok Halaqah</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Sakit</th>
                            <th class="text-center">Izin</th>
                            <th class="text-center">Alpa</th>
                            <th class="text-center">Total Sesi</th>
                            <th style="width: 190px;">% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($rk = mysqli_fetch_assoc($res_rekap)): ?>
                        <?php
                            $tot   = (int) $rk['total_pertemuan'];
                            $hadir = (int) $rk['jml_hadir'];
                            $pct   = $tot > 0 ? round(($hadir / $tot) * 100, 1) : 100.0;
                            $bar_c = $pct >= 85 ? 'bg-success' : ($pct >= 70 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= e($rk['nis']) ?></td>
                            <td class="fw-bold text-dark"><?= e($rk['nama_santri']) ?></td>
                            <td><?= e($rk['nama_halaqah'] ?? '-') ?></td>
                            <td class="text-center"><span class="badge bg-success"><?= $hadir ?></span></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?= (int) $rk['jml_sakit'] ?></span></td>
                            <td class="text-center"><span class="badge bg-info text-dark"><?= (int) $rk['jml_izin'] ?></span></td>
                            <td class="text-center"><span class="badge bg-danger"><?= (int) $rk['jml_alpa'] ?></span></td>
                            <td class="text-center fw-bold"><?= $tot ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $bar_c ?>" style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <span class="small fw-bold"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnSetAll = document.getElementById('btnSetSemuaHadir');
    if (btnSetAll) {
        btnSetAll.addEventListener('click', function () {
            document.querySelectorAll('.radio-hadir').forEach(function (r) {
                r.checked = true;
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
