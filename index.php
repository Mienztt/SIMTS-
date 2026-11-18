<?php
/**
 * FILE: index.php
 * Halaman Home Dashboard SIMTS - Rumah Qur'an Qurtuba (Desain Visual SIATA + Panel Monitoring Orang Tua Santri)
 */
require_once __DIR__ . '/config/koneksi.php';
cek_login();

$page_title = 'Dashboard';
$role       = $_SESSION['role'] ?? 'admin';
$user_id    = (int) ($_SESSION['user_id'] ?? 0);

// Ambil 4 Statistik Angka secara Dinamis dari Database
$jml_santri_aktif  = hitung_total($conn, "SELECT COUNT(*) FROM santri WHERE status_santri = 'Aktif'", 31);
$jml_santri_alumni = hitung_total($conn, "SELECT COUNT(*) FROM santri WHERE status_santri = 'Alumni'", 6);
$jml_asatidzah     = hitung_total($conn, "SELECT COUNT(*) FROM pengajar WHERE status = 'Aktif'", 15);
$jml_lembaga       = hitung_total($conn, "SELECT COUNT(*) FROM lembaga", 1);

// Rekapitulasi Lintas Angkatan (Tabel 1.1 Laporan)
$rekap_angkatan = [];
$sql_angk = "SELECT tahun_angkatan,
                    COUNT(*) AS jml_masuk,
                    SUM(CASE WHEN status_santri = 'Alumni' THEN 1 ELSE 0 END) AS jml_alumni,
                    SUM(CASE WHEN status_santri = 'Keluar' THEN 1 ELSE 0 END) AS jml_keluar,
                    SUM(CASE WHEN status_santri = 'Aktif' THEN 1 ELSE 0 END) AS jml_aktif
             FROM santri
             GROUP BY tahun_angkatan
             ORDER BY tahun_angkatan ASC";
$res_angk = mysqli_query($conn, $sql_angk);
if ($res_angk) {
    while ($ra = mysqli_fetch_assoc($res_angk)) {
        $rekap_angkatan[] = $ra;
    }
}

