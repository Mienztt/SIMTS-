<?php
/**
 * FILE: config/koneksi.php
 * Konfigurasi Koneksi Database & Helper Fungsi Utama
 * Aplikasi SIMTS - Rumah Qur'an Qurtuba (PHP 8+ & MySQL)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Normalisasi legacy role 'wali' menjadi 'orang_tua'
if (isset($_SESSION['role']) && $_SESSION['role'] === 'wali') {
    $_SESSION['role'] = 'orang_tua';
}

date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Kredensial Database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'db_simts_qurtuba';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die('
    <div style="font-family:sans-serif;max-width:600px;margin:80px auto;padding:30px;border-radius:12px;background:#fff3f3;border:1px solid #f5c2c7;color:#842029;">
        <h3 style="margin-top:0;">Koneksi Database Gagal!</h3>
        <p>Pastikan MySQL (XAMPP/Laragon) sudah berjalan dan database <strong>' . htmlspecialchars($db_name) . '</strong> sudah di-import dari file <code>database/database.sql</code>.</p>
        <hr style="border:none;border-top:1px solid #f5c2c7;">
        <small>Detail Error: ' . htmlspecialchars(mysqli_connect_error()) . '</small>
    </div>');
}

mysqli_set_charset($conn, 'utf8mb4');

// Otomatisasi penyederhanaan penomoran santri (1, 2, 3...) & kolom sesuai dokumen Rumah Quran Qurthuba
$chk_col = mysqli_query($conn, "SHOW COLUMNS FROM santri LIKE 'asal_sekolah'");
if ($chk_col && mysqli_num_rows($chk_col) === 0) {
    @mysqli_query($conn, "ALTER TABLE santri ADD COLUMN asal_sekolah VARCHAR(100) DEFAULT 'SMP / MTs Sederajat' AFTER jenjang");
    @mysqli_query($conn, "ALTER TABLE santri ADD COLUMN status_berkas VARCHAR(60) DEFAULT 'Lengkap (7 Berkas)' AFTER alamat");
    @mysqli_query($conn, "UPDATE santri SET nis = CAST(id AS CHAR), nisn = '-' WHERE nis LIKE 'QRT-%'");
    @mysqli_query($conn, "UPDATE lembaga SET nama_lembaga='Rumah Quran Qurthuba', mudir_pimpinan='Ust. Ridwan Kamal', alamat='Perum. Griya Ranca Indah 1 Blok B1, No. 7 Kel. Jelegong, Kec. Rancaekek, Kab. Bandung', kota='Kab. Bandung', telepon='+62 896-3986-8831 (Ust. Dicky)' WHERE id=1");
}

/**
 * Fungsi Helper Menghasilkan Path Relatif ke Root Proyek SIMTS
 */
function base_url(string $path = ''): string
{
    $root_dir   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $script_dir = isset($_SERVER['SCRIPT_FILENAME'])
        ? str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']))
        : $root_dir;

    $prefix = '';
    if (str_starts_with($script_dir, $root_dir) && $script_dir !== $root_dir) {
        $rel = trim(substr($script_dir, strlen($root_dir)), '/');
        if ($rel !== '') {
            $depth  = substr_count($rel, '/') + 1;
            $prefix = str_repeat('../', $depth);
        }
    }

    $path = ltrim($path, '/');
    return $prefix . $path;
}

/**
 * Fungsi Helper Escape String HTML (Mencegah XSS)
 */
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Fungsi Proteksi Halaman (Wajib Login)
 */
function cek_login(): void
{
    if (!isset($_SESSION['login_simts']) || $_SESSION['login_simts'] !== true) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Fungsi Cek Hak Akses Role (admin, pengajar, orang_tua)
 */
function cek_role(array $allowed_roles): void
{
    cek_login();
    $user_role = $_SESSION['role'] ?? '';
    if (!in_array($user_role, $allowed_roles, true)) {
        header('Location: ' . base_url('index.php?pesan=akses_ditolak'));
        exit;
    }
}

/**
 * Fungsi Helper Menghitung Jumlah Baris Tabel secara Dinamis
 */
function hitung_total(mysqli $conn, string $sql, int $default = 0): int
{
    $res = mysqli_query($conn, $sql);
    if ($res && $row = mysqli_fetch_row($res)) {
        return (int) $row[0];
    }
    return $default;
}
