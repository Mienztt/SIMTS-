<?php
/**
 * FILE: modules/rapor/cetak_rapor.php
 * Lembar Cetak Rapor Semester Resmi Berformat Kertas A4 - Rumah Qur'an Qurtuba
 */
require_once __DIR__ . '/../../config/koneksi.php';
cek_login();

$role     = $_SESSION['role'] ?? 'admin';
$user_id  = (int) ($_SESSION['user_id'] ?? 0);
$id_rapor = (int) ($_GET['id'] ?? 1);

// Ambil Data Lembaga
$res_lembaga = mysqli_query($conn, "SELECT * FROM lembaga ORDER BY id ASC LIMIT 1");
$lembaga     = mysqli_fetch_assoc($res_lembaga) ?: [
    'nama_lembaga'   => "Rumah Qur'an Qurtuba",
    'nsm_npsn'       => '510031740092',
    'mudir_pimpinan' => 'Ust. Ridwan',
    'alamat'         => 'Rumah Wakaf Terpadu Qurtuba, Jl. Qurtuba Raya No. 17',
    'kota'           => 'Jakarta Selatan',
    'telepon'        => '0812-8899-7766',
    'email'          => 'info@qurtuba.sch.id',
    'website'        => 'https://qurtuba.sch.id'
];

// Ambil Data Rapor, Santri, Halaqah & Muhaffizh (Filter Orang Tua Santri jika Role = orang_tua)
$where_ortu = ($role === 'orang_tua') ? "AND s.id_user_orang_tua = $user_id AND r.status_publikasi = 'Terbit'" : "";
$sql = "SELECT r.*, s.nis, s.nisn, s.nama_santri, s.kelas_program, s.jilid_tahsin, s.target_juz, s.nama_orang_tua,
               h.nama_halaqah, p.nama_pengajar, p.nip AS nip_pengajar
        FROM rapor_semester r
        JOIN santri s ON r.santri_id = s.id
        LEFT JOIN halaqah h ON s.halaqah_id = h.id
        LEFT JOIN pengajar p ON r.pengajar_id = p.id
        WHERE r.id = $id_rapor $where_ortu
        LIMIT 1";
$res = mysqli_query($conn, $sql);
$rapor = mysqli_fetch_assoc($res);

if (!$rapor) {
    // Fallback ke rapor pertama yang berhak diakses jika ID tidak ditemukan
    $res_first = mysqli_query($conn, "SELECT r.*, s.nis, s.nisn, s.nama_santri, s.kelas_program, s.jilid_tahsin, s.target_juz, s.nama_orang_tua, h.nama_halaqah, p.nama_pengajar, p.nip AS nip_pengajar FROM rapor_semester r JOIN santri s ON r.santri_id = s.id LEFT JOIN halaqah h ON s.halaqah_id = h.id LEFT JOIN pengajar p ON r.pengajar_id = p.id WHERE 1=1 $where_ortu ORDER BY r.id ASC LIMIT 1");
    $rapor = mysqli_fetch_assoc($res_first);
}

if (!$rapor) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">Data rapor belum tersedia atau belum diterbitkan untuk akun Anda. Silakan kembali ke halaman <a href="rapor.php">Rapor Semester</a>.</div>');
}

function predikat_nilai(float $n): string
{
    if ($n >= 90) return 'Mumtaz (Istimewa)';
    if ($n >= 80) return 'Jayyid Jiddan (Sangat Baik)';
    if ($n >= 70) return 'Jayyid (Baik)';
    return 'Maqbul (Cukup)';
}

