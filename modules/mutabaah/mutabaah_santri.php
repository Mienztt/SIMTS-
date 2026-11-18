<?php
/**
 * FILE: modules/mutabaah/mutabaah_santri.php
 * Modul Input Checklist Mutaba'ah Harian & Rekapitulasi Mutaba'ah Yaumiyah (2 Tab)
 * Tabel: mutabaah_santri (id, santri_id, tanggal, master_mutabaah_id, status_pelaksanaan, jumlah_capaian, keterangan, diinput_oleh)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Isi & Rekap Mutaba\'ah Santri';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$nama_user  = $_SESSION['nama_lengkap'] ?? 'Musyrif / Pengajar';
$active_tab = $_GET['tab'] ?? ($role === 'orang_tua' ? 'rekap' : 'input');
$alert_msg  = '';
$alert_type = 'success';

// Filter Santri berdasarkan Role (Jika Orang Tua Santri, hanya anak miliknya)
$where_ortu = ($role === 'orang_tua') ? "AND s.id_user_orang_tua = $user_id" : "";
$list_santri = [];
$list_santri_ids = [];
$res_s = mysqli_query($conn, "SELECT s.id, s.nis, s.nama_santri, h.nama_halaqah FROM santri s LEFT JOIN halaqah h ON s.halaqah_id = h.id WHERE s.status_santri='Aktif' $where_ortu ORDER BY s.id ASC");
while ($r = mysqli_fetch_assoc($res_s)) {
    $list_santri[] = $r;
    $list_santri_ids[] = (int) $r['id'];
}

$f_santri_id = (int) ($_GET['santri_id'] ?? ($list_santri[0]['id'] ?? 1));
if ($role === 'orang_tua' && !empty($list_santri_ids) && !in_array($f_santri_id, $list_santri_ids, true)) {
    $f_santri_id = $list_santri_ids[0];
}
$f_tanggal   = $_GET['tanggal'] ?? date('Y-m-d');
$f_bulan     = $_GET['bulan'] ?? date('Y-m');

// Proses Simpan Checklist Mutaba'ah Harian (Khusus Admin & Pengajar / Musyrif / Petugas Rekap)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'simpan_mutabaah') {
    cek_role(['admin', 'pengajar']);
    $p_santri_id = (int) ($_POST['santri_id'] ?? 1);
    $p_tanggal   = $_POST['tanggal'] ?: date('Y-m-d');
    $status_arr  = $_POST['status_pelaksanaan'] ?? [];
    $capaian_arr = $_POST['jumlah_capaian'] ?? [];
    $ket_arr     = $_POST['keterangan'] ?? [];

    $jml_simpan = 0;
    foreach ($status_arr as $master_id => $status_pel) {
        $master_id  = (int) $master_id;
        $status_pel = in_array($status_pel, ['Ya', 'Tidak', 'Uzur'], true) ? $status_pel : 'Ya';
        $capaian    = trim($capaian_arr[$master_id] ?? 'Tuntas');
        $ket        = trim($ket_arr[$master_id] ?? '');

        $cek = mysqli_prepare($conn, "SELECT id FROM mutabaah_santri WHERE santri_id=? AND tanggal=? AND master_mutabaah_id=? LIMIT 1");
        mysqli_stmt_bind_param($cek, 'isi', $p_santri_id, $p_tanggal, $master_id);
        mysqli_stmt_execute($cek);
        $res_c = mysqli_stmt_get_result($cek);

        if ($row_c = mysqli_fetch_assoc($res_c)) {
            $id_ms = (int) $row_c['id'];
            $upd = mysqli_prepare($conn, "UPDATE mutabaah_santri SET status_pelaksanaan=?, jumlah_capaian=?, keterangan=?, diinput_oleh=? WHERE id=?");
            mysqli_stmt_bind_param($upd, 'ssssi', $status_pel, $capaian, $ket, $nama_user, $id_ms);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
        } else {
            $ins = mysqli_prepare($conn, "INSERT INTO mutabaah_santri (santri_id, tanggal, master_mutabaah_id, status_pelaksanaan, jumlah_capaian, keterangan, diinput_oleh) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($ins, 'isissss', $p_santri_id, $p_tanggal, $master_id, $status_pel, $capaian, $ket, $nama_user);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($cek);
        $jml_simpan++;
    }

    $f_santri_id = $p_santri_id;
    $f_tanggal   = $p_tanggal;
    $alert_msg   = "Checklist Mutaba'ah Yaumiyah tanggal $p_tanggal berhasil disimpan ($jml_simpan butir amalan)!";
}

// Ambil seluruh item master_mutabaah yang Aktif beserta isian santri pada tanggal terpilih
$esc_tgl = mysqli_real_escape_string($conn, $f_tanggal);
$sql_checklist = "SELECT m.*, ms.status_pelaksanaan, ms.jumlah_capaian, ms.keterangan, ms.diinput_oleh
                  FROM master_mutabaah m
                  LEFT JOIN mutabaah_santri ms ON (ms.master_mutabaah_id = m.id AND ms.santri_id = $f_santri_id AND ms.tanggal = '$esc_tgl')
                  WHERE m.status_aktif = 'Aktif'
                  ORDER BY m.kode_amalan ASC";
$res_checklist = mysqli_query($conn, $sql_checklist);

// Query Rekap Mutaba'ah Yaumiyah per Santri
$esc_bulan = mysqli_real_escape_string($conn, $f_bulan);
$sql_rekap = "SELECT s.id, s.nis, s.nama_santri, h.nama_halaqah,
                     SUM(CASE WHEN ms.status_pelaksanaan = 'Ya' THEN 1 ELSE 0 END) AS jml_ya,
                     SUM(CASE WHEN ms.status_pelaksanaan = 'Tidak' THEN 1 ELSE 0 END) AS jml_tidak,
                     SUM(CASE WHEN ms.status_pelaksanaan = 'Uzur' THEN 1 ELSE 0 END) AS jml_uzur,
                     COALESCE(SUM(CASE WHEN ms.status_pelaksanaan = 'Ya' THEN m.poin_amalan ELSE 0 END), 0) AS total_poin_amalan,
                     COUNT(ms.id) AS total_isian
              FROM santri s
              LEFT JOIN halaqah h ON s.halaqah_id = h.id
              LEFT JOIN mutabaah_santri ms ON (ms.santri_id = s.id AND DATE_FORMAT(ms.tanggal, '%Y-%m') = '$esc_bulan')
              LEFT JOIN master_mutabaah m ON ms.master_mutabaah_id = m.id
              WHERE s.status_santri = 'Aktif' $where_ortu
              GROUP BY s.id
              ORDER BY total_poin_amalan DESC, s.id ASC";
$res_rekap = mysqli_query($conn, $sql_rekap);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-square-check me-2"></i>Mutaba'ah Yaumiyah Santri</h4>
        <small class="text-muted">Pencatatan checklist ibadah harian santri &amp; rekapitulasi persentase ketaatan ibadah</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <a href="master_mutabaah.php" class="btn btn-outline-dark btn-sm">
        <i class="fa-solid fa-gear me-1"></i> Kelola Butir Amalan
    </a>
    <?php endif; ?>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Navigasi 2 Tab -->
<ul class="nav nav-tabs nav-tabs-siata mb-3 bg-white rounded-top px-3 pt-2 border" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'input' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#paneInputMutabaah" type="button" role="tab">
            <i class="fa-solid fa-list-check me-1"></i> Tab 1: <?= ($role === 'orang_tua') ? "Pantau Checklist Mutaba'ah Harian" : "Input Checklist Mutaba'ah Harian" ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'rekap' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#paneRekapMutabaah" type="button" role="tab">
            <i class="fa-solid fa-chart-simple me-1"></i> Tab 2: Rekap Mutaba'ah Yaumiyah
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: INPUT CHECKLIST MUTABA'AH HARIAN -->
    <div class="tab-pane fade <?= $active_tab === 'input' ? 'show active' : '' ?>" id="paneInputMutabaah" role="tabpanel">
        <div class="card-siata p-3 mb-3">
            <form method="GET" action="mutabaah_santri.php" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="input">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Pilih Santri</label>
                    <select name="santri_id" class="form-select form-select-sm" required>
                        <?php foreach ($list_santri as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= ($f_santri_id === (int) $s['id']) ? 'selected' : '' ?>>
                                <?= e($s['nis'] . ' - ' . $s['nama_santri'] . ' (' . ($s['nama_halaqah'] ?? '-') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Tanggal Pelaksanaan</label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($f_tanggal) ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-siata btn-sm w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Buka Checklist
                    </button>
                </div>
            </form>
        </div>

        <div class="card-siata">
            <form method="POST" action="mutabaah_santri.php?tab=input&santri_id=<?= $f_santri_id ?>&tanggal=<?= e($f_tanggal) ?>">
                <input type="hidden" name="aksi" value="simpan_mutabaah">
                <input type="hidden" name="santri_id" value="<?= $f_santri_id ?>">
                <input type="hidden" name="tanggal" value="<?= e($f_tanggal) ?>">

                <div class="card-header-siata">
                    <h5><i class="fa-solid fa-hands-praying"></i> Lembar Checklist Mutaba'ah Harian (<?= e($f_tanggal) ?>)</h5>
                    <?php if ($role === 'orang_tua'): ?>
                    <span class="badge bg-warning text-dark px-3 py-2">
                        <i class="fa-solid fa-eye me-1"></i> Mode Pantau Orang Tua Santri (Read-Only)
                    </span>
                    <?php else: ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning btn-sm fw-semibold" id="btnSetSemuaYa">
                            <i class="fa-solid fa-check-double me-1"></i> Tandai Semua "Ya"
                        </button>
                        <button type="submit" class="btn btn-success btn-sm fw-semibold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Checklist
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="p-3 table-responsive">
                    <table class="table table-hover table-bordered table-siata align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 45px;">No</th>
                                <th>Kode &amp; Nama Amalan</th>
                                <th>Kategori &amp; Target</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center" style="width: 240px;">Status Pelaksanaan</th>
                                <th style="width: 180px;">Jumlah Capaian</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $is_ro = ($role === 'orang_tua') ? 'disabled' : ''; ?>
                            <?php $no = 1; while ($item = mysqli_fetch_assoc($res_checklist)): ?>
                            <?php
                                $mid    = (int) $item['id'];
                                $st_pel = $item['status_pelaksanaan'] ?: 'Ya';
                                $cap    = $item['jumlah_capaian'] ?: $item['target_harian'];
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <span class="badge bg-dark me-1"><?= e($item['kode_amalan']) ?></span>
                                    <span class="fw-bold text-dark"><?= e($item['nama_amalan']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= e($item['kategori']) ?></span>
                                    <small class="text-muted d-block">Target: <?= e($item['target_harian']) ?></small>
                                </td>
                                <td class="text-center"><span class="badge bg-success">+<?= (int) $item['poin_amalan'] ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check radio-ya" name="status_pelaksanaan[<?= $mid ?>]" id="ya_<?= $mid ?>" value="Ya" <?= $st_pel === 'Ya' ? 'checked' : '' ?> <?= $is_ro ?>>
                                        <label class="btn btn-outline-success" for="ya_<?= $mid ?>">Ya</label>

                                        <input type="radio" class="btn-check" name="status_pelaksanaan[<?= $mid ?>]" id="tdk_<?= $mid ?>" value="Tidak" <?= $st_pel === 'Tidak' ? 'checked' : '' ?> <?= $is_ro ?>>
                                        <label class="btn btn-outline-danger" for="tdk_<?= $mid ?>">Tidak</label>

                                        <input type="radio" class="btn-check" name="status_pelaksanaan[<?= $mid ?>]" id="uzr_<?= $mid ?>" value="Uzur" <?= $st_pel === 'Uzur' ? 'checked' : '' ?> <?= $is_ro ?>>
                                        <label class="btn btn-outline-warning" for="uzr_<?= $mid ?>">Uzur</label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="jumlah_capaian[<?= $mid ?>]" class="form-control form-control-sm" value="<?= e($cap) ?>" <?= $is_ro ?> required>
                                </td>
                                <td>
                                    <input type="text" name="keterangan[<?= $mid ?>]" class="form-control form-control-sm"
                                           placeholder="Keterangan..." value="<?= e($item['keterangan'] ?? '') ?>" <?= $is_ro ?>>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($role !== 'orang_tua'): ?>
                <div class="p-3 bg-light border-top text-end">
                    <button type="submit" class="btn btn-siata">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Checklist Mutaba'ah Harian
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- TAB 2: REKAP MUTABA'AH YAUMIYAH -->
    <div class="tab-pane fade <?= $active_tab === 'rekap' ? 'show active' : '' ?>" id="paneRekapMutabaah" role="tabpanel">
        <div class="card-siata p-3 mb-3">
            <form method="GET" action="mutabaah_santri.php" class="row g-3 align-items-end">
                <input type="hidden" name="tab" value="rekap">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Periode Bulan</label>
                    <input type="month" name="bulan" class="form-control form-control-sm" value="<?= e($f_bulan) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-siata btn-sm"><i class="fa-solid fa-filter me-1"></i> Tampilkan Rekap</button>
                </div>
            </form>
        </div>

        <div class="card-siata">
            <div class="card-header-siata">
                <h5><i class="fa-solid fa-chart-line"></i> Rekapitulasi Pelaksanaan Mutaba'ah Yaumiyah (Periode: <?= e($f_bulan) ?>)</h5>
            </div>
            <div class="p-3 table-responsive">
                <table class="table table-hover table-striped table-siata datatable-export align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Santri</th>
                            <th>Nama Santri</th>
                            <th>Halaqah</th>
                            <th class="text-center">Terlaksana (Ya)</th>
                            <th class="text-center">Tidak</th>
                            <th class="text-center">Uzur</th>
                            <th class="text-center">Total Poin Ibadah</th>
                            <th style="width: 190px;">% Pelaksanaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($rk = mysqli_fetch_assoc($res_rekap)): ?>
                        <?php
                            $tot = (int) $rk['total_isian'];
                            $ya  = (int) $rk['jml_ya'];
                            $uzr = (int) $rk['jml_uzur'];
                            $pct = $tot > 0 ? round((($ya + $uzr) / $tot) * 100, 1) : 100.0;
                            $bar = $pct >= 85 ? 'bg-success' : ($pct >= 65 ? 'bg-info' : 'bg-warning');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold"><?= e($rk['nis']) ?></td>
                            <td class="fw-bold text-dark"><?= e($rk['nama_santri']) ?></td>
                            <td><?= e($rk['nama_halaqah'] ?? '-') ?></td>
                            <td class="text-center"><span class="badge bg-success"><?= $ya ?></span></td>
                            <td class="text-center"><span class="badge bg-danger"><?= (int) $rk['jml_tidak'] ?></span></td>
                            <td class="text-center"><span class="badge bg-warning text-dark"><?= $uzr ?></span></td>
                            <td class="text-center"><span class="badge bg-primary px-2 py-1"><?= (int) $rk['total_poin_amalan'] ?> Poin</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $bar ?>" style="width: <?= $pct ?>%"></div>
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
    const btnAllYa = document.getElementById('btnSetSemuaYa');
    if (btnAllYa) {
        btnAllYa.addEventListener('click', function () {
            document.querySelectorAll('.radio-ya').forEach(function (r) {
                r.checked = true;
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
