<?php
/**
 * FILE: modules/pengaturan/lembaga.php
 * Modul Profil Lembaga & Pengaturan Tahun Ajaran / Semester Aktif
 * Tabel: lembaga (id, nama_lembaga, nsm_npsn, mudir_pimpinan, alamat, kota, telepon, email, website, tahun_ajaran_aktif, semester_aktif)
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin', 'pengajar']);

$page_title = 'Profil Lembaga';
$alert_msg  = '';
$alert_type = 'success';

// Proses Update Profil Lembaga (Khusus Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'update_lembaga') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        $alert_msg  = 'Hanya Admin Lembaga yang dapat mengubah profil lembaga.';
        $alert_type = 'danger';
    } else {
        $id                 = (int) ($_POST['id'] ?? 1);
        $nama_lembaga       = trim($_POST['nama_lembaga'] ?? '');
        $nsm_npsn           = trim($_POST['nsm_npsn'] ?? '');
        $mudir_pimpinan     = trim($_POST['mudir_pimpinan'] ?? '');
        $alamat             = trim($_POST['alamat'] ?? '');
        $kota               = trim($_POST['kota'] ?? '');
        $telepon            = trim($_POST['telepon'] ?? '');
        $email              = trim($_POST['email'] ?? '');
        $website            = trim($_POST['website'] ?? '');
        $tahun_ajaran_aktif = trim($_POST['tahun_ajaran_aktif'] ?? '2026/2027');
        $semester_aktif     = in_array($_POST['semester_aktif'] ?? '', ['Ganjil', 'Genap'], true) ? $_POST['semester_aktif'] : 'Ganjil';

        $stmt = mysqli_prepare($conn, "UPDATE lembaga SET nama_lembaga=?, nsm_npsn=?, mudir_pimpinan=?, alamat=?, kota=?, telepon=?, email=?, website=?, tahun_ajaran_aktif=?, semester_aktif=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssssssi', $nama_lembaga, $nsm_npsn, $mudir_pimpinan, $alamat, $kota, $telepon, $email, $website, $tahun_ajaran_aktif, $semester_aktif, $id);
        if (mysqli_stmt_execute($stmt)) {
            $alert_msg = 'Data profil lembaga dan tahun ajaran aktif berhasil diperbarui!';
        } else {
            $alert_msg  = 'Gagal memperbarui data lembaga: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// Ambil Data Lembaga
$res_lembaga = mysqli_query($conn, "SELECT * FROM lembaga ORDER BY id ASC LIMIT 1");
$lembaga     = mysqli_fetch_assoc($res_lembaga) ?: [
    'id'                 => 1,
    'nama_lembaga'       => "Rumah Qur'an Qurtuba",
    'nsm_npsn'           => '510031740092',
    'mudir_pimpinan'     => 'Ust. Ridwan',
    'alamat'             => 'Rumah Wakaf Terpadu Qurtuba, Jl. Qurtuba Raya No. 17 (Berdiri Legal: 19 November 2021)',
    'kota'               => 'Jakarta Selatan',
    'telepon'            => '0812-8899-7766',
    'email'              => 'info@qurtuba.sch.id',
    'website'            => 'https://qurtuba.sch.id',
    'tahun_ajaran_aktif' => '2025/2026',
    'semester_aktif'     => 'Genap'
];

$total_santri  = hitung_total($conn, "SELECT COUNT(*) FROM santri WHERE status_santri='Aktif'", 31);
$total_alumni  = hitung_total($conn, "SELECT COUNT(*) FROM santri WHERE status_santri='Alumni'", 6);
$total_asatidz = hitung_total($conn, "SELECT COUNT(*) FROM pengajar WHERE status='Aktif'", 15);
$total_halaqah = hitung_total($conn, "SELECT COUNT(*) FROM halaqah", 5);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-building-columns me-2"></i>Profil Lembaga</h4>
        <small class="text-muted">Identitas resmi lembaga &amp; konfigurasi periode akademik aktif</small>
    </div>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalEditLembaga">
        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profil Lembaga
    </button>
    <?php endif; ?>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Kolom Kiri: Banner Identitas Utama -->
    <div class="col-lg-4">
        <div class="card-siata h-100">
            <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #0b2220 0%, #145345 100%); border-bottom: 3px solid #c5a880;">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle shadow"
                     style="width:92px;height:92px;background:#ffffff;color:#0b2220;border:3px solid #c5a880;">
                    <i class="fa-solid fa-mosque fa-3x" style="color:#145345;"></i>
                </div>
                <h5 class="fw-bold mb-1"><?= e($lembaga['nama_lembaga']) ?></h5>
                <div class="small" style="color:#fde047;">NSM / NPSN: <?= e($lembaga['nsm_npsn']) ?></div>
                <span class="badge bg-warning text-dark mt-2 px-3 py-1 rounded-pill fw-semibold">
                    TA <?= e($lembaga['tahun_ajaran_aktif']) ?> - Semester <?= e($lembaga['semester_aktif']) ?>
                </span>
            </div>
            <div class="p-4">
                <div class="d-flex justify-content-around text-center border-bottom pb-3 mb-3">
                    <div>
                        <div class="fs-4 fw-bold text-success"><?= $total_santri ?></div>
                        <div class="small text-muted">Santri Aktif</div>
                    </div>
                    <div class="vr"></div>
                    <div>
                        <div class="fs-4 fw-bold text-primary"><?= $total_alumni ?></div>
                        <div class="small text-muted">Alumni</div>
                    </div>
                    <div class="vr"></div>
                    <div>
                        <div class="fs-4 fw-bold text-info"><?= $total_asatidz ?></div>
                        <div class="small text-muted">Pengurus/Musyrif</div>
                    </div>
                    <div class="vr"></div>
                    <div>
                        <div class="fs-4 fw-bold text-warning"><?= $total_halaqah ?></div>
                        <div class="small text-muted">Halaqah</div>
                    </div>
                </div>
                <div class="small text-secondary">
                    <div class="mb-2"><i class="fa-solid fa-user-shield me-2 text-success"></i><strong>Mudir / Pimpinan:</strong> <?= e($lembaga['mudir_pimpinan']) ?></div>
                    <div class="mb-2"><i class="fa-solid fa-calendar-day me-2 text-warning"></i><strong>Berdiri Legal:</strong> 19 November 2021 (Rumah Wakaf)</div>
                    <div class="mb-2"><i class="fa-solid fa-clipboard-check me-2 text-info"></i><strong>Petugas Rekap Internal:</strong> A Yudha</div>
                    <div class="mb-2"><i class="fa-solid fa-location-dot me-2 text-danger"></i><strong>Kota:</strong> <?= e($lembaga['kota']) ?></div>
                    <div><i class="fa-solid fa-phone me-2 text-primary"></i><strong>Telepon:</strong> <?= e($lembaga['telepon']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rincian Lengkap Lembaga -->
    <div class="col-lg-8">
        <div class="card-siata h-100">
            <div class="card-header-siata">
                <h5><i class="fa-solid fa-circle-info"></i> Informasi Lengkap Lembaga</h5>
                <span class="badge bg-light text-dark">Terverifikasi SIMTS</span>
            </div>
            <div class="p-4">
                <table class="table table-borderless align-middle mb-0">
                    <tbody>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3" style="width: 32%;">Nama Resmi Lembaga</th>
                            <td class="fw-bold py-3 text-dark">: <?= e($lembaga['nama_lembaga']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Nomor Statistik (NSM / NPSN)</th>
                            <td class="py-3">: <span class="badge bg-secondary"><?= e($lembaga['nsm_npsn']) ?></span></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Mudir / Pimpinan Pesantren</th>
                            <td class="fw-semibold py-3">: <?= e($lembaga['mudir_pimpinan']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Tanggal Berdiri Legal &amp; Asal</th>
                            <td class="py-3">: <span class="badge bg-success-subtle text-success border border-success">19 November 2021</span> &bull; Wakaf Rumah Tinggal (Asrama Terpadu)</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Program &amp; Sasaran Santri</th>
                            <td class="py-3">: Beasiswa Penuh 3 Tahun (Yatim, Piatu &amp; Dhuafa) &bull; Jenjang SMP &amp; SMA (Usia 12–18 Tahun)</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Petugas Rekapitulasi Internal</th>
                            <td class="py-3">: <strong>A Yudha</strong> (Rekapitulasi Setoran &amp; Mutaba'ah Harian)</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Alamat Lengkap</th>
                            <td class="py-3">: <?= e($lembaga['alamat']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Kota / Kabupaten</th>
                            <td class="py-3">: <?= e($lembaga['kota']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Nomor Telepon / WhatsApp</th>
                            <td class="py-3">: <?= e($lembaga['telepon']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Email Resmi</th>
                            <td class="py-3">: <?= e($lembaga['email']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="text-muted fw-medium py-3">Website</th>
                            <td class="py-3">: <a href="<?= e($lembaga['website']) ?>" target="_blank" class="text-decoration-none"><?= e($lembaga['website']) ?></a></td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-medium py-3">Tahun Ajaran &amp; Semester Aktif</th>
                            <td class="py-3">:
                                <span class="badge bg-success"><?= e($lembaga['tahun_ajaran_aktif']) ?></span>
                                <span class="badge bg-info text-dark ms-1">Semester <?= e($lembaga['semester_aktif']) ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Profil Lembaga -->
<div class="modal fade" id="modalEditLembaga" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="lembaga.php">
                <input type="hidden" name="aksi" value="update_lembaga">
                <input type="hidden" name="id" value="<?= (int) $lembaga['id'] ?>">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Update Profil Lembaga</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nama Lembaga</label>
                            <input type="text" name="nama_lembaga" class="form-control" value="<?= e($lembaga['nama_lembaga']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">NSM / NPSN</label>
                            <input type="text" name="nsm_npsn" class="form-control" value="<?= e($lembaga['nsm_npsn']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mudir / Pimpinan</label>
                            <input type="text" name="mudir_pimpinan" class="form-control" value="<?= e($lembaga['mudir_pimpinan']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kota / Kabupaten</label>
                            <input type="text" name="kota" class="form-control" value="<?= e($lembaga['kota']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="2" required><?= e($lembaga['alamat']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Telepon / WhatsApp</label>
                            <input type="text" name="telepon" class="form-control" value="<?= e($lembaga['telepon']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($lembaga['email']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Website</label>
                            <input type="text" name="website" class="form-control" value="<?= e($lembaga['website']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tahun Ajaran Aktif</label>
                            <input type="text" name="tahun_ajaran_aktif" class="form-control" value="<?= e($lembaga['tahun_ajaran_aktif']) ?>" placeholder="2026/2027" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Semester Aktif</label>
                            <select name="semester_aktif" class="form-select" required>
                                <option value="Ganjil" <?= ($lembaga['semester_aktif'] === 'Ganjil') ? 'selected' : '' ?>>Ganjil</option>
                                <option value="Genap" <?= ($lembaga['semester_aktif'] === 'Genap') ? 'selected' : '' ?>>Genap</option>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
