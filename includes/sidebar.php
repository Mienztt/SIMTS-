<?php
/**
 * FILE: includes/sidebar.php
 * Sidebar Navigasi Kiri SIMTS - Rumah Qur'an Qurtuba (Struktur Modular)
 */
$current_page = basename($_SERVER['PHP_SELF']);
$role         = $_SESSION['role'] ?? 'admin';

if (!function_exists('is_group_active')) {
    function is_group_active(array $pages, string $current_page): bool
    {
        return in_array($current_page, $pages, true);
    }
}

$grp_master      = ['santri.php', 'pengajar.php'];
$grp_tahfidz     = ['target_tahfidz.php', 'setoran_tahfidz.php', 'ujian_tasmi.php'];
$grp_tahsin      = ['setoran_tahsin.php', 'ujian_tahsin.php'];
$grp_mutabaah    = ['master_mutabaah.php', 'mutabaah_santri.php'];
$grp_laporan     = ['evaluasi_tahfidz.php'];
$grp_pelanggaran = ['jenis_pelanggaran.php', 'pelanggaran_input.php', 'pelanggaran_rekap.php'];
$grp_rapor       = ['rapor.php', 'cetak_rapor.php'];
$grp_presensi    = ['presensi.php'];

$jenis_eval = $_GET['jenis'] ?? 'Ziyadah';
$tab_pres   = $_GET['tab'] ?? 'input';
?>