$poin_plg = (int) $rapor['total_poin_pelanggaran'];
$status_disiplin = $poin_plg >= 50 ? 'Kartu Merah / Evaluasi Beasiswa' : ($poin_plg >= 30 ? 'Kartu Kuning / SP-1' : ($poin_plg >= 15 ? 'Teguran Lisan' : 'Aman'));
$rata_akhir = round(((float) $rapor['nilai_tahfidz'] + (float) $rapor['nilai_tahsin'] + (float) $rapor['nilai_mutabaah']) / 3, 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rapor Semester - <?= e($rapor['nama_santri']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #cbd5e1;
            font-family: 'Times New Roman', Times, serif;
            color: #000000;
            margin: 0;
            padding: 25px 0;
        }

        .toolbar-no-print {
            max-width: 210mm;
            margin: 0 auto 15px;
            background: #0b2220;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: sans-serif;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 16mm 18mm;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.22);
            position: relative;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3.5px double #0b2220;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .kop-logo {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 2.5px solid #0b2220;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.3rem;
            color: #0b2220;
            margin-right: 18px;
            flex-shrink: 0;
        }

        .kop-teks {
            text-align: center;
            flex-grow: 1;
        }

        .kop-yayasan {
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .kop-nama {
            font-size: 17pt;
            font-weight: 900;
            color: #0b2220;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 2px 0;
        }

        .kop-alamat {
            font-size: 9.5pt;
            color: #333;
        }

        .judul-rapor {
            text-align: center;
            font-weight: bold;
            font-size: 12.5pt;
            text-transform: uppercase;
            margin-bottom: 14px;
            text-decoration: underline;
        }

        .tabel-biodata {
            width: 100%;
            font-size: 10.5pt;
            margin-bottom: 14px;
        }

        .tabel-biodata td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            background: #f1f5f9;
            padding: 4px 8px;
            border-left: 4px solid #0b2220;
            margin: 10px 0 6px;
            text-transform: uppercase;
        }

        .tabel-nilai {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 10px;
        }

        .tabel-nilai th, .tabel-nilai td {
            border: 1px solid #000000;
            padding: 5px 8px;
            vertical-align: middle;
        }

        .tabel-nilai th {
            background-color: #e2e8f0;
            text-align: center;
            font-weight: bold;
        }

        .kotak-catatan {
            border: 1px solid #000000;
            padding: 8px 10px;
            font-size: 10pt;
            min-height: 46px;
            margin-bottom: 10px;
        }

        .ttd-table {
            width: 100%;
            margin-top: 22px;
            font-size: 10.5pt;
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .toolbar-no-print {
                display: none !important;
            }
            .a4-page {
                box-shadow: none;
                width: 100%;
                min-height: auto;
                padding: 8mm 10mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Cetak (Tidak ikut tercetak saat diprint) -->
    <div class="toolbar-no-print">
        <div>
            <strong><i class="fa-solid fa-file-pdf me-2 text-warning"></i>Pratinjau Cetak Rapor A4</strong>
            <span class="small ms-2 text-light opacity-75"><?= e($rapor['nama_santri']) ?> (<?= e($rapor['semester']) ?> - <?= e($rapor['tahun_ajaran']) ?>)</span>
        </div>
        <div class="d-flex gap-2">
            <a href="rapor.php" class="btn btn-outline-light btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
            <button onclick="window.print()" class="btn btn-warning btn-sm fw-bold">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF (A4)
            </button>
        </div>
    </div>

    <!-- Lembar Kertas A4 -->
    <div class="a4-page">
        <!-- Kop Surat Lembaga -->
        <div class="kop-surat">
            <div class="kop-logo">
                <i class="fa-solid fa-mosque"></i>
            </div>
            <div class="kop-teks">
                <div class="kop-yayasan">YAYASAN PENDIDIKAN ISLAM &amp; TAHFIDZUL QUR'AN</div>
                <div class="kop-nama"><?= e($lembaga['nama_lembaga']) ?></div>
                <div class="kop-alamat">
                    NSM/NPSN: <?= e($lembaga['nsm_npsn']) ?> &bull; <?= e($lembaga['alamat']) ?>, <?= e($lembaga['kota']) ?><br>
                    Telp: <?= e($lembaga['telepon']) ?> &bull; Email: <?= e($lembaga['email']) ?> &bull; Website: <?= e($lembaga['website']) ?>
                </div>
            </div>
        </div>

        <div class="judul-rapor">
            LAPORAN HASIL EVALUASI TAHFIDZ, TAHSIN &amp; MUTABA'AH SANTRI
        </div>

        <!-- Biodata Santri -->
        <table class="tabel-biodata">
            <tr>
                <td style="width: 18%;">Nama Santri</td>
                <td style="width: 36%;">: <strong><?= e($rapor['nama_santri']) ?></strong></td>
                <td style="width: 20%;">Tahun Ajaran</td>
                <td style="width: 26%;">: <?= e($rapor['tahun_ajaran']) ?></td>
            </tr>
            <tr>
                <td>No. Urut Santri</td>
                <td>: <?= e($rapor['nis']) ?> / <?= e($rapor['nisn'] ?: '-') ?></td>
                <td>Semester</td>
                <td>: <strong><?= e($rapor['semester']) ?></strong></td>
            </tr>
            <tr>
                <td>Program / Kelas</td>
                <td>: <?= e($rapor['kelas_program']) ?></td>
                <td>Kelompok Halaqah</td>
                <td>: <?= e($rapor['nama_halaqah'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>Muhaffizh/ah</td>
                <td>: <?= e($rapor['nama_pengajar'] ?? '-') ?></td>
                <td>Target Program</td>
                <td>: <?= (int) $rapor['target_juz'] ?> Juz (Jilid: <?= e($rapor['jilid_tahsin']) ?>)</td>
            </tr>
        </table>

        <!-- Bagian A: Nilai Tahfidz & Tahsin -->
        <div class="section-title">A. CAPAIAN HAFALAN &amp; KUALITAS BACAAN AL-QUR'AN (TAHFIDZ &amp; TAHSIN)</div>
        <table class="tabel-nilai">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th style="width: 44%;">Komponen Penilaian</th>
                    <th style="width: 20%;">Capaian / Nilai</th>
                    <th style="width: 30%;">Predikat / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>Total Akumulasi Hafalan Al-Qur'an</td>
                    <td class="text-center fw-bold"><?= (float) $rapor['total_hafalan_juz'] ?> Juz</td>
                    <td>Target Program: <?= (int) $rapor['target_juz'] ?> Juz</td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>Capaian Setoran Ziyadah Semester Ini</td>
                    <td class="text-center fw-bold"><?= (int) $rapor['capaian_ziyadah_halaman'] ?> Halaman</td>
                    <td>Tuntas Ujian 10 Halaman / Tasmi'</td>
                </tr>
                <tr>
                    <td class="text-center">3</td>
                    <td>Nilai Kualitas Tahfidz (Kelancaran, Tajwid &amp; Makhraj)</td>
                    <td class="text-center fw-bold"><?= number_format((float) $rapor['nilai_tahfidz'], 2) ?></td>
                    <td><?= predikat_nilai((float) $rapor['nilai_tahfidz']) ?></td>
                </tr>
                <tr>
                    <td class="text-center">4</td>
                    <td>Nilai Tahsin Tilawah (Makharijul Huruf, Shifat, Mad &amp; Ghunnah)</td>
                    <td class="text-center fw-bold"><?= number_format((float) $rapor['nilai_tahsin'], 2) ?></td>
                    <td><?= predikat_nilai((float) $rapor['nilai_tahsin']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Bagian B: Mutaba'ah Yaumiyah & Akhlak -->
        <div class="section-title">B. MUTABA'AH YAUMIYAH (IBADAH HARIAN) &amp; ADAB AKHLAK SANTRI</div>
        <table class="tabel-nilai">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th style="width: 44%;">Aspek Pembinaan Ibadah &amp; Karakter</th>
                    <th style="width: 20%;">Nilai / Grade</th>
                    <th style="width: 30%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td>Pelaksanaan Mutaba'ah Yaumiyah (Shalat Berjamaah, Qiyamul Lail, Dhuha, Tilawah)</td>
                    <td class="text-center fw-bold"><?= number_format((float) $rapor['nilai_mutabaah'], 2) ?></td>
                    <td><?= predikat_nilai((float) $rapor['nilai_mutabaah']) ?></td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td>Adab terhadap Al-Qur'an, Muhaffizh, Orang Tua &amp; Sesama Santri</td>
                    <td class="text-center fw-bold">Grade <?= e($rapor['nilai_akhlak']) ?></td>
                    <td><?= $rapor['nilai_akhlak'] === 'A' ? 'Sangat Baik & Beradab Mulia' : 'Baik & Terbina' ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end fw-bold">RATA-RATA NILAI AKHIR SEMESTER</td>
                    <td class="text-center fw-bold"><?= number_format($rata_akhir, 2) ?></td>
                    <td class="fw-bold"><?= predikat_nilai($rata_akhir) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Bagian C: Presensi & Poin Kedisiplinan -->
        <div class="section-title">C. KEHADIRAN HALAQAH &amp; REKAPITULASI KEDISIPLINAN</div>
        <table class="tabel-nilai">
            <thead>
                <tr>
                    <th>Hadir</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alpa</th>
                    <th>Total Poin Pelanggaran</th>
                    <th>Status Kedisiplinan</th>
                </tr>
            </thead>
            <tbody>
                <tr class="text-center">
                    <td class="fw-bold"><?= (int) $rapor['jumlah_hadir'] ?> Sesi</td>
                    <td><?= (int) $rapor['jumlah_sakit'] ?> Sesi</td>
                    <td><?= (int) $rapor['jumlah_izin'] ?> Sesi</td>
                    <td><?= (int) $rapor['jumlah_alpa'] ?> Sesi</td>
                    <td class="fw-bold"><?= $poin_plg ?> Poin</td>
                    <td class="fw-bold"><?= e($status_disiplin) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Bagian D: Catatan Muhaffizh & Mudir -->
        <div class="section-title">D. CATATAN EVALUASI MUHAFFIZH &amp; MUDIR LEMBAGA</div>
        <div class="kotak-catatan">
            <strong>Catatan Muhaffizh:</strong><br>
            <?= e($rapor['catatan_muhaffizh'] ?: '-') ?>
        </div>
        <div class="kotak-catatan">
            <strong>Catatan Mudir / Pimpinan:</strong><br>
            <?= e($rapor['catatan_mudir'] ?: '-') ?>
        </div>

        <!-- Bagian Tanda Tangan -->
        <table class="ttd-table">
            <tr>
                <td style="width: 33%;">
                    Mengetahui,<br>Orang Tua Santri
                    <br><br><br><br>
                    <strong><u>( <?= e($rapor['nama_orang_tua'] ?: '....................................') ?> )</u></strong>
                </td>
                <td style="width: 33%;">
                    <br>Muhaffizh / Pembimbing Halaqah
                    <br><br><br><br>
                    <strong><u><?= e($rapor['nama_pengajar'] ?? '....................................') ?></u></strong><br>
                    <small>NIP: <?= e($rapor['nip_pengajar'] ?? '-') ?></small>
                </td>
                <td style="width: 34%;">
                    <?= e($lembaga['kota']) ?>, <?= e($rapor['tanggal_terbit'] ?: date('Y-m-d')) ?><br>
                    Mudir <?= e($lembaga['nama_lembaga']) ?>
                    <br><br><br><br>
                    <strong><u><?= e($lembaga['mudir_pimpinan']) ?></u></strong>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
