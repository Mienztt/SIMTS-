<?php
/**
 * FILE: modules/pelanggaran/pelanggaran_rekap.php
 * Modul Rekap Akumulasi Poin Kedisiplinan & Logika Status Sanksi Otomatis
 * Logika Status Sanksi:
 *   0 - 14 Poin  = Badge Hijau "Aman"
 *  15 - 29 Poin  = Badge Biru/Info "Teguran Lisan"
 *  30 - 49 Poin  = Badge Kuning "Kartu Kuning / SP-1"
 *  >= 50 Poin    = Badge Merah "Kartu Merah / Evaluasi Beasiswa"
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title = 'Rekap Pelanggaran & Poin Kedisiplinan';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);

$where_ortu = ($role === 'orang_tua') ? "AND s.id_user_orang_tua = $user_id" : "";

$sql_rekap = "SELECT s.id, s.nis, s.nama_santri, s.nama_orang_tua, s.no_hp_orang_tua, h.nama_halaqah, p.nama_pengajar,
                     COUNT(ps.id) AS jml_kasus,
                     COALESCE(SUM(ps.poin_saat_itu), 0) AS total_poin,
                     MAX(ps.tanggal) AS tgl_terakhir
              FROM santri s
              LEFT JOIN halaqah h ON s.halaqah_id = h.id
              LEFT JOIN pengajar p ON s.pengajar_id = p.id
              LEFT JOIN pelanggaran_santri ps ON ps.santri_id = s.id
              WHERE s.status_santri = 'Aktif' $where_ortu
              GROUP BY s.id
              ORDER BY total_poin DESC, s.id ASC";
$res_rekap = mysqli_query($conn, $sql_rekap);

$data_rekap = [];
$jml_aman   = 0;
$jml_tegur  = 0;
$jml_sp1    = 0;
$jml_merah  = 0;

while ($r = mysqli_fetch_assoc($res_rekap)) {
    $poin = (int) $r['total_poin'];
    if ($poin >= 50) {
        $r['status_sanksi'] = 'Kartu Merah / Evaluasi Beasiswa';
        $r['badge_class']   = 'bg-danger';
        $r['rekomendasi']   = 'Kartu Merah, pemanggilan Orang Tua Santri, serta evaluasi oleh dewan asatidz dan pimpinan pesantren';
        $jml_merah++;
    } elseif ($poin >= 30) {
        $r['status_sanksi'] = 'Kartu Kuning / SP-1';
        $r['badge_class']   = 'bg-warning text-dark';
        $r['rekomendasi']   = 'Pemanggilan oleh musyrif, pembinaan kedisiplinan, dan Kartu Kuning / SP-1';
        $jml_sp1++;
    } elseif ($poin >= 15) {
        $r['status_sanksi'] = 'Teguran Lisan';
        $r['badge_class']   = 'bg-info text-dark';
        $r['rekomendasi']   = 'Teguran lisan dan tugas pembinaan oleh musyrif';
        $jml_tegur++;
    } else {
        $r['status_sanksi'] = 'Aman';
        $r['badge_class']   = 'bg-success';
        $r['rekomendasi']   = 'Pertahankan Adab & Kedisiplinan Santri';
        $jml_aman++;
    }
    $data_rekap[] = $r;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-shield-halved me-2"></i>Rekapitulasi Poin Kedisiplinan &amp; Status Sanksi</h4>
        <small class="text-muted">Akumulasi poin pelanggaran santri dengan penentuan status sanksi otomatis berbasis ambang batas</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <a href="pelanggaran_input.php" class="btn btn-siata">
        <i class="fa-solid fa-plus-circle me-1"></i> Input Pelanggaran Baru
    </a>
    <?php endif; ?>
</div>

<!-- 4 Kartu Indikator Ambang Batas Status Sanksi Otomatis -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card-siata p-3 mb-0 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold">0 – 14 Poin</div>
                    <div class="fw-bold text-success">Aman</div>
                </div>
                <span class="fs-3 fw-bold text-success"><?= $jml_aman ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-siata p-3 mb-0 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold">15 – 29 Poin</div>
                    <div class="fw-bold text-info-emphasis">Teguran Lisan</div>
                </div>
                <span class="fs-3 fw-bold text-info-emphasis"><?= $jml_tegur ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-siata p-3 mb-0 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold">30 – 49 Poin</div>
                    <div class="fw-bold text-warning-emphasis">Kartu Kuning / SP-1</div>
                </div>
                <span class="fs-3 fw-bold text-warning-emphasis"><?= $jml_sp1 ?></span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card-siata p-3 mb-0 border-start border-4 border-danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="small text-muted fw-semibold">&ge; 50 Poin</div>
                    <div class="fw-bold text-danger">Kartu Merah / Evaluasi Beasiswa</div>
                </div>
                <span class="fs-3 fw-bold text-danger"><?= $jml_merah ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Rekapitulasi Akumulasi Poin Kedisiplinan -->
<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-table-list"></i> Tabel Akumulasi Poin Pelanggaran &amp; Status Sanksi Santri</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-export align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Santri</th>
                    <th>Nama Santri</th>
                    <th>Halaqah &amp; Muhaffizh</th>
                    <th class="text-center">Frekuensi Kasus</th>
                    <th class="text-center">Total Akumulasi Poin</th>
                    <th>Status Sanksi Otomatis</th>
                    <th>Tindak Lanjut / Rekomendasi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($data_rekap as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-semibold"><?= e($row['nis']) ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted">Orang Tua: <?= e($row['nama_orang_tua']) ?> (<?= e($row['no_hp_orang_tua']) ?>)</small>
                    </td>
                    <td>
                        <div class="fw-medium"><?= e($row['nama_halaqah'] ?? '-') ?></div>
                        <small class="text-muted"><?= e($row['nama_pengajar'] ?? '-') ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?= (int) $row['jml_kasus'] ?>x Pelanggaran</span>
                    </td>
                    <td class="text-center">
                        <span class="fs-6 fw-bold <?= (int) $row['total_poin'] >= 30 ? 'text-danger' : ((int) $row['total_poin'] >= 15 ? 'text-warning-emphasis' : 'text-success') ?>">
                            <?= (int) $row['total_poin'] ?> Poin
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= e($row['badge_class']) ?> px-3 py-2">
                            <?= e($row['status_sanksi']) ?>
                        </span>
                    </td>
                    <td><small class="text-secondary"><?= e($row['rekomendasi']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
