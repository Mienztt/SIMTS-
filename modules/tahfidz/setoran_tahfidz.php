<?php
/**
 * FILE: modules/tahfidz/setoran_tahfidz.php
 * Modul Setoran Hafalan Harian (Ziyadah & Muraja'ah) + Deteksi Otomatis Kelipatan 10 Halaman untuk Ujian Tasmi'
 * Tabel: setoran_tahfidz (id, santri_id, pengajar_id, tanggal, jenis_setoran, juz, surah_awal, ayat_awal, surah_akhir, ayat_akhir, jumlah_halaman, jumlah_baris, nilai_kelancaran, nilai_tajwid, nilai_makhraj, predikat, catatan)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$page_title   = 'Setoran Tahfidz, Hadis & Kitab';
$role         = $_SESSION['role'] ?? 'admin';
$user_id      = (int) ($_SESSION['user_id'] ?? 0);
$alert_msg    = '';
$alert_type   = 'success';
$tasmi_notice = '';

$daftar_jenis_setoran = [
    'Tahsin Dasar',
    'Ziyadah',
    'Murajaah',
    'Hafalan Hadis & Kitab',
    'Evaluasi Tertutup 10 Halaman',
    'Ujian Tasmi 5 Juz'
];

// Proses CRUD Setoran Tahfidz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    cek_role(['admin', 'pengajar']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $id               = (int) ($_POST['id'] ?? 0);
        $santri_id        = (int) ($_POST['santri_id'] ?? 1);
        $pengajar_id      = (int) ($_POST['pengajar_id'] ?? 1);
        $tanggal          = $_POST['tanggal'] ?: date('Y-m-d');
        $jenis_setoran    = in_array($_POST['jenis_setoran'] ?? '', $daftar_jenis_setoran, true) ? $_POST['jenis_setoran'] : 'Ziyadah';
        $juz              = (int) ($_POST['juz'] ?? 30);
        $surah_awal       = trim($_POST['surah_awal'] ?? '');
        $ayat_awal        = (int) ($_POST['ayat_awal'] ?? 1);
        $surah_akhir      = trim($_POST['surah_akhir'] ?? '');
        $ayat_akhir       = (int) ($_POST['ayat_akhir'] ?? 10);
        $jumlah_halaman   = (float) ($_POST['jumlah_halaman'] ?? 1.0);
        $jumlah_baris     = (int) ($_POST['jumlah_baris'] ?? 15);
        $nilai_kelancaran = (int) ($_POST['nilai_kelancaran'] ?? 90);
        $nilai_tajwid     = (int) ($_POST['nilai_tajwid'] ?? 90);
        $nilai_makhraj    = (int) ($_POST['nilai_makhraj'] ?? 90);

        $rata_nilai = ($nilai_kelancaran + $nilai_tajwid + $nilai_makhraj) / 3;
        if (!empty($_POST['predikat']) && in_array($_POST['predikat'], ['Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul', 'Rosib'], true)) {
            $predikat = $_POST['predikat'];
        } else {
            $predikat = $rata_nilai >= 90 ? 'Mumtaz' : ($rata_nilai >= 80 ? 'Jayyid Jiddan' : ($rata_nilai >= 70 ? 'Jayyid' : ($rata_nilai >= 60 ? 'Maqbul' : 'Rosib')));
        }
        $catatan = trim($_POST['catatan'] ?? '');

        if ($aksi === 'tambah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO setoran_tahfidz (santri_id, pengajar_id, tanggal, jenis_setoran, juz, surah_awal, ayat_awal, surah_akhir, ayat_akhir, jumlah_halaman, jumlah_baris, nilai_kelancaran, nilai_tajwid, nilai_makhraj, predikat, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'iissisisidiiiiss', $santri_id, $pengajar_id, $tanggal, $jenis_setoran, $juz, $surah_awal, $ayat_awal, $surah_akhir, $ayat_akhir, $jumlah_halaman, $jumlah_baris, $nilai_kelancaran, $nilai_tajwid, $nilai_makhraj, $predikat, $catatan);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Setoran $jenis_setoran berhasil disimpan!";

                // Cek akumulasi Ziyadah santri apakah mencapai kelipatan 10 Halaman (Evaluasi Tertutup)
                if ($jenis_setoran === 'Ziyadah') {
                    $q_akum = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah_halaman),0) AS tot_hal, (SELECT nama_santri FROM santri WHERE id=$santri_id) AS nm FROM setoran_tahfidz WHERE santri_id=$santri_id AND jenis_setoran='Ziyadah'");
                    if ($r_akum = mysqli_fetch_assoc($q_akum)) {
                        $tot_hal = (float) $r_akum['tot_hal'];
                        if ($tot_hal >= 10 && fmod($tot_hal, 10.0) < $jumlah_halaman) {
                            $tasmi_notice = "Alhamdulillah! Akumulasi Ziyadah santri <strong>" . e($r_akum['nm']) . "</strong> telah mencapai <strong>" . $tot_hal . " Halaman</strong> (Kelipatan 10 Halaman). Santri wajib mengikuti <strong>Evaluasi Tertutup 10 Halaman</strong> bersama musyrif sebelum melanjutkan halaman baru.";
                        }
                    }
                }
            } else {
                $alert_msg  = 'Gagal menyimpan setoran: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE setoran_tahfidz SET santri_id=?, pengajar_id=?, tanggal=?, jenis_setoran=?, juz=?, surah_awal=?, ayat_awal=?, surah_akhir=?, ayat_akhir=?, jumlah_halaman=?, jumlah_baris=?, nilai_kelancaran=?, nilai_tajwid=?, nilai_makhraj=?, predikat=?, catatan=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iissisisidiiiissi', $santri_id, $pengajar_id, $tanggal, $jenis_setoran, $juz, $surah_awal, $ayat_awal, $surah_akhir, $ayat_akhir, $jumlah_halaman, $jumlah_baris, $nilai_kelancaran, $nilai_tajwid, $nilai_makhraj, $predikat, $catatan, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = 'Data setoran tahfidz berhasil diperbarui!';
            } else {
                $alert_msg  = 'Gagal memperbarui setoran: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM setoran_tahfidz WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Riwayat setoran berhasil dihapus!';
    }
}

// Ambil Daftar Santri & Pengajar
$where_ortu = ($role === 'orang_tua') ? "AND s.id_user_orang_tua = $user_id" : "";
$list_santri = [];
$res_s = mysqli_query($conn, "SELECT id, nis, nama_santri, pengajar_id, total_hafalan_juz FROM santri s WHERE status_santri='Aktif' $where_ortu ORDER BY nama_santri ASC");
while ($r = mysqli_fetch_assoc($res_s)) {
    $list_santri[] = $r;
}

$list_pengajar = [];
$res_p = mysqli_query($conn, "SELECT id, nama_pengajar FROM pengajar WHERE status='Aktif' ORDER BY nama_pengajar ASC");
while ($r = mysqli_fetch_assoc($res_p)) {
    $list_pengajar[] = $r;
}

// Hitung Akumulasi Ziyadah per Santri untuk Indikator Evaluasi Tertutup 10 Halaman & Kelipatan 5 Juz
$akumulasi_ziyadah = [];
$santri_siap_tasmi = [];
$sql_akum = "SELECT st.santri_id, s.nama_santri, s.nis, s.total_hafalan_juz, SUM(st.jumlah_halaman) AS total_ziyadah
             FROM setoran_tahfidz st
             JOIN santri s ON st.santri_id = s.id
             WHERE st.jenis_setoran = 'Ziyadah' $where_ortu
             GROUP BY st.santri_id";
$res_akum = mysqli_query($conn, $sql_akum);
while ($ra = mysqli_fetch_assoc($res_akum)) {
    $sid = (int) $ra['santri_id'];
    $tot = (float) $ra['total_ziyadah'];
    $akumulasi_ziyadah[$sid] = $tot;
    if ($tot >= 10) {
        $santri_siap_tasmi[] = $ra;
    }
}

// Santri siap Tasmi / Imtihan Kelipatan 5 Juz (5, 10, 15, 20, 25, 30 Juz)
$santri_siap_5juz = [];
$res_5juz = mysqli_query($conn, "SELECT s.id, s.nis, s.nama_santri, s.total_hafalan_juz FROM santri s WHERE s.status_santri='Aktif' AND s.total_hafalan_juz >= 5 $where_ortu ORDER BY s.total_hafalan_juz DESC LIMIT 8");
while ($r5 = mysqli_fetch_assoc($res_5juz)) {
    $juz_val = (float) $r5['total_hafalan_juz'];
    if ($juz_val >= 5 && fmod($juz_val, 5.0) == 0.0) {
        $santri_siap_5juz[] = $r5;
    }
}

// Filter Jenis Setoran & Role Orang Tua Santri
$filter_jenis = $_GET['jenis'] ?? '';
$where_parts = ["1=1"];
if ($role === 'orang_tua') {
    $where_parts[] = "s.id_user_orang_tua = $user_id";
}
if (in_array($filter_jenis, $daftar_jenis_setoran, true)) {
    $where_parts[] = "st.jenis_setoran = '" . mysqli_real_escape_string($conn, $filter_jenis) . "'";
}
$where_sql = "WHERE " . implode(' AND ', $where_parts);

$sql_setoran = "SELECT st.*, s.nis, s.nama_santri, s.total_hafalan_juz, p.nama_pengajar
                FROM setoran_tahfidz st
                JOIN santri s ON st.santri_id = s.id
                LEFT JOIN pengajar p ON st.pengajar_id = p.id
                $where_sql
                ORDER BY st.tanggal DESC, st.id DESC";
$res_setoran = mysqli_query($conn, $sql_setoran);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-book-quran me-2"></i>Setoran Tahfidz, Muraja'ah, Hadis &amp; Kitab</h4>
        <small class="text-muted">Pencatatan setoran harian santri dilengkapi deteksi Evaluasi Tertutup 10 Halaman &amp; Tasmi' kelipatan 5 Juz</small>
    </div>
    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahSetoran">
        <i class="fa-solid fa-plus-circle me-1"></i> Input Setoran Baru
    </button>
    <?php endif; ?>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($tasmi_notice !== ''): ?>
<div class="alert alert-warning alert-dismissible fade show shadow-sm border-start border-4 border-warning" role="alert">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="fa-solid fa-award fa-lg text-warning me-2"></i>
            <?= $tasmi_notice ?>
        </div>
        <a href="ujian_tasmi.php" class="btn btn-dark btn-sm">
            <i class="fa-solid fa-arrow-right me-1"></i> Jadwalkan Evaluasi 10 Hal / Tasmi'
        </a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Indikator Otomatis Santri yang Mencapai Kelipatan 10 Halaman Ziyadah & Kelipatan 5 Juz -->
<?php if (!empty($santri_siap_tasmi) || !empty($santri_siap_5juz)): ?>
<div class="card-siata p-3 mb-3 border-start border-4 border-warning" style="background:#fffbeb;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-bell text-warning me-2"></i>Notifikasi Evaluasi Tertutup 10 Halaman &amp; Tasmi' Kelipatan 5 Juz
            </h6>
            <?php if (!empty($santri_siap_tasmi)): ?>
            <div class="small text-secondary mb-1">
                <strong>Evaluasi Tertutup 10 Halaman (Ziyadah &ge; 10 Hal):</strong>
                <?php foreach ($santri_siap_tasmi as $st): ?>
                    <span class="badge bg-warning text-dark ms-1">
                        <?= e($st['nama_santri']) ?> (<?= (float) $st['total_ziyadah'] ?> Hal)
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($santri_siap_5juz)): ?>
            <div class="small text-secondary">
                <strong>Siap Tasmi' / Imtihan Kelipatan 5 Juz:</strong>
                <?php foreach ($santri_siap_5juz as $s5): ?>
                    <span class="badge bg-success ms-1">
                        <?= e($s5['nama_santri']) ?> (<?= (float) $s5['total_hafalan_juz'] ?> Juz)
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <a href="ujian_tasmi.php" class="btn btn-gold-siata btn-sm">
            <i class="fa-solid fa-graduation-cap me-1"></i> Ke Halaman Evaluasi / Tasmi'
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Filter Cepat 6 Kategori Setoran & Evaluasi -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="setoran_tahfidz.php" class="btn btn-sm <?= $filter_jenis === '' ? 'btn-siata' : 'btn-outline-secondary bg-white' ?>">
        Semua Setoran
    </a>
    <?php foreach ($daftar_jenis_setoran as $js): ?>
    <a href="setoran_tahfidz.php?jenis=<?= urlencode($js) ?>" class="btn btn-sm <?= $filter_jenis === $js ? 'btn-siata' : 'btn-outline-secondary bg-white' ?>">
        <?= e($js) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Tabel DataTables Setoran Tahfidz -->
<div class="card-siata">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-book-open-reader"></i> Riwayat Setoran Ziyadah &amp; Muraja'ah Harian</h5>
    </div>
    <div class="p-3 table-responsive">
        <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Santri</th>
                    <th>Jenis</th>
                    <th>Juz &amp; Surah / Ayat</th>
                    <th class="text-center">Jml Hal / Baris</th>
                    <th class="text-center">Nilai (K/T/M)</th>
                    <th>Predikat</th>
                    <th>Status 10 Hal</th>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_setoran)): ?>
                <?php
                    $sid      = (int) $row['santri_id'];
                    $tot_ziy  = $akumulasi_ziyadah[$sid] ?? 0;
                    $siap_10  = ($tot_ziy >= 10 && fmod($tot_ziy, 10.0) === 0.0) || ($tot_ziy >= 10);
                    $badge_pr = match ($row['predikat']) {
                        'Mumtaz'        => 'bg-success',
                        'Jayyid Jiddan' => 'bg-primary',
                        'Jayyid'        => 'bg-info text-dark',
                        'Maqbul'        => 'bg-warning text-dark',
                        default         => 'bg-danger'
                    };
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($row['tanggal']) ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= e($row['nama_santri']) ?></div>
                        <small class="text-muted">Muhaffizh: <?= e($row['nama_pengajar'] ?? '-') ?></small>
                    </td>
                    <td>
                        <?php
                            $badge_js = match ($row['jenis_setoran']) {
                                'Tahsin Dasar'                  => 'bg-secondary',
                                'Ziyadah'                       => 'bg-success',
                                'Murajaah'                      => 'bg-warning text-dark',
                                'Hafalan Hadis & Kitab'         => 'bg-info text-dark',
                                'Evaluasi Tertutup 10 Halaman'  => 'bg-primary',
                                'Ujian Tasmi 5 Juz'             => 'bg-dark',
                                default                         => 'bg-primary'
                            };
                        ?>
                        <span class="badge <?= $badge_js ?>">
                            <?= e($row['jenis_setoran']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">Juz <?= (int) $row['juz'] ?>: <?= e($row['surah_awal']) ?> (<?= (int) $row['ayat_awal'] ?>) - <?= e($row['surah_akhir']) ?> (<?= (int) $row['ayat_akhir'] ?>)</div>
                        <?php if ($row['catatan']): ?>
                            <small class="text-muted"><i class="fa-regular fa-comment-dots me-1"></i><?= e($row['catatan']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold"><?= (float) $row['jumlah_halaman'] ?> Hal</span>
                        <small class="text-muted d-block"><?= (int) $row['jumlah_baris'] ?> Baris</small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            <?= (int) $row['nilai_kelancaran'] ?> / <?= (int) $row['nilai_tajwid'] ?> / <?= (int) $row['nilai_makhraj'] ?>
                        </span>
                    </td>
                    <td><span class="badge <?= $badge_pr ?>"><?= e($row['predikat']) ?></span></td>
                    <td>
                        <div class="small fw-semibold">Akumulasi: <?= $tot_ziy ?> Hal</div>
                        <?php if ($siap_10): ?>
                            <a href="ujian_tasmi.php?santri_id=<?= $sid ?>" class="badge bg-warning text-dark text-decoration-none mt-1">
                                <i class="fa-solid fa-star me-1"></i>Wajib Evaluasi 10 Hal
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary">Menuju 10 Hal (<?= round(max(0, 10 - fmod((float) $tot_ziy, 10.0)), 1) ?> Hal lagi)</span>
                        <?php endif; ?>
                    </td>
                    <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                    <td class="text-center text-nowrap">
                        <button type="button" class="btn btn-warning btn-sm text-white btn-edit-setoran"
                                data-setoran='<?= e(json_encode($row)) ?>'
                                data-bs-toggle="modal" data-bs-target="#modalEditSetoran">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-setoran"
                                data-id="<?= (int) $row['id'] ?>"
                                data-nama="<?= e($row['nama_santri']) ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapusSetoran">
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

<!-- Modal Tambah Setoran -->
<div class="modal fade" id="modalTambahSetoran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="setoran_tahfidz.php">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-book-quran me-2"></i>Input Setoran Ziyadah / Muraja'ah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Muhaffizh / Pengajar</label>
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
                            <label class="form-label small fw-semibold">Kategori Setoran</label>
                            <select name="jenis_setoran" class="form-select" required>
                                <?php foreach ($daftar_jenis_setoran as $js): ?>
                                    <option value="<?= e($js) ?>" <?= $js === 'Ziyadah' ? 'selected' : '' ?>><?= e($js) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Juz</label>
                            <input type="number" name="juz" class="form-control" value="30" min="1" max="30" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Surah Awal</label>
                            <input type="text" name="surah_awal" class="form-control" placeholder="An-Naba" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Ayat Awal</label>
                            <input type="number" name="ayat_awal" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Surah Akhir</label>
                            <input type="text" name="surah_akhir" class="form-control" placeholder="An-Naba" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Ayat Akhir</label>
                            <input type="number" name="ayat_akhir" class="form-control" value="20" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jumlah Halaman</label>
                            <input type="number" step="0.5" name="jumlah_halaman" class="form-control" value="1.0" min="0.5" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jumlah Baris</label>
                            <input type="number" name="jumlah_baris" class="form-control" value="15" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Kelancaran</label>
                            <input type="number" name="nilai_kelancaran" class="form-control" value="92" min="0" max="100" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tajwid</label>
                            <input type="number" name="nilai_tajwid" class="form-control" value="90" min="0" max="100" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Makhraj</label>
                            <input type="number" name="nilai_makhraj" class="form-control" value="90" min="0" max="100" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Predikat</label>
                            <select name="predikat" class="form-select">
                                <option value="Mumtaz">Mumtaz</option>
                                <option value="Jayyid Jiddan">Jayyid Jiddan</option>
                                <option value="Jayyid">Jayyid</option>
                                <option value="Maqbul">Maqbul</option>
                                <option value="Rosib">Rosib</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Catatan Muhaffizh</label>
                            <input type="text" name="catatan" class="form-control" placeholder="Bacaan tartil, perhatikan mad...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Setoran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Setoran -->
<div class="modal fade" id="modalEditSetoran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="setoran_tahfidz.php">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="es_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Setoran Tahfidz</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Santri</label>
                            <select name="santri_id" id="es_santri" class="form-select" required>
                                <?php foreach ($list_santri as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nis'] . ' - ' . $s['nama_santri']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Muhaffizh / Pengajar</label>
                            <select name="pengajar_id" id="es_pengajar" class="form-select" required>
                                <?php foreach ($list_pengajar as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= e($p['nama_pengajar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Setoran</label>
                            <input type="date" name="tanggal" id="es_tanggal" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori Setoran</label>
                            <select name="jenis_setoran" id="es_jenis" class="form-select" required>
                                <?php foreach ($daftar_jenis_setoran as $js): ?>
                                    <option value="<?= e($js) ?>"><?= e($js) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Juz</label>
                            <input type="number" name="juz" id="es_juz" class="form-control" min="1" max="30" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Surah Awal</label>
                            <input type="text" name="surah_awal" id="es_s_awal" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Ayat Awal</label>
                            <input type="number" name="ayat_awal" id="es_a_awal" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Surah Akhir</label>
                            <input type="text" name="surah_akhir" id="es_s_akhir" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Ayat Akhir</label>
                            <input type="number" name="ayat_akhir" id="es_a_akhir" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jumlah Halaman</label>
                            <input type="number" step="0.5" name="jumlah_halaman" id="es_hal" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jumlah Baris</label>
                            <input type="number" name="jumlah_baris" id="es_baris" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Kelancaran</label>
                            <input type="number" name="nilai_kelancaran" id="es_nk" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Tajwid</label>
                            <input type="number" name="nilai_tajwid" id="es_nt" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Makhraj</label>
                            <input type="number" name="nilai_makhraj" id="es_nm" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Predikat</label>
                            <select name="predikat" id="es_predikat" class="form-select">
                                <option value="Mumtaz">Mumtaz</option>
                                <option value="Jayyid Jiddan">Jayyid Jiddan</option>
                                <option value="Jayyid">Jayyid</option>
                                <option value="Maqbul">Maqbul</option>
                                <option value="Rosib">Rosib</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Catatan Muhaffizh</label>
                            <input type="text" name="catatan" id="es_catatan" class="form-control">
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

<!-- Modal Hapus Setoran -->
<div class="modal fade" id="modalHapusSetoran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="setoran_tahfidz.php">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="hs_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Setoran</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus data setoran santri:</p>
                    <h6 class="fw-bold text-danger" id="hs_nama"></h6>
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
    document.querySelectorAll('.btn-edit-setoran').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-setoran'));
            document.getElementById('es_id').value = d.id;
            document.getElementById('es_santri').value = d.santri_id;
            document.getElementById('es_pengajar').value = d.pengajar_id;
            document.getElementById('es_tanggal').value = d.tanggal;
            document.getElementById('es_jenis').value = d.jenis_setoran;
            document.getElementById('es_juz').value = d.juz;
            document.getElementById('es_s_awal').value = d.surah_awal;
            document.getElementById('es_a_awal').value = d.ayat_awal;
            document.getElementById('es_s_akhir').value = d.surah_akhir;
            document.getElementById('es_a_akhir').value = d.ayat_akhir;
            document.getElementById('es_hal').value = d.jumlah_halaman;
            document.getElementById('es_baris').value = d.jumlah_baris;
            document.getElementById('es_nk').value = d.nilai_kelancaran;
            document.getElementById('es_nt').value = d.nilai_tajwid;
            document.getElementById('es_nm').value = d.nilai_makhraj;
            document.getElementById('es_predikat').value = d.predikat;
            document.getElementById('es_catatan').value = d.catatan || '';
        });
    });

    document.querySelectorAll('.btn-hapus-setoran').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hs_id').value = this.getAttribute('data-id');
            document.getElementById('hs_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
