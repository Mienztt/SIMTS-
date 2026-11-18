<?php
/**
 * FILE: includes/header.php
 * Header Utama, Topbar & Global Stylesheet (DataTables + SIATA Card Theme)
 * Aplikasi SIMTS - Rumah Qur'an Qurtuba
 */
require_once __DIR__ . '/../config/koneksi.php';
cek_login();

$current_page  = basename($_SERVER['PHP_SELF']);
$nama_user     = $_SESSION['nama_lengkap'] ?? 'Admin Lembaga';
$role_user     = $_SESSION['role'] ?? 'admin';
$jabatan_label = $_SESSION['jabatan_label'] ?? 'Admin Lembaga';

// Label yang tampil di Topbar kanan (Contoh: "Admin Lembaga")
$topbar_user_label = ($role_user === 'admin') ? 'Admin Lembaga' : $nama_user;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - SIMTS' : 'SIMTS - Rumah Qur\'an Qurtuba' ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 & Buttons CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom SIMTS Stylesheet -->
    <link href="<?= e(base_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>

    <!-- TOPBAR ATAS -->
    <header class="simts-topbar">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn-hamburger d-lg-none" id="btnToggleSidebar" aria-label="Buka Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div class="dropdown">
            <a href="#" class="user-profile-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar-ring">
                    <span class="avatar-inner">
                        <i class="fa-solid fa-user-tie"></i>
                    </span>
                </span>
                <span class="topbar-username">
                    <?= e($topbar_user_label) ?>
                    <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" style="min-width: 210px; border-radius: 10px;">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-semibold small text-dark"><?= e($nama_user) ?></div>
                    <div class="text-muted" style="font-size: 0.73rem;">Role: <?= e($role_user === 'orang_tua' ? 'Orang Tua Santri' : ucfirst($role_user)) ?> (<?= e($jabatan_label) ?>)</div>
                </li>
                <li>
                    <a class="dropdown-item small py-2" href="<?= e(base_url('index.php')) ?>">
                        <i class="fa-solid fa-gauge-high me-2 text-secondary"></i> Dashboard Utama
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item small py-2 text-danger fw-medium" href="<?= e(base_url('logout.php')) ?>">
                        <i class="fa-solid fa-right-from-bracket me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </header>