<!-- Overlay Gelap untuk Sidebar Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR KIRI (#0b2220, width: 250px) -->
<aside class="simts-sidebar" id="simtsSidebar">
    <a href="<?= e(base_url('index.php')) ?>" class="sidebar-brand">
        <div class="sidebar-brand-title">SIMTS</div>
        <div class="sidebar-brand-subtitle">
            <span class="cursive-gold">All in One</span>
            ISLAMIC SCHOOL MANAGEMENT SYSTEM
        </div>
    </a>

    <div class="sidebar-menu-scroll">
        <ul class="sidebar-nav" id="sidebarAccordion">

            <!-- 1. Home -->
            <li class="sidebar-item">
                <a href="<?= e(base_url('index.php')) ?>" class="sidebar-link <?= ($current_page === 'index.php') ? 'active' : '' ?>">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-house"></i>
                        <span>Home</span>
                    </span>
                </a>
            </li>

            <!-- 2. Lembaga -->
            <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
            <li class="sidebar-item">
                <a href="<?= e(base_url('modules/pengaturan/lembaga.php')) ?>" class="sidebar-link <?= ($current_page === 'lembaga.php') ? 'active' : '' ?>">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>Lembaga</span>
                    </span>
                </a>
            </li>
            <?php endif; ?>

            <!-- 3. Master -->
            <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
            <?php $active_master = is_group_active($grp_master, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_master ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuMaster" role="button"
                   aria-expanded="<?= $active_master ? 'true' : 'false' ?>" aria-controls="menuMaster">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-database"></i>
                        <span>Master</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_master ? 'show' : '' ?>" id="menuMaster">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="<?= e(base_url('modules/master/santri.php')) ?>" class="sidebar-sublink <?= ($current_page === 'santri.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Data Santri</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(base_url('modules/master/pengajar.php')) ?>" class="sidebar-sublink <?= ($current_page === 'pengajar.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Data Asatidz/Guru</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <!-- 4. Menu Tahfidz -->
            <?php $active_tahfidz = is_group_active($grp_tahfidz, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_tahfidz ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuTahfidz" role="button"
                   aria-expanded="<?= $active_tahfidz ? 'true' : 'false' ?>" aria-controls="menuTahfidz">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-book-quran"></i>
                        <span>Menu Tahfidz</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_tahfidz ? 'show' : '' ?>" id="menuTahfidz">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="<?= e(base_url('modules/tahfidz/target_tahfidz.php')) ?>" class="sidebar-sublink <?= ($current_page === 'target_tahfidz.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Target Tahfidz</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(base_url('modules/tahfidz/setoran_tahfidz.php')) ?>" class="sidebar-sublink <?= ($current_page === 'setoran_tahfidz.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Setoran Ziyadah &amp; Muraja'ah</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(base_url('modules/tahfidz/ujian_tasmi.php')) ?>" class="sidebar-sublink <?= ($current_page === 'ujian_tasmi.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Evaluasi 10 Hal / Tasmi'</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 5. Menu Tahsin -->
            <?php $active_tahsin = is_group_active($grp_tahsin, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_tahsin ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuTahsin" role="button"
                   aria-expanded="<?= $active_tahsin ? 'true' : 'false' ?>" aria-controls="menuTahsin">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-spell-check"></i>
                        <span>Menu Tahsin</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_tahsin ? 'show' : '' ?>" id="menuTahsin">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="<?= e(base_url('modules/tahsin/setoran_tahsin.php')) ?>" class="sidebar-sublink <?= ($current_page === 'setoran_tahsin.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Setoran Tahsin</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(base_url('modules/tahsin/ujian_tahsin.php')) ?>" class="sidebar-sublink <?= ($current_page === 'ujian_tahsin.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Ujian Kenaikan Jilid</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 6. Mutabaah Yaumiyah -->
            <?php $active_mutabaah = is_group_active($grp_mutabaah, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_mutabaah ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuMutabaah" role="button"
                   aria-expanded="<?= $active_mutabaah ? 'true' : 'false' ?>" aria-controls="menuMutabaah">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Mutabaah Yaumiyah</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_mutabaah ? 'show' : '' ?>" id="menuMutabaah">
                    <ul class="sidebar-submenu">
                        <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                        <li>
                            <a href="<?= e(base_url('modules/mutabaah/master_mutabaah.php')) ?>" class="sidebar-sublink <?= ($current_page === 'master_mutabaah.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Kelola Data Mutaba'ah</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= e(base_url('modules/mutabaah/mutabaah_santri.php')) ?>" class="sidebar-sublink <?= ($current_page === 'mutabaah_santri.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span><?= ($role === 'orang_tua') ? "Pantau Mutaba'ah Santri" : "Isi Mutaba'ah Santri" ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 7. Laporan -->
            <?php $active_laporan = is_group_active($grp_laporan, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_laporan ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuLaporan" role="button"
                   aria-expanded="<?= $active_laporan ? 'true' : 'false' ?>" aria-controls="menuLaporan">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Laporan</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_laporan ? 'show' : '' ?>" id="menuLaporan">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="<?= e(base_url('modules/laporan/evaluasi_tahfidz.php?jenis=Ziyadah')) ?>" class="sidebar-sublink <?= ($active_laporan && $jenis_eval !== 'Murajaah') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Evaluasi Ziyadah</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(base_url('modules/laporan/evaluasi_tahfidz.php?jenis=Murajaah')) ?>" class="sidebar-sublink <?= ($active_laporan && $jenis_eval === 'Murajaah') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Evaluasi Muroja'ah</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 8. Pelanggaran & Poin -->
            <?php $active_pelanggaran = is_group_active($grp_pelanggaran, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_pelanggaran ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuPelanggaran" role="button"
                   aria-expanded="<?= $active_pelanggaran ? 'true' : 'false' ?>" aria-controls="menuPelanggaran">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Pelanggaran &amp; Poin</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_pelanggaran ? 'show' : '' ?>" id="menuPelanggaran">
                    <ul class="sidebar-submenu">
                        <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                        <li>
                            <a href="<?= e(base_url('modules/pelanggaran/jenis_pelanggaran.php')) ?>" class="sidebar-sublink <?= ($current_page === 'jenis_pelanggaran.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Jenis Pelanggaran &amp; Poin</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= e(base_url('modules/pelanggaran/pelanggaran_input.php')) ?>" class="sidebar-sublink <?= ($current_page === 'pelanggaran_input.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Input Pelanggaran</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= e(base_url('modules/pelanggaran/pelanggaran_rekap.php')) ?>" class="sidebar-sublink <?= ($current_page === 'pelanggaran_rekap.php') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Rekap Pelanggaran</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 9. Rapor -->
            <?php $active_rapor = is_group_active($grp_rapor, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_rapor ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuRapor" role="button"
                   aria-expanded="<?= $active_rapor ? 'true' : 'false' ?>" aria-controls="menuRapor">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-file-signature"></i>
                        <span>Rapor</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_rapor ? 'show' : '' ?>" id="menuRapor">
                    <ul class="sidebar-submenu">
                        <li>
                            <a href="<?= e(base_url('modules/rapor/rapor.php')) ?>" class="sidebar-sublink <?= $active_rapor ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Rapor Semester</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 10. Presensi Halaqah -->
            <?php $active_presensi = is_group_active($grp_presensi, $current_page); ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= $active_presensi ? 'active' : '' ?>"
                   data-bs-toggle="collapse" href="#menuPresensi" role="button"
                   aria-expanded="<?= $active_presensi ? 'true' : 'false' ?>" aria-controls="menuPresensi">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Presensi Halaqah</span>
                    </span>
                    <i class="fa-solid fa-chevron-down sidebar-arrow"></i>
                </a>
                <div class="collapse <?= $active_presensi ? 'show' : '' ?>" id="menuPresensi">
                    <ul class="sidebar-submenu">
                        <?php if (in_array($role, ['admin', 'pengajar'], true)): ?>
                        <li>
                            <a href="<?= e(base_url('modules/presensi/presensi.php?tab=input')) ?>" class="sidebar-sublink <?= ($active_presensi && $tab_pres !== 'rekap') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Input Presensi</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= e(base_url('modules/presensi/presensi.php?tab=rekap')) ?>" class="sidebar-sublink <?= ($active_presensi && $tab_pres === 'rekap') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle"></i>
                                <span>Rekap Absensi</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- 11. Data User -->
            <?php if ($role === 'admin'): ?>
            <li class="sidebar-item">
                <a href="<?= e(base_url('modules/pengaturan/users.php')) ?>" class="sidebar-link <?= ($current_page === 'users.php') ? 'active' : '' ?>">
                    <span class="sidebar-link-left">
                        <i class="fa-solid fa-users-gear"></i>
                        <span>Data User</span>
                    </span>
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </div>
</aside>

<!-- PEMBUKA AREA KONTEN UTAMA -->
<main class="simts-main-content">
