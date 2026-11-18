<?php
/**
 * FILE: modules/master/pengajar.php
 * Modul Master Data Asatidz/Guru & Kelola Kelompok Halaqah (2 Tab + CRUD Modal + DataTables)
 * Tabel pengajar: id, user_id, nip, nama_pengajar, jenis_kelamin, tempat_lahir, tanggal_lahir, pendidikan_terakhir, jumlah_hafalan_juz, spesialisasi, no_hp, alamat, tanggal_bergabung, status
 * Tabel halaqah : id, kode_halaqah, nama_halaqah, pengajar_id, ruangan, target_program
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_role(['admin', 'pengajar']);

$page_title = 'Data Asatidz & Halaqah';
$alert_msg  = '';
$alert_type = 'success';
$active_tab = $_GET['tab'] ?? 'pengajar';

$daftar_spesialisasi = [
    'Musyrif Halaqah Tahsin (Dasar)',
    'Musyrif Halaqah Tahfidz (Lanjutan)',
    'Musyrif Pendamping Asrama',
    'Pimpinan Pesantren / Mudir',
    'Petugas Rekapitulasi Internal'
];

// Proses CRUD Pengajar & Halaqah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    // --- CRUD PENGAJAR ---
    if ($aksi === 'tambah_pengajar' || $aksi === 'edit_pengajar') {
        $id                  = (int) ($_POST['id'] ?? 0);
        $nip                 = trim($_POST['nip'] ?? '');
        $nama_pengajar       = trim($_POST['nama_pengajar'] ?? '');
        $jenis_kelamin       = ($_POST['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L';
        $tempat_lahir        = trim($_POST['tempat_lahir'] ?? 'Jakarta');
        $tanggal_lahir       = $_POST['tanggal_lahir'] ?: '1993-01-01';
        $pendidikan_terakhir = trim($_POST['pendidikan_terakhir'] ?? 'S1 Syariah / LIPIA');
        $jumlah_hafalan_juz  = (int) ($_POST['jumlah_hafalan_juz'] ?? 30);
        $spesialisasi        = in_array($_POST['spesialisasi'] ?? '', $daftar_spesialisasi, true) ? $_POST['spesialisasi'] : 'Musyrif Halaqah Tahfidz (Lanjutan)';
        $no_hp               = trim($_POST['no_hp'] ?? '');
        $alamat              = trim($_POST['alamat'] ?? '');
        $tanggal_bergabung   = $_POST['tanggal_bergabung'] ?: date('Y-m-d');
        $status              = ($_POST['status'] ?? 'Aktif') === 'Nonaktif' ? 'Nonaktif' : 'Aktif';

        if ($aksi === 'tambah_pengajar') {
            $stmt = mysqli_prepare($conn, "INSERT INTO pengajar (nip, nama_pengajar, jenis_kelamin, tempat_lahir, tanggal_lahir, pendidikan_terakhir, jumlah_hafalan_juz, spesialisasi, no_hp, alamat, tanggal_bergabung, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssssssisssss', $nip, $nama_pengajar, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $pendidikan_terakhir, $jumlah_hafalan_juz, $spesialisasi, $no_hp, $alamat, $tanggal_bergabung, $status);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Data Asatidz $nama_pengajar berhasil ditambahkan!";
            } else {
                $alert_msg  = 'Gagal menambah pengajar: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE pengajar SET nip=?, nama_pengajar=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, pendidikan_terakhir=?, jumlah_hafalan_juz=?, spesialisasi=?, no_hp=?, alamat=?, tanggal_bergabung=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssssisssssi', $nip, $nama_pengajar, $jenis_kelamin, $tempat_lahir, $tanggal_lahir, $pendidikan_terakhir, $jumlah_hafalan_juz, $spesialisasi, $no_hp, $alamat, $tanggal_bergabung, $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Data Asatidz $nama_pengajar berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal memperbarui pengajar: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus_pengajar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM pengajar WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Data Asatidz berhasil dihapus!';
    }

    // --- CRUD HALAQAH ---
    elseif ($aksi === 'tambah_halaqah' || $aksi === 'edit_halaqah') {
        $active_tab     = 'halaqah';
        $id             = (int) ($_POST['id'] ?? 0);
        $kode_halaqah   = trim($_POST['kode_halaqah'] ?? '');
        $nama_halaqah   = trim($_POST['nama_halaqah'] ?? '');
        $pengajar_id    = (int) ($_POST['pengajar_id'] ?? 1);
        $ruangan        = trim($_POST['ruangan'] ?? 'Ruang Utama Masjid');
        $target_program = trim($_POST['target_program'] ?? 'Tahfidz Reguler');

        if ($aksi === 'tambah_halaqah') {
            $stmt = mysqli_prepare($conn, "INSERT INTO halaqah (kode_halaqah, nama_halaqah, pengajar_id, ruangan, target_program) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'ssiss', $kode_halaqah, $nama_halaqah, $pengajar_id, $ruangan, $target_program);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Kelompok $nama_halaqah berhasil ditambahkan!";
            } else {
                $alert_msg  = 'Gagal menambah halaqah: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE halaqah SET kode_halaqah=?, nama_halaqah=?, pengajar_id=?, ruangan=?, target_program=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssissi', $kode_halaqah, $nama_halaqah, $pengajar_id, $ruangan, $target_program, $id);
            if (mysqli_stmt_execute($stmt)) {
                $alert_msg = "Kelompok $nama_halaqah berhasil diperbarui!";
            } else {
                $alert_msg  = 'Gagal memperbarui halaqah: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'hapus_halaqah') {
        $active_tab = 'halaqah';
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM halaqah WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $alert_msg = 'Kelompok Halaqah berhasil dihapus!';
    }
}

// Query Data Pengajar
$res_pengajar = mysqli_query($conn, "SELECT * FROM pengajar ORDER BY nip ASC");
$all_pengajar = [];
while ($r = mysqli_fetch_assoc($res_pengajar)) {
    $all_pengajar[] = $r;
}

// Query Data Halaqah beserta jumlah santrinya
$sql_halaqah = "SELECT h.*, p.nama_pengajar,
                (SELECT COUNT(*) FROM santri s WHERE s.halaqah_id = h.id AND s.status_santri='Aktif') AS jml_santri
                FROM halaqah h
                LEFT JOIN pengajar p ON h.pengajar_id = p.id
                ORDER BY h.kode_halaqah ASC";
$res_halaqah = mysqli_query($conn, $sql_halaqah);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#0b2220;"><i class="fa-solid fa-user-tie me-2"></i>Data Asatidzah &amp; Kelompok Halaqah</h4>
        <small class="text-muted">Manajemen pengajar tahfidz/tahsin serta pembagian kelompok halaqah santri</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-siata" data-bs-toggle="modal" data-bs-target="#modalTambahPengajar">
            <i class="fa-solid fa-user-plus me-1"></i> Tambah Asatidz
        </button>
        <button class="btn btn-gold-siata" data-bs-toggle="modal" data-bs-target="#modalTambahHalaqah">
            <i class="fa-solid fa-people-group me-1"></i> Tambah Halaqah
        </button>
    </div>
</div>

<?php if ($alert_msg !== ''): ?>
<div class="alert alert-<?= e($alert_type) ?> alert-dismissible fade show shadow-sm border-0" role="alert">
    <i class="fa-solid fa-circle-check me-2"></i> <?= e($alert_msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Navigasi 2 Tab: Tab Pengajar & Tab Kelompok Halaqah -->
<ul class="nav nav-tabs nav-tabs-siata mb-3 bg-white rounded-top px-3 pt-2 border" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'pengajar' ? 'active' : '' ?>" id="tab-pengajar-btn"
                data-bs-toggle="tab" data-bs-target="#panePengajar" type="button" role="tab">
            <i class="fa-solid fa-chalkboard-user me-1"></i> Data Asatidz / Pengajar (<?= count($all_pengajar) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab === 'halaqah' ? 'active' : '' ?>" id="tab-halaqah-btn"
                data-bs-toggle="tab" data-bs-target="#paneHalaqah" type="button" role="tab">
            <i class="fa-solid fa-mosque me-1"></i> Kelompok Halaqah (<?= mysqli_num_rows($res_halaqah) ?>)
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- TAB 1: DATA ASATIDZ / PENGAJAR -->
    <div class="tab-pane fade <?= $active_tab === 'pengajar' ? 'show active' : '' ?>" id="panePengajar" role="tabpanel">
        <div class="card-siata">
            <div class="card-header-siata">
                <h5><i class="fa-solid fa-user-tie"></i> Daftar Asatidzah Rumah Qur'an Qurtuba</h5>
                <span class="badge bg-warning text-dark">Total: <?= count($all_pengajar) ?> Pengajar</span>
            </div>
            <div class="p-3 table-responsive">
                <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIP</th>
                            <th>Nama Asatidz</th>
                            <th>L/P</th>
                            <th>Pendidikan</th>
                            <th>Hafalan</th>
                            <th>Spesialisasi</th>
                            <th>No. HP</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($all_pengajar as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="fw-semibold text-dark"><?= e($row['nip']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($row['nama_pengajar']) ?></div>
                                <small class="text-muted"><?= e($row['tempat_lahir']) ?>, <?= e($row['tanggal_lahir']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $row['jenis_kelamin'] === 'L' ? 'bg-info text-dark' : 'bg-danger-subtle text-danger' ?>">
                                    <?= e($row['jenis_kelamin']) ?>
                                </span>
                            </td>
                            <td><?= e($row['pendidikan_terakhir']) ?></td>
                            <td><span class="badge bg-success-subtle text-success fw-bold"><?= (int) $row['jumlah_hafalan_juz'] ?> Juz</span></td>
                            <td><span class="badge bg-secondary"><?= e($row['spesialisasi']) ?></span></td>
                            <td><?= e($row['no_hp']) ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'Aktif' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-warning btn-sm text-white btn-edit-pengajar"
                                        data-pengajar='<?= e(json_encode($row)) ?>'
                                        data-bs-toggle="modal" data-bs-target="#modalEditPengajar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-hapus-pengajar"
                                        data-id="<?= (int) $row['id'] ?>"
                                        data-nama="<?= e($row['nama_pengajar']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusPengajar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: KELOLA KELOMPOK HALAQAH -->
    <div class="tab-pane fade <?= $active_tab === 'halaqah' ? 'show active' : '' ?>" id="paneHalaqah" role="tabpanel">
        <div class="card-siata">
            <div class="card-header-siata">
                <h5><i class="fa-solid fa-people-group"></i> Daftar Kelompok Halaqah Tahfidz &amp; Tahsin</h5>
            </div>
            <div class="p-3 table-responsive">
                <table class="table table-hover table-striped table-siata datatable-siata align-middle w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Halaqah</th>
                            <th>Nama Kelompok Halaqah</th>
                            <th>Muhaffizh / Pembimbing</th>
                            <th>Ruangan</th>
                            <th>Target Program</th>
                            <th>Jumlah Santri</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($hlq = mysqli_fetch_assoc($res_halaqah)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><span class="badge bg-dark"><?= e($hlq['kode_halaqah']) ?></span></td>
                            <td class="fw-bold text-dark"><?= e($hlq['nama_halaqah']) ?></td>
                            <td class="fw-semibold text-success"><i class="fa-solid fa-user-tie me-1"></i><?= e($hlq['nama_pengajar'] ?? '-') ?></td>
                            <td><?= e($hlq['ruangan']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= e($hlq['target_program']) ?></span></td>
                            <td>
                                <a href="santri.php?halaqah_id=<?= (int) $hlq['id'] ?>" class="badge bg-success text-decoration-none">
                                    <?= (int) $hlq['jml_santri'] ?> Santri
                                </a>
                            </td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-warning btn-sm text-white btn-edit-halaqah"
                                        data-halaqah='<?= e(json_encode($hlq)) ?>'
                                        data-bs-toggle="modal" data-bs-target="#modalEditHalaqah">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-hapus-halaqah"
                                        data-id="<?= (int) $hlq['id'] ?>"
                                        data-nama="<?= e($hlq['nama_halaqah']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusHalaqah">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengajar -->
<div class="modal fade" id="modalTambahPengajar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pengajar.php">
                <input type="hidden" name="aksi" value="tambah_pengajar">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2"></i>Tambah Data Asatidz / Pengajar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">NIP</label>
                            <input type="text" name="nip" class="form-control" placeholder="AST-2026-016" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nama Lengkap &amp; Gelar</label>
                            <input type="text" name="nama_pengajar" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control" value="Jakarta">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="1992-01-01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pendidikan Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" class="form-control" value="S1 LIPIA / Syariah">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Hafalan (Juz)</label>
                            <input type="number" name="jumlah_hafalan_juz" class="form-control" value="30" min="1" max="30">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Peran / Spesialisasi</label>
                            <select name="spesialisasi" class="form-select">
                                <?php foreach ($daftar_spesialisasi as $sp): ?>
                                    <option value="<?= e($sp) ?>"><?= e($sp) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">No. HP / WhatsApp</label>
                            <input type="text" name="no_hp" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Alamat Domisili</label>
                            <input type="text" name="alamat" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Pengajar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pengajar -->
<div class="modal fade" id="modalEditPengajar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pengajar.php">
                <input type="hidden" name="aksi" value="edit_pengajar">
                <input type="hidden" name="id" id="ep_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-user-pen me-2"></i>Edit Data Asatidz / Pengajar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">NIP</label>
                            <input type="text" name="nip" id="ep_nip" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nama Lengkap &amp; Gelar</label>
                            <input type="text" name="nama_pengajar" id="ep_nama" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="ep_jk" class="form-select">
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="ep_tmp" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="ep_tgl" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Pendidikan Terakhir</label>
                            <input type="text" name="pendidikan_terakhir" id="ep_pend" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Hafalan (Juz)</label>
                            <input type="number" name="jumlah_hafalan_juz" id="ep_juz" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Peran / Spesialisasi</label>
                            <select name="spesialisasi" id="ep_spes" class="form-select">
                                <?php foreach ($daftar_spesialisasi as $sp): ?>
                                    <option value="<?= e($sp) ?>"><?= e($sp) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">No. HP / WhatsApp</label>
                            <input type="text" name="no_hp" id="ep_hp" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" id="ep_status" class="form-select">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" id="ep_gabung" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Alamat Domisili</label>
                            <input type="text" name="alamat" id="ep_alamat" class="form-control">
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

<!-- Modal Hapus Pengajar -->
<div class="modal fade" id="modalHapusPengajar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pengajar.php">
                <input type="hidden" name="aksi" value="hapus_pengajar">
                <input type="hidden" name="id" id="hp_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Konfirmasi Hapus</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus data Asatidz:</p>
                    <h6 class="fw-bold text-danger" id="hp_nama"></h6>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Halaqah -->
<div class="modal fade" id="modalTambahHalaqah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pengajar.php?tab=halaqah">
                <input type="hidden" name="aksi" value="tambah_halaqah">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-people-group me-2"></i>Tambah Kelompok Halaqah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Halaqah</label>
                        <input type="text" name="kode_halaqah" class="form-control" placeholder="HLQ-06" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kelompok Halaqah</label>
                        <input type="text" name="nama_halaqah" class="form-control" placeholder="Halaqah Ali bin Abi Thalib" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Muhaffizh / Pembimbing</label>
                        <select name="pengajar_id" class="form-select" required>
                            <?php foreach ($all_pengajar as $pgj): ?>
                                <option value="<?= (int) $pgj['id'] ?>"><?= e($pgj['nama_pengajar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ruangan</label>
                        <input type="text" name="ruangan" class="form-control" value="Ruang Masjid Lt. 1" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Target Program</label>
                        <input type="text" name="target_program" class="form-control" value="Tahfidz 15 Juz" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Simpan Halaqah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Halaqah -->
<div class="modal fade" id="modalEditHalaqah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pengajar.php?tab=halaqah">
                <input type="hidden" name="aksi" value="edit_halaqah">
                <input type="hidden" name="id" id="eh_id">
                <div class="modal-header modal-header-siata">
                    <h5 class="modal-title"><i class="fa-solid fa-pen me-2"></i>Edit Kelompok Halaqah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Halaqah</label>
                        <input type="text" name="kode_halaqah" id="eh_kode" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kelompok Halaqah</label>
                        <input type="text" name="nama_halaqah" id="eh_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Muhaffizh / Pembimbing</label>
                        <select name="pengajar_id" id="eh_pengajar" class="form-select" required>
                            <?php foreach ($all_pengajar as $pgj): ?>
                                <option value="<?= (int) $pgj['id'] ?>"><?= e($pgj['nama_pengajar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ruangan</label>
                        <input type="text" name="ruangan" id="eh_ruangan" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Target Program</label>
                        <input type="text" name="target_program" id="eh_target" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-siata">Update Halaqah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Halaqah -->
<div class="modal fade" id="modalHapusHalaqah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="pengajar.php?tab=halaqah">
                <input type="hidden" name="aksi" value="hapus_halaqah">
                <input type="hidden" name="id" id="hh_id">
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Hapus Halaqah</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="mb-1">Hapus kelompok halaqah:</p>
                    <h6 class="fw-bold text-danger" id="hh_nama"></h6>
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
    document.querySelectorAll('.btn-edit-pengajar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-pengajar'));
            document.getElementById('ep_id').value = d.id;
            document.getElementById('ep_nip').value = d.nip;
            document.getElementById('ep_nama').value = d.nama_pengajar;
            document.getElementById('ep_jk').value = d.jenis_kelamin;
            document.getElementById('ep_tmp').value = d.tempat_lahir || '';
            document.getElementById('ep_tgl').value = d.tanggal_lahir || '';
            document.getElementById('ep_pend').value = d.pendidikan_terakhir || '';
            document.getElementById('ep_juz').value = d.jumlah_hafalan_juz;
            document.getElementById('ep_spes').value = d.spesialisasi;
            document.getElementById('ep_hp').value = d.no_hp || '';
            document.getElementById('ep_status').value = d.status;
            document.getElementById('ep_gabung').value = d.tanggal_bergabung || '';
            document.getElementById('ep_alamat').value = d.alamat || '';
        });
    });

    document.querySelectorAll('.btn-hapus-pengajar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hp_id').value = this.getAttribute('data-id');
            document.getElementById('hp_nama').textContent = this.getAttribute('data-nama');
        });
    });

    document.querySelectorAll('.btn-edit-halaqah').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const d = JSON.parse(this.getAttribute('data-halaqah'));
            document.getElementById('eh_id').value = d.id;
            document.getElementById('eh_kode').value = d.kode_halaqah;
            document.getElementById('eh_nama').value = d.nama_halaqah;
            document.getElementById('eh_pengajar').value = d.pengajar_id;
            document.getElementById('eh_ruangan').value = d.ruangan;
            document.getElementById('eh_target').value = d.target_program;
        });
    });

    document.querySelectorAll('.btn-hapus-halaqah').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('hh_id').value = this.getAttribute('data-id');
            document.getElementById('hh_nama').textContent = this.getAttribute('data-nama');
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
