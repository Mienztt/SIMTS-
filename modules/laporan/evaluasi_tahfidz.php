<?php
/**
 * FILE: modules/laporan/evaluasi_tahfidz.php
 * Modul Laporan Evaluasi Ziyadah & Muraja'ah (Filter Periode/Halaqah + Grafik Chart.js + Tabel Export PDF/Excel/Print)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$f_jenis   = in_array($_GET['jenis'] ?? '', ['Ziyadah', 'Murajaah', 'Semua'], true) ? $_GET['jenis'] : 'Ziyadah';
$f_halaqah = (int) ($_GET['halaqah_id'] ?? 0);
$f_tgl_1   = $_GET['tgl_awal'] ?? date('Y-01-01');
$f_tgl_2   = $_GET['tgl_akhir'] ?? date('Y-12-31');

$page_title = 'Laporan Evaluasi ' . ($f_jenis === 'Murajaah' ? "Muroja'ah" : $f_jenis);
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);

// Ambil Daftar Halaqah untuk Filter
$list_halaqah = [];
$res_hlq = mysqli_query($conn, "SELECT id, kode_halaqah, nama_halaqah FROM halaqah ORDER BY kode_halaqah ASC");
while ($r = mysqli_fetch_assoc($res_hlq)) {
    $list_halaqah[] = $r;
}

// Query Rekapitulasi Evaluasi per Santri
$esc_t1     = mysqli_real_escape_string($conn, $f_tgl_1);
$esc_t2     = mysqli_real_escape_string($conn, $f_tgl_2);
$where_hlq  = $f_halaqah > 0 ? "AND s.halaqah_id = $f_halaqah" : "";
$where_ortu = ($role === 'orang_tua') ? "AND s.id_user_orang_tua = $user_id" : "";

$sql_eval = "SELECT s.id, s.nis, s.nama_santri, s.target_juz, s.total_hafalan_juz,
                    h.nama_halaqah, p.nama_pengajar,
                    COALESCE(SUM(CASE WHEN st.jenis_setoran='Ziyadah' AND st.tanggal BETWEEN '$esc_t1' AND '$esc_t2' THEN st.jumlah_halaman ELSE 0 END), 0) AS hal_ziyadah,
                    COALESCE(SUM(CASE WHEN st.jenis_setoran='Murajaah' AND st.tanggal BETWEEN '$esc_t1' AND '$esc_t2' THEN st.jumlah_halaman ELSE 0 END), 0) AS hal_murajaah,
                    COALESCE(AVG(CASE WHEN st.tanggal BETWEEN '$esc_t1' AND '$esc_t2' THEN (st.nilai_kelancaran + st.nilai_tajwid + st.nilai_makhraj)/3 END), 90) AS rata_nilai,
                    (SELECT COUNT(*) FROM ujian_tasmi ut WHERE ut.santri_id = s.id AND ut.status_lulus = 'Lulus') AS jml_tasmi_lulus
             FROM santri s
             LEFT JOIN halaqah h ON s.halaqah_id = h.id
             LEFT JOIN pengajar p ON s.pengajar_id = p.id
             LEFT JOIN setoran_tahfidz st ON st.santri_id = s.id
             WHERE s.status_santri = 'Aktif' $where_hlq $where_ortu
             GROUP BY s.id
             ORDER BY hal_ziyadah DESC, s.total_hafalan_juz DESC, s.id ASC";
$res_eval = mysqli_query($conn, $sql_eval);

$rows_eval   = [];
$chart_names = [];
$chart_ziy   = [];
$chart_mur   = [];
$total_ziy_all = 0;
$total_mur_all = 0;

while ($r = mysqli_fetch_assoc($res_eval)) {
    $rows_eval[]    = $r;
    $total_ziy_all += (float) $r['hal_ziyadah'];
    $total_mur_all += (float) $r['hal_murajaah'];
    if (count($chart_names) < 10) {
        $chart_names[] = $r['nama_santri'];
        $chart_ziy[]   = (float) $r['hal_ziyadah'];
        $chart_mur[]   = (float) $r['hal_murajaah'];
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;">
            <i class="fa-solid fa-chart-pie me-2"></i>Laporan Evaluasi <?= $f_jenis === 'Murajaah' ? "Muroja'ah" : e($f_jenis) ?> Santri
        </h4>
        <small class="text-muted">Analisis grafik capaian Ziyadah vs Muraja'ah serta rekapitulasi siap cetak (PDF / Excel / Print)</small>
    </div>
    <div class="btn-group">
        <a href="evaluasi_tahfidz.php?jenis=Ziyadah" class="btn btn-sm <?= $f_jenis === 'Ziyadah' ? 'btn-siata' : 'btn-outline-secondary bg-white' ?>">
            Evaluasi Ziyadah
        </a>
        <a href="evaluasi_tahfidz.php?jenis=Murajaah" class="btn btn-sm <?= $f_jenis === 'Murajaah' ? 'btn-siata' : 'btn-outline-secondary bg-white' ?>">
            Evaluasi Muroja'ah
        </a>
        <a href="evaluasi_tahfidz.php?jenis=Semua" class="btn btn-sm <?= $f_jenis === 'Semua' ? 'btn-siata' : 'btn-outline-secondary bg-white' ?>">
            Gabungan Semua
        </a>
    </div>
</div>

<!-- Form Filter Periode & Halaqah -->
<div class="card-siata p-3 mb-3">
    <form method="GET" action="evaluasi_tahfidz.php" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Fokus Evaluasi</label>
            <select name="jenis" class="form-select form-select-sm">
                <option value="Ziyadah" <?= $f_jenis === 'Ziyadah' ? 'selected' : '' ?>>Evaluasi Ziyadah</option>
                <option value="Murajaah" <?= $f_jenis === 'Murajaah' ? 'selected' : '' ?>>Evaluasi Muroja'ah</option>
                <option value="Semua" <?= $f_jenis === 'Semua' ? 'selected' : '' ?>>Ziyadah &amp; Muroja'ah</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Kelompok Halaqah</label>
            <select name="halaqah_id" class="form-select form-select-sm">
                <option value="0">-- Semua Halaqah --</option>
                <?php foreach ($list_halaqah as $hlq): ?>
                    <option value="<?= (int) $hlq['id'] ?>" <?= ($f_halaqah === (int) $hlq['id']) ? 'selected' : '' ?>>
                        <?= e($hlq['kode_halaqah'] . ' - ' . $hlq['nama_halaqah']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
            <input type="date" name="tgl_awal" class="form-control form-control-sm" value="<?= e($f_tgl_1) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
            <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= e($f_tgl_2) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-siata btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
        </div>
    </form>
</div>

<!-- Ringkasan Grafik Chart.js & Kartu Statistik Evaluasi -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card-siata h-100 mb-0">
            <div class="card-header-siata">
                <h6><i class="fa-solid fa-chart-column"></i> Grafik Perbandingan Capaian Ziyadah vs Muraja'ah (10 Santri Teratas)</h6>
            </div>
            <div class="p-3">
                <canvas id="chartEvaluasiTahfidz" height="115"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-siata h-100 mb-0">
            <div class="card-header-siata">
                <h6><i class="fa-solid fa-award"></i> Ringkasan Periode</h6>
            </div>
            <div class="p-4">
                <div class="p-3 rounded-3 mb-3" style="background:#ecfdf5;border-left:4px solid #10b981;">
                    <div class="small text-muted">Total Setoran Ziyadah</div>
                    <div class="fs-3 fw-bold text-success"><?= number_format($total_ziy_all, 1) ?> Halaman</div>
                </div>
                <div class="p-3 rounded-3 mb-3" style="background:#fefce8;border-left:4px solid #eab308;">
                    <div class="small text-muted">Total Setoran Muraja'ah</div>
                    <div class="fs-3 fw-bold text-warning-emphasis"><?= number_format($total_mur_all, 1) ?> Halaman</div>
                </div>
                <div class="p-3 rounded-3" style="background:#f1f5f9;border-left:4px solid #0b2220;">
                    <div class="small text-muted">Jumlah Santri Dievaluasi</div>
                    <div class="fs-4 fw-bold text-dark"><?= count($rows_eval) ?> Santri Aktif</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Rekapitulasi Siap Cetak (Export Excel / PDF / Print) -->
<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-table"></i> Tabel Rekapitulasi Evaluasi Tahfidz Santri</h5>
        <span class="badge bg-warning text-dark">Periode: <?= e($f_tgl_1) ?> s/d <?= e($f_tgl_2) ?></span>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-export align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Santri</th>
                    <th>Nama Santri</th>
                    <th>Kelompok Halaqah</th>
                    <th>Muhaffizh</th>
                    <th class="text-center">Total Hafalan</th>
                    <th class="text-center">Ziyadah (Hal)</th>
                    <th class="text-center">Muroja'ah (Hal)</th>
                    <th class="text-center">Lulus Tasmi'</th>
                    <th class="text-center">Rata-Rata Nilai</th>
                    <th>Predikat Evaluasi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($rows_eval as $row): ?>
                <?php
                    $avg = round((float) $row['rata_nilai'], 1);
                    $pred = $avg >= 90 ? 'Mumtaz' : ($avg >= 80 ? 'Jayyid Jiddan' : 'Jayyid');
                    $badge_p = $pred === 'Mumtaz' ? 'bg-success' : ($pred === 'Jayyid Jiddan' ? 'bg-primary' : 'bg-info text-dark');
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-semibold"><?= e($row['nis']) ?></td>
                    <td class="fw-bold text-dark"><?= e($row['nama_santri']) ?></td>
                    <td><?= e($row['nama_halaqah'] ?? '-') ?></td>
                    <td><?= e($row['nama_pengajar'] ?? '-') ?></td>
                    <td class="text-center"><span class="fw-bold text-success"><?= (float) $row['total_hafalan_juz'] ?> Juz</span> / <?= (int) $row['target_juz'] ?> Juz</td>
                    <td class="text-center fw-bold text-success"><?= number_format((float) $row['hal_ziyadah'], 1) ?> Hal</td>
                    <td class="text-center fw-bold text-warning-emphasis"><?= number_format((float) $row['hal_murajaah'], 1) ?> Hal</td>
                    <td class="text-center"><span class="badge bg-dark"><?= (int) $row['jml_tasmi_lulus'] ?>x Lulus</span></td>
                    <td class="text-center fw-bold"><?= $avg ?></td>
                    <td><span class="badge <?= $badge_p ?>"><?= $pred ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js 4.4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('chartEvaluasiTahfidz');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_names) ?>,
                datasets: [
                    {
                        label: 'Capaian Ziyadah (Halaman)',
                        data: <?= json_encode($chart_ziy) ?>,
                        backgroundColor: '#145345',
                        borderRadius: 6
                    },
                    {
                        label: 'Capaian Muroja\'ah (Halaman)',
                        data: <?= json_encode($chart_mur) ?>,
                        backgroundColor: '#eab308',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