// Jika login sebagai Orang Tua Santri (orang_tua), ambil ringkasan perkembangan putra/putrinya
$anak_ortu = [];
if ($role === 'orang_tua') {
    $sql_anak = "SELECT s.*, h.nama_halaqah, p.nama_pengajar,
                        COALESCE((SELECT SUM(poin_saat_itu) FROM pelanggaran_santri ps WHERE ps.santri_id = s.id), 0) AS poin_disiplin,
                        COALESCE((SELECT COUNT(*) FROM mutabaah_santri ms WHERE ms.santri_id = s.id AND ms.status_pelaksanaan='Ya'), 0) AS mutabaah_ya,
                        (SELECT id FROM rapor_semester r WHERE r.santri_id = s.id AND r.status_publikasi='Terbit' ORDER BY r.id DESC LIMIT 1) AS id_rapor
                 FROM santri s
                 LEFT JOIN halaqah h ON s.halaqah_id = h.id
                 LEFT JOIN pengajar p ON s.pengajar_id = p.id
                 WHERE s.id_user_orang_tua = $user_id AND s.status_santri = 'Aktif'
                 ORDER BY s.id ASC LIMIT 6";
    $res_anak = mysqli_query($conn, $sql_anak);
    while ($a = mysqli_fetch_assoc($res_anak)) {
        $anak_ortu[] = $a;
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<style>
    /* ========================================================================
       1. BANNER CAROUSEL ISLAMI (HIJAU GELAP BERMOTIF + POLAROID AL-QUR'AN)
       ======================================================================== */
    .siata-banner-carousel {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(11, 34, 32, 0.12);
        margin-bottom: 22px;
    }

    .siata-slide-item {
        position: relative;
        min-height: 235px;
        background: linear-gradient(120deg, #0b2220 0%, #133c38 55%, #1d5650 100%);
        color: #ffffff;
        padding: 32px 48px;
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    .siata-slide-item::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23c5a880' stroke-width='0.8' stroke-opacity='0.18'%3E%3Cpath d='M40 0L50 30L80 40L50 50L40 80L30 50L0 40L30 30Z'/%3E%3Ccircle cx='40' cy='40' r='16'/%3E%3C/g%3E%3C/svg%3E");
        pointer-events: none;
        z-index: 0;
    }

    .siata-slide-content {
        position: relative;
        z-index: 2;
        width: 100%;
    }

    .banner-title {
        font-size: 1.55rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 12px;
        letter-spacing: 0.3px;
    }

    .banner-quote {
        font-size: 0.94rem;
        line-height: 1.7;
        color: #e2e8f0;
        margin-bottom: 0;
        max-width: 680px;
    }

    .banner-quote strong {
        color: #fde047;
        font-weight: 600;
    }

    .polaroid-stack {
        position: relative;
        width: 195px;
        height: 165px;
        margin: 0 auto;
    }

    .polaroid-card-back {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.85);
        border-radius: 6px;
        transform: rotate(7deg) translate(8px, 4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    }

    .polaroid-card-front {
        position: relative;
        background: #ffffff;
        padding: 9px 9px 22px 9px;
        border-radius: 6px;
        transform: rotate(-5deg);
        box-shadow: 0 12px 26px rgba(0, 0, 0, 0.38);
        transition: transform 0.3s ease;
    }

    .polaroid-card-front:hover {
        transform: rotate(-1deg) scale(1.03);
    }

    .polaroid-img-box {
        width: 100%;
        height: 125px;
        border-radius: 4px;
        background: linear-gradient(145deg, #1b4d47, #0d2926);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #f8fafc;
        position: relative;
        overflow: hidden;
        border: 1px solid #c5a880;
    }

    .polaroid-img-box i {
        font-size: 3.1rem;
        color: #eab308;
        margin-bottom: 6px;
        filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.3));
    }

    .polaroid-caption {
        font-size: 0.65rem;
        font-weight: 600;
        color: #f1f5f9;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* ========================================================================
       2. KOTAK PUTIH MENU CEPAT (8 IKON BULAT BERWARNA - 2 BARIS x 4 KOLOM)
       ======================================================================== */
    .quick-menu-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 30px 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        border: 1px solid #e9eef3;
        margin-bottom: 22px;
    }

    .quick-menu-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #334155;
        padding: 8px 6px;
        border-radius: 10px;
        transition: transform 0.2s ease;
    }

    .quick-menu-item:hover {
        transform: translateY(-4px);
        color: #0b2220;
    }

    .quick-icon-circle {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.55rem;
        margin-bottom: 10px;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.2s ease, filter 0.2s ease;
    }

    .quick-menu-item:hover .quick-icon-circle {
        filter: brightness(1.06);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
    }

    .quick-menu-label {
        font-size: 0.82rem;
        font-weight: 500;
        color: #475569;
        text-align: center;
        line-height: 1.3;
    }

    .bg-lime-siata    { background-color: #8cc63f; }
    .bg-orange-siata  { background-color: #f37021; }
    .bg-purple-siata  { background-color: #7c4dff; }
    .bg-gold-siata    { background-color: #eab308; }
    .bg-lgreen-siata  { background-color: #a3cf62; }
    .bg-tosca-siata   { background-color: #2a9d8f; }
    .bg-blue-siata    { background-color: #3b82f6; }
    .bg-emerald-siata { background-color: #10b981; }

    /* ========================================================================
       3. 4 KARTU STATISTIK ANGKA DI BAGIAN BAWAH (1 BARIS x 4 KOLOM)
       ======================================================================== */
    .stat-card-siata {
        background: #ffffff;
        border-radius: 10px;
        padding: 24px 18px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        border: 1px solid #e9eef3;
        height: 100%;
        transition: transform 0.2s ease;
    }

    .stat-card-siata:hover {
        transform: translateY(-3px);
    }

    .stat-number {
        font-size: 2.3rem;
        font-weight: 700;
        color: #334155;
        line-height: 1.15;
        margin-bottom: 6px;
    }

    .stat-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: #64748b;
    }

    @media (max-width: 767.98px) {
        .siata-slide-item {
            padding: 24px 20px;
            text-align: center;
        }
        .banner-title {
            font-size: 1.25rem;
        }
        .banner-quote {
            font-size: 0.84rem;
        }
        .polaroid-stack {
            margin-top: 18px;
        }
    }
</style>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'akses_ditolak'): ?>
    <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        Anda tidak memiliki hak akses untuk membuka halaman tersebut.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ==========================================================================
     PANEL KHUSUS ROLE ORANG TUA SANTRI: MONITORING PUTRA/PUTRI (READ-ONLY)
     ========================================================================== -->
<?php if ($role === 'orang_tua' && !empty($anak_ortu)): ?>
<div class="card-siata mb-4">
    <div class="card-header-siata">
        <h5><i class="fa-solid fa-child-reaching"></i> Portal Monitoring Orang Tua Santri - Perkembangan Putra/Putri Anda</h5>
        <a href="modules/mutabaah/mutabaah_santri.php?tab=rekap" class="btn btn-warning btn-sm fw-semibold">
            <i class="fa-solid fa-eye me-1"></i> Pantau Mutaba'ah Harian
        </a>
    </div>
    <div class="p-3">
        <div class="row g-3">
            <?php foreach ($anak_ortu as $ank): ?>
            <?php
                $poin = (int) $ank['poin_disiplin'];
                $st_dis = $poin >= 50 ? ['Kartu Merah / Evaluasi Beasiswa', 'bg-danger']
                        : ($poin >= 30 ? ['Kartu Kuning / SP-1', 'bg-warning text-dark']
                        : ($poin >= 15 ? ['Teguran Lisan', 'bg-info text-dark']
                        : ['Aman', 'bg-success']));
            ?>
            <div class="col-md-6">
                <div class="p-3 rounded-3 border bg-light h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><?= e($ank['nama_santri']) ?></h6>
                            <small class="text-muted">
                                <?= e($ank['nis']) ?> &bull; <?= e($ank['jenjang'] ?? 'SMP') ?> &bull; <?= e($ank['tahun_angkatan'] ?? '-') ?>
                            </small>
                            <div class="small text-success fw-medium"><?= e($ank['nama_halaqah'] ?? '-') ?></div>
                        </div>
                        <span class="badge <?= $st_dis[1] ?>"><?= $st_dis[0] ?> (<?= $poin ?> Poin)</span>
                    </div>
                    <div class="row g-2 text-center my-2">
                        <div class="col-4">
                            <div class="p-2 bg-white rounded border">
                                <div class="small text-muted">Hafalan</div>
                                <div class="fw-bold text-success"><?= (float) $ank['total_hafalan_juz'] ?> / <?= (int) $ank['target_juz'] ?> Juz</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-white rounded border">
                                <div class="small text-muted">Jilid Tahsin</div>
                                <div class="fw-bold text-primary small"><?= e($ank['jilid_tahsin']) ?></div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-white rounded border">
                                <div class="small text-muted">Mutaba'ah</div>
                                <div class="fw-bold text-dark"><?= (int) $ank['mutabaah_ya'] ?> Amalan</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top flex-wrap gap-2">
                        <small class="text-muted"><i class="fa-solid fa-user-tie me-1"></i><?= e($ank['nama_pengajar'] ?? '-') ?></small>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="modules/mutabaah/mutabaah_santri.php?tab=rekap&santri_id=<?= (int) $ank['id'] ?>" class="btn btn-outline-success btn-sm">Pantau Mutaba'ah</a>
                            <a href="modules/pelanggaran/pelanggaran_rekap.php" class="btn btn-outline-secondary btn-sm">Cek Poin Disiplin</a>
                            <?php if (!empty($ank['id_rapor'])): ?>
                            <a href="modules/rapor/cetak_rapor.php?id=<?= (int) $ank['id_rapor'] ?>" target="_blank" class="btn btn-siata btn-sm">
                                <i class="fa-solid fa-file-pdf me-1"></i> Unduh Rapor PDF
                            </a>
                            <?php else: ?>
                            <a href="modules/rapor/rapor.php" class="btn btn-siata btn-sm">Lihat Rapor</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==========================================================================
     BAGIAN 1: BANNER CAROUSEL (BOOTSTRAP 5 CAROUSEL)
     ========================================================================== -->
<div id="bannerQuranCarousel" class="carousel slide siata-banner-carousel" data-bs-ride="carousel" data-bs-interval="6000">
    <div class="carousel-indicators mb-2">
        <button type="button" data-bs-target="#bannerQuranCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#bannerQuranCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
    </div>

    <div class="carousel-inner">
        <!-- SLIDE 1: Surat Shad Ayat 29 -->
        <div class="carousel-item active">
            <div class="siata-slide-item">
                <div class="siata-slide-content">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-lg-9">
                            <h2 class="banner-title">Anjuran Mempelajari Al-Qur'an</h2>
                            <p class="banner-quote">
                                Kitab (Al-Qur'an) yang Kami turunkan kepadamu penuh berkah, agar mereka menghayati ayat-ayatnya dan agar orang-orang yang berakal sehat mendapat pelajaran.
                                <strong>[Surat Shad: 29]</strong>
                            </p>
                        </div>
                        <div class="col-md-4 col-lg-3 d-flex justify-content-center">
                            <div class="polaroid-stack">
                                <div class="polaroid-card-back"></div>
                                <div class="polaroid-card-front">
                                    <div class="polaroid-img-box">
                                        <i class="fa-solid fa-book-quran"></i>
                                        <span class="polaroid-caption">Al-Qur'anul Karim</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 2: Hadis Riwayat Tirmidzi -->
        <div class="carousel-item">
            <div class="siata-slide-item">
                <div class="siata-slide-content">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-lg-9">
                            <h2 class="banner-title">Keutamaan Membaca &amp; Menghafal Al-Qur'an</h2>
                            <p class="banner-quote">
                                "Barangsiapa membaca satu huruf dari Kitabullah (Al-Qur'an), maka baginya satu kebaikan, dan satu kebaikan itu dilipatgandakan menjadi sepuluh kebaikan..."
                                <strong>(HR. Tirmidzi)</strong>
                            </p>
                        </div>
                        <div class="col-md-4 col-lg-3 d-flex justify-content-center">
                            <div class="polaroid-stack">
                                <div class="polaroid-card-back"></div>
                                <div class="polaroid-card-front">
                                    <div class="polaroid-img-box">
                                        <i class="fa-solid fa-mosque"></i>
                                        <span class="polaroid-caption">Rumah Qur'an Qurtuba</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#bannerQuranCarousel" data-bs-slide="prev" style="width: 42px;">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Sebelumnya</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#bannerQuranCarousel" data-bs-slide="next" style="width: 42px;">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Selanjutnya</span>
    </button>
</div>

<!-- ==========================================================================
     BAGIAN 2: KOTAK PUTIH MENU CEPAT (8 IKON BULAT BERWARNA - 2 BARIS x 4 KOLOM)
     ========================================================================== -->
<div class="quick-menu-card">
    <div class="row g-4">
        <!-- BARIS 1 -->
        <div class="col-6 col-md-3">
            <a href="modules/tahfidz/target_tahfidz.php" class="quick-menu-item">
                <div class="quick-icon-circle bg-lime-siata">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <span class="quick-menu-label">Target Hafalan</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="modules/tahfidz/setoran_tahfidz.php" class="quick-menu-item">
                <div class="quick-icon-circle bg-orange-siata">
                    <i class="fa-solid fa-star-and-crescent"></i>
                </div>
                <span class="quick-menu-label">Hafalan Hari Ini</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="modules/laporan/evaluasi_tahfidz.php?jenis=Ziyadah" class="quick-menu-item">
                <div class="quick-icon-circle bg-purple-siata">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <span class="quick-menu-label">Evaluasi Ziyadah</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="modules/laporan/evaluasi_tahfidz.php?jenis=Murajaah" class="quick-menu-item">
                <div class="quick-icon-circle bg-gold-siata">
                    <i class="fa-solid fa-repeat"></i>
                </div>
                <span class="quick-menu-label">Evaluasi Muroja'ah</span>
            </a>
        </div>

        <!-- BARIS 2 -->
        <div class="col-6 col-md-3">
            <a href="<?= $role === 'orang_tua' ? 'modules/mutabaah/mutabaah_santri.php?tab=rekap' : 'modules/master/santri.php' ?>" class="quick-menu-item">
                <div class="quick-icon-circle bg-lgreen-siata">
                    <i class="fa-solid fa-users"></i>
                </div>
                <span class="quick-menu-label"><?= $role === 'orang_tua' ? "Pantau Mutaba'ah" : 'Data Santri' ?></span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="<?= $role === 'orang_tua' ? 'modules/rapor/rapor.php' : 'modules/master/pengajar.php' ?>" class="quick-menu-item">
                <div class="quick-icon-circle bg-tosca-siata">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <span class="quick-menu-label"><?= $role === 'orang_tua' ? 'Rapor Semester' : 'Data Pengurus & Musyrif' ?></span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="modules/presensi/presensi.php?tab=rekap" class="quick-menu-item">
                <div class="quick-icon-circle bg-blue-siata">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <span class="quick-menu-label">Rekap Absensi 7 Sesi</span>
            </a>
        </div>

        <div class="col-6 col-md-3">
            <a href="modules/tahfidz/target_tahfidz.php?view=pencapaian" class="quick-menu-item">
                <div class="quick-icon-circle bg-emerald-siata">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <span class="quick-menu-label">% Pencapaian Target</span>
            </a>
        </div>
    </div>
</div>

<!-- ==========================================================================
     BAGIAN 3: 4 KARTU STATISTIK ANGKA DI BAGIAN BAWAH (1 BARIS x 4 KOLOM)
     ========================================================================== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card-siata">
            <div class="stat-number"><?= e((string) $jml_santri_aktif) ?></div>
            <div class="stat-label">Santri Aktif</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card-siata">
            <div class="stat-number"><?= e((string) $jml_santri_alumni) ?></div>
            <div class="stat-label">Santri Alumni</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card-siata">
            <div class="stat-number"><?= e((string) $jml_asatidzah) ?></div>
            <div class="stat-label">Pengurus &amp; Musyrif</div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="stat-card-siata">
            <div class="stat-number"><?= e((string) $jml_lembaga) ?></div>
            <div class="stat-label">Lembaga</div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     BAGIAN 4: RINGKASAN LINTAS ANGKATAN (TABEL 1.1) & JADWAL 7 SESI (TABEL 1.2)
     ========================================================================== -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-siata h-100 mb-0">
            <div class="card-header-siata">
                <h6><i class="fa-solid fa-layer-group"></i> Rekapitulasi Santri Lintas Angkatan (2021–2026)</h6>
                <span class="badge bg-warning text-dark">Beasiswa Penuh 3 Tahun</span>
            </div>
            <div class="p-3 table-responsive">
                <table class="table table-sm table-hover table-bordered align-middle mb-0" style="font-size:0.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Angkatan / Tahun Masuk</th>
                            <th class="text-center">Masuk</th>
                            <th class="text-center">Alumni</th>
                            <th class="text-center">Keluar</th>
                            <th class="text-center">Aktif</th>
                            <th>Status Program</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tot_masuk = 0; $tot_alumni = 0; $tot_keluar = 0; $tot_aktif = 0;
                        $ket_program_map = [
                            '2021/2022' => 'Angkatan I — Lulus 3 Tahun',
                            '2022/2023' => 'Angkatan II — Lulus 3 Tahun',
                            '2023/2024' => 'Angkatan III — Tahfidz Lanjutan',
                            '2024/2025' => 'Angkatan IV — Tahfidz Lanjutan',
                            '2025/2026' => 'Angkatan V — Tahsin Dasar',
                        ];
                        foreach ($rekap_angkatan as $rk):
                            $tot_masuk  += (int) $rk['jml_masuk'];
                            $tot_alumni += (int) $rk['jml_alumni'];
                            $tot_keluar += (int) $rk['jml_keluar'];
                            $tot_aktif  += (int) $rk['jml_aktif'];
                            $ket_prog    = $ket_program_map[$rk['tahun_angkatan']] ?? 'Program Pembinaan';
                        ?>
                        <tr>
                            <td class="fw-semibold text-dark">Angkatan <?= e($rk['tahun_angkatan']) ?></td>
                            <td class="text-center"><?= (int) $rk['jml_masuk'] ?></td>
                            <td class="text-center"><?= (int) $rk['jml_alumni'] ?></td>
                            <td class="text-center"><?= (int) $rk['jml_keluar'] ?></td>
                            <td class="text-center fw-bold text-success"><?= (int) $rk['jml_aktif'] ?></td>
                            <td><small class="text-muted"><?= e($ket_prog) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>Total Keseluruhan</td>
                            <td class="text-center"><?= $tot_masuk ?></td>
                            <td class="text-center text-primary"><?= $tot_alumni ?></td>
                            <td class="text-center text-secondary"><?= $tot_keluar ?></td>
                            <td class="text-center text-success"><?= $tot_aktif ?></td>
                            <td><span class="badge bg-success">3 Angkatan Aktif</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-siata h-100 mb-0">
            <div class="card-header-siata">
                <h6><i class="fa-solid fa-clock"></i> Jadwal 7 Sesi Kegiatan Harian (03.00 – 21.00 WIB)</h6>
                <span class="badge bg-light text-dark">Asrama Terpadu</span>
            </div>
            <div class="p-3 table-responsive">
                <table class="table table-sm table-hover table-bordered align-middle mb-0" style="font-size:0.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Sesi</th>
                            <th>Waktu (WIB)</th>
                            <th>Aktivitas Lapangan</th>
                            <th>Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Sesi 1 - Dini Hari / Tahajud</td>
                            <td class="text-nowrap">03.00 – 04.30</td>
                            <td>Bangun malam, qiyamul lail berjamaah, dzikir</td>
                            <td><small>Musyrif Asrama</small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Sesi 2 - Subuh / Halaqah Utama</td>
                            <td class="text-nowrap">04.30 – 06.30</td>
                            <td>Shalat Subuh, ziyadah tahfidz &amp; tahsin dasar</td>
                            <td><small>Musyrif &amp; Mudir</small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Sesi 3 - Pagi / Dhuha</td>
                            <td class="text-nowrap">08.00 – 11.00</td>
                            <td>Shalat Dhuha, halaqah tahsin &amp; muraja'ah</td>
                            <td><small>Musyrif Halaqah</small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Sesi 4 - Siang / Kondisional</td>
                            <td class="text-nowrap">13.00 – 14.30</td>
                            <td>Shalat Dzuhur &amp; pelajaran penunjang / kitab</td>
                            <td><small>Pengajar / Asatidz</small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Sesi 5 - Sore / Ashar: Halaqah Hadis</td>
                            <td class="text-nowrap">16.00 – 17.30</td>
                            <td>Shalat Ashar, hafalan Hadis &amp; muraja'ah sore</td>
                            <td><small>Musyrif Halaqah</small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Sesi 6 - Maghrib: Tilawah / Kajian Jumat</td>
                            <td class="text-nowrap">18.00 – 19.30</td>
                            <td>Tilawah mandiri / pengajian masjid (Jumat)</td>
                            <td><small>Musyrif Asrama</small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Sesi 7 - Ba'da Isya: Evaluasi &amp; Mutaba'ah</td>
                            <td class="text-nowrap">19.30 – 21.00</td>
                            <td>Muraja'ah malam &amp; ceklis mutaba'ah (22 amalan)</td>
                            <td><small>Musyrif &amp; Petugas Rekap</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
