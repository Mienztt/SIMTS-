-- ============================================================================
-- DATABASE: db_simts_qurtuba
-- Aplikasi: SIMTS (Sistem Informasi Manajemen Tahfidz Santri)
-- Lembaga : Rumah Qur'an Qurtuba (Berdiri Legal: 19 November 2021)
-- Sinkron 100% dengan Dokumen Laporan Revisi Terbaru
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `db_simts_qurtuba` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_simts_qurtuba`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. TABEL LEMBAGA
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `lembaga`;
CREATE TABLE `lembaga` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_lembaga` VARCHAR(150) NOT NULL,
  `nsm_npsn` VARCHAR(50) DEFAULT NULL,
  `mudir_pimpinan` VARCHAR(120) NOT NULL,
  `alamat` TEXT NOT NULL,
  `kota` VARCHAR(80) DEFAULT 'Jakarta',
  `telepon` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `website` VARCHAR(100) DEFAULT NULL,
  `tahun_ajaran_aktif` VARCHAR(20) DEFAULT '2025/2026',
  `semester_aktif` ENUM('Ganjil','Genap') DEFAULT 'Genap',
  `logo` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lembaga` (`id`, `nama_lembaga`, `nsm_npsn`, `mudir_pimpinan`, `alamat`, `kota`, `telepon`, `email`, `website`, `tahun_ajaran_aktif`, `semester_aktif`) VALUES
(1, 'Rumah Quran Qurthuba', '510031740092', 'Ust. Ridwan Kamal', 'Perum. Griya Ranca Indah 1 Blok B1, No. 7 Kel. Jelegong, Kec. Rancaekek, Kab. Bandung', 'Kab. Bandung', '+62 896-3986-8831 (Ust. Dicky)', 'info@qurtuba.sch.id', 'https://qurtuba.sch.id', '2025/2026', 'Genap');

-- ----------------------------------------------------------------------------
-- 2. TABEL USERS (Admin, Pengajar, Orang Tua Santri)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_lengkap` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','pengajar','orang_tua') NOT NULL DEFAULT 'orang_tua',
  `jabatan_label` VARCHAR(80) DEFAULT 'Orang Tua Santri',
  `no_hp` VARCHAR(25) DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `status_aktif` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password default:
-- admin     -> admin123
-- pengajar  -> pengajar123
-- orang_tua -> orang_tua123
INSERT INTO `users` (`id`, `nama_lengkap`, `username`, `password`, `role`, `jabatan_label`, `no_hp`, `status_aktif`) VALUES
(1, 'Admin / Ust. Ridwan (Mudir)', 'admin', '$2y$10$1fg4PNpvhOlKq5hKNcV.DOVIJEAfnrxkFJFxdqWicNuzKXmpo.tim', 'admin', 'Pimpinan & Admin Pesantren', '081210000001', 'Aktif'),
(2, 'Musyrif & Petugas Rekap (A Yudha)', 'pengajar', '$2y$10$P3cBzPhabRrpFWb0yU90b.QtCPDYQ4o92YMlT1HiSxqli6h6xE9Si', 'pengajar', 'Musyrif Halaqah & Asrama', '081210000002', 'Aktif'),
(3, 'Bpk. H. Sulaiman', 'orang_tua', '$2y$10$LWNcJ9XOzmxr6jTes2FY8.Oujow3J81WOTvfqSnVOW78vgzbeOhz.', 'orang_tua', 'Orang Tua Santri', '081210000003', 'Aktif');

-- ----------------------------------------------------------------------------
-- 3. TABEL PENGAJAR / MUSYRIF (15 Data Aktif)
-- Spesialisasi tugas:
--   - Musyrif Halaqah Tahsin (Dasar)
--   - Musyrif Halaqah Tahfidz (Lanjutan)
--   - Musyrif Pendamping Asrama
--   - Pimpinan Pesantren / Mudir & Petugas Rekapitulasi Internal
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `pengajar`;
CREATE TABLE `pengajar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `nip` VARCHAR(30) NOT NULL UNIQUE,
  `nama_pengajar` VARCHAR(120) NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `tempat_lahir` VARCHAR(60) DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `pendidikan_terakhir` VARCHAR(80) DEFAULT 'S1 Syariah / LIPIA',
  `jumlah_hafalan_juz` INT DEFAULT 30,
  `spesialisasi` VARCHAR(80) DEFAULT 'Musyrif Halaqah Tahfidz (Lanjutan)',
  `no_hp` VARCHAR(25) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `tanggal_bergabung` DATE DEFAULT '2021-11-19',
  `status` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pengajar_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pengajar` (`id`, `user_id`, `nip`, `nama_pengajar`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `pendidikan_terakhir`, `jumlah_hafalan_juz`, `spesialisasi`, `no_hp`, `alamat`, `tanggal_bergabung`, `status`) VALUES
(1, 1, 'QRT-STF-001', 'Ust. Ridwan', 'L', 'Bandung', '1988-05-14', 'S1 Ilmu Al-Qur''an & Tafsir', 30, 'Pimpinan Pesantren / Mudir', '081290000001', 'Rumah Wakaf Terpadu Qurtuba', '2021-11-19', 'Aktif'),
(2, NULL, 'QRT-STF-002', 'Ust. Ahmad Fauzi, Lc.', 'L', 'Jakarta', '1991-04-12', 'S1 LIPIA Jakarta', 30, 'Musyrif Halaqah Tahfidz (Lanjutan)', '081290000002', 'Asrama Qurtuba Blok A', '2021-11-19', 'Aktif'),
(3, NULL, 'QRT-STF-003', 'Ust. Abdurrahman Wahid, S.Pd.I', 'L', 'Bogor', '1990-11-03', 'S1 PAI UIN Jakarta', 30, 'Musyrif Halaqah Tahfidz (Lanjutan)', '081290000003', 'Asrama Qurtuba Blok A', '2022-01-10', 'Aktif'),
(4, NULL, 'QRT-STF-004', 'Ust. Salman Al-Farisi, Lc.', 'L', 'Depok', '1994-01-19', 'S1 Universitas Al-Azhar', 30, 'Musyrif Halaqah Tahfidz (Lanjutan)', '081290000004', 'Asrama Qurtuba Blok B', '2022-07-01', 'Aktif'),
(5, NULL, 'QRT-STF-005', 'Ust. Zaid bin Tsabit, M.Ag.', 'L', 'Surabaya', '1989-06-14', 'S2 Ilmu Al-Qur''an', 30, 'Musyrif Halaqah Tahsin (Dasar)', '081290000005', 'Asrama Qurtuba Blok B', '2022-07-01', 'Aktif'),
(6, NULL, 'QRT-STF-006', 'Ust. Hamzah Fansuri, Al-Hafizh', 'L', 'Aceh', '1995-03-27', 'STIU Darul Hikmah', 30, 'Musyrif Halaqah Tahsin (Dasar)', '081290000006', 'Asrama Qurtuba Blok C', '2023-01-15', 'Aktif'),
(7, NULL, 'QRT-STF-007', 'Ust. Bilal Rabah, S.Ag.', 'L', 'Bekasi', '1993-09-10', 'S1 Ilmu Al-Qur''an & Tafsir', 20, 'Musyrif Halaqah Tahsin (Dasar)', '081290000007', 'Asrama Qurtuba Blok C', '2023-07-10', 'Aktif'),
(8, NULL, 'QRT-STF-008', 'Ust. Umar Faruq, Al-Hafizh', 'L', 'Yogyakarta', '1992-12-05', 'Ponpes Yanbu''ul Qur''an', 30, 'Musyrif Pendamping Asrama', '081290000008', 'Rumah Wakaf Terpadu Qurtuba', '2023-07-10', 'Aktif'),
(9, NULL, 'QRT-STF-009', 'Ust. Hasan Al-Banna, S.Pd.', 'L', 'Garut', '1996-02-18', 'S1 Pendidikan Agama Islam', 18, 'Musyrif Pendamping Asrama', '081290000009', 'Rumah Wakaf Terpadu Qurtuba', '2024-01-08', 'Aktif'),
(10, NULL, 'QRT-STF-010', 'Ust. Luthfi Hakim, Al-Hafizh', 'L', 'Cirebon', '1997-08-22', 'Ma''had Aly Tahfidz', 30, 'Musyrif Pendamping Asrama', '081290000010', 'Rumah Wakaf Terpadu Qurtuba', '2024-07-01', 'Aktif'),
(11, NULL, 'QRT-STF-011', 'Ustdzh. Aisyah Humaira, Lc.', 'P', 'Jakarta', '1994-05-16', 'S1 LIPIA Jakarta', 30, 'Musyrif Halaqah Tahfidz (Lanjutan)', '081290000011', 'Gedung Pembinaan Putri', '2022-07-15', 'Aktif'),
(12, NULL, 'QRT-STF-012', 'Ustdzh. Fatimah Az-Zahra, Al-Hafizhah', 'P', 'Solo', '1996-07-22', 'Ma''had Tahfidz Isy Karima', 30, 'Musyrif Halaqah Tahsin (Dasar)', '081290000012', 'Gedung Pembinaan Putri', '2023-07-15', 'Aktif'),
(13, NULL, 'QRT-STF-013', 'Ustdzh. Khadijah Kubra, S.Pd.', 'P', 'Tangerang', '1993-02-11', 'S1 PGMI', 25, 'Musyrif Pendamping Asrama', '081290000013', 'Gedung Pembinaan Putri', '2024-01-10', 'Aktif'),
(14, NULL, 'QRT-STF-014', 'Ustdzh. Maryam Qanitah, Al-Hafizhah', 'P', 'Malang', '1997-10-30', 'STIQ Zad Cianjur', 30, 'Musyrif Halaqah Tahsin (Dasar)', '081290000014', 'Gedung Pembinaan Putri', '2024-07-15', 'Aktif'),
(15, 2, 'QRT-STF-015', 'A Yudha (Petugas Rekapitulasi)', 'L', 'Sumedang', '2007-04-09', 'Santri Senior / Amanah Rekap', 15, 'Petugas Rekapitulasi Internal', '081290000015', 'Rumah Wakaf Terpadu Qurtuba', '2023-07-15', 'Aktif');

-- ----------------------------------------------------------------------------
-- 4. TABEL HALAQAH (Kelompok Bimbingan Musyrif)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `halaqah`;
CREATE TABLE `halaqah` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_halaqah` VARCHAR(20) NOT NULL UNIQUE,
  `nama_halaqah` VARCHAR(100) NOT NULL,
  `pengajar_id` INT NOT NULL,
  `ruangan` VARCHAR(60) DEFAULT 'Ruang Utama Masjid',
  `target_program` VARCHAR(80) DEFAULT 'Tahfidz Lanjutan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_halaqah_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `halaqah` (`id`, `kode_halaqah`, `nama_halaqah`, `pengajar_id`, `ruangan`, `target_program`) VALUES
(1, 'HLQ-01', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 2, 'Masjid Qurtuba Utama', 'Tahfidz Lanjutan'),
(2, 'HLQ-02', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 3, 'Aula Rumah Wakaf Lt. 1', 'Tahfidz Lanjutan'),
(3, 'HLQ-03', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 4, 'Saung Tahfidz Qurtuba', 'Tahfidz Lanjutan'),
(4, 'HLQ-04', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 5, 'Ruang Belajar Dasar A', 'Tahsin Dasar'),
(5, 'HLQ-05', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 6, 'Ruang Belajar Dasar B', 'Tahsin Dasar');

-- ----------------------------------------------------------------------------
-- 5. TABEL SANTRI (tb_santri - 58 Data Lintas Angkatan 2021-2026)
--   - 31 Santri Aktif : 8 (2023/2024), 12 (2024/2025), 11 (2025/2026)
--   -  6 Santri Alumni: 4 (2021/2022),  2 (2022/2023)
--   - 21 Santri Keluar: 6 (2021/2022),  6 (2022/2023), 4 (2023/2024), 3 (2024/2025), 2 (2025/2026)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `santri`;
CREATE TABLE `santri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_santri` INT GENERATED ALWAYS AS (`id`) VIRTUAL,
  `nis` VARCHAR(30) NOT NULL UNIQUE,
  `nisn` VARCHAR(30) DEFAULT NULL,
  `nama_santri` VARCHAR(120) NOT NULL,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `tempat_lahir` VARCHAR(60) DEFAULT 'Jakarta',
  `tanggal_lahir` DATE DEFAULT '2012-01-01',
  `usia` INT NOT NULL DEFAULT 14,
  `jenjang` ENUM('SMP','SMA') NOT NULL DEFAULT 'SMP',
  `tahun_angkatan` VARCHAR(20) NOT NULL DEFAULT '2025/2026',
  `kategori_halaqah` ENUM('Tahsin Dasar','Tahfidz Lanjutan') NOT NULL DEFAULT 'Tahsin Dasar',
  `kelompok_musyrif` VARCHAR(100) NOT NULL DEFAULT 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)',
  `kelas_program` VARCHAR(50) DEFAULT 'Tahun ke-1 (Tingkat Dasar)',
  `jilid_tahsin` VARCHAR(40) DEFAULT 'Al-Qur''an / Tajwid',
  `target_juz` INT NOT NULL DEFAULT 5,
  `total_hafalan_juz` DECIMAL(4,1) DEFAULT 1.0,
  `halaqah_id` INT DEFAULT 1,
  `pengajar_id` INT DEFAULT 1,
  `id_user_orang_tua` INT DEFAULT 3,
  `nama_orang_tua` VARCHAR(120) DEFAULT NULL,
  `no_hp_orang_tua` VARCHAR(25) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `tanggal_masuk` DATE DEFAULT '2025-07-15',
  `status_santri` ENUM('Aktif','Alumni','Keluar') NOT NULL DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_santri_halaqah` FOREIGN KEY (`halaqah_id`) REFERENCES `halaqah`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_santri_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_santri_ortu` FOREIGN KEY (`id_user_orang_tua`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `santri` (`id`, `nis`, `nisn`, `nama_santri`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `usia`, `jenjang`, `tahun_angkatan`, `kategori_halaqah`, `kelompok_musyrif`, `kelas_program`, `jilid_tahsin`, `target_juz`, `total_hafalan_juz`, `halaqah_id`, `pengajar_id`, `id_user_orang_tua`, `nama_orang_tua`, `no_hp_orang_tua`, `alamat`, `tanggal_masuk`, `status_santri`) VALUES
-- Angkatan 2021/2022 (10 Santri: 4 Alumni, 6 Keluar, 0 Aktif)
(1, '1', '0081001001', 'Ahmad Fauzan Hakim', 'L', 'Jakarta', '2008-03-11', 18, 'SMA', '2021/2022', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Lulus Program 3 Tahun', 'Al-Qur''an / Tajwid', 30, 30.0, 1, 2, NULL, 'Bpk. Lukman Hakim', '081281000001', 'Jakarta Selatan', '2021-11-19', 'Alumni'),
(2, '2', '0081001002', 'Rizki Ramadhan Putra', 'L', 'Depok', '2008-06-19', 18, 'SMA', '2021/2022', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Lulus Program 3 Tahun', 'Al-Qur''an / Tajwid', 30, 25.0, 1, 2, NULL, 'Ibu Siti Aminah', '081281000002', 'Depok', '2021-11-19', 'Alumni'),
(3, '3', '0081001003', 'Luqman Al-Farizi', 'L', 'Bogor', '2009-01-24', 17, 'SMA', '2021/2022', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Lulus Program 3 Tahun', 'Al-Qur''an / Tajwid', 20, 20.0, 1, 2, NULL, 'Bpk. Mansur', '081281000003', 'Bogor', '2021-11-19', 'Alumni'),
(4, '4', '0081001004', 'Fadhlan Maulana', 'L', 'Bekasi', '2008-09-05', 18, 'SMA', '2021/2022', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Lulus Program 3 Tahun', 'Al-Qur''an / Tajwid', 20, 18.0, 1, 2, NULL, 'Ibu Rahmawati', '081281000004', 'Bekasi', '2021-11-19', 'Alumni'),
(5, '5', '0081001005', 'Daffa Pratama', 'L', 'Jakarta', '2009-02-14', 17, 'SMA', '2021/2022', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Program 3 Tahun', 'Qurtuba Jilid 5', 10, 3.0, 4, 5, NULL, 'Bpk. Hendra', '081281000005', 'Jakarta Timur', '2021-11-19', 'Keluar'),
(6, '6', '0081001006', 'Galih Permana', 'L', 'Tangerang', '2009-04-17', 17, 'SMA', '2021/2022', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Program 3 Tahun', 'Qurtuba Jilid 4', 10, 2.0, 4, 5, NULL, 'Bpk. Wahyudi', '081281000006', 'Tangerang', '2021-11-19', 'Keluar'),
(7, '7', '0081001007', 'Ilham Saputra', 'L', 'Bogor', '2009-07-29', 17, 'SMA', '2021/2022', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Program 3 Tahun', 'Qurtuba Jilid 6', 10, 4.0, 4, 5, NULL, 'Bpk. Agus', '081281000007', 'Bogor', '2021-11-19', 'Keluar'),
(8, '8', '0081001008', 'Rafi Akbar', 'L', 'Jakarta', '2009-10-12', 17, 'SMA', '2021/2022', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Program 3 Tahun', 'Qurtuba Jilid 3', 10, 1.5, 5, 6, NULL, 'Ibu Nurjanah', '081281000008', 'Jakarta Barat', '2021-11-19', 'Keluar'),
(9, '9', '0081001009', 'Bagas Nugroho', 'L', 'Depok', '2008-12-03', 18, 'SMA', '2021/2022', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Program 3 Tahun', 'Qurtuba Jilid 5', 10, 2.5, 5, 6, NULL, 'Bpk. Bambang', '081281000009', 'Depok', '2021-11-19', 'Keluar'),
(10, '10', '0081001010', 'Taufik Hidayat', 'L', 'Bekasi', '2009-05-20', 17, 'SMA', '2021/2022', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Program 3 Tahun', 'Qurtuba Jilid 4', 10, 2.0, 5, 6, NULL, 'Ibu Maryam', '081281000010', 'Bekasi', '2021-11-19', 'Keluar'),

-- Angkatan 2022/2023 (8 Santri: 2 Alumni, 6 Keluar, 0 Aktif)
(11, '11', '0092001011', 'Farhan Abdillah', 'L', 'Jakarta', '2009-02-08', 17, 'SMA', '2022/2023', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Lulus Program 3 Tahun', 'Al-Qur''an / Tajwid', 30, 30.0, 1, 2, NULL, 'Bpk. Abdillah', '081282000011', 'Jakarta Selatan', '2022-07-15', 'Alumni'),
(12, '12', '0092001012', 'Muhammad Raihan', 'L', 'Bandung', '2009-08-15', 17, 'SMA', '2022/2023', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Lulus Program 3 Tahun', 'Al-Qur''an / Tajwid', 25, 21.5, 1, 2, NULL, 'Ibu Kurnia', '081282000012', 'Bandung', '2022-07-15', 'Alumni'),
(13, '13', '0092001013', 'Azka Firdaus', 'L', 'Depok', '2010-01-19', 16, 'SMA', '2022/2023', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Program 3 Tahun', 'Qurtuba Jilid 6', 10, 4.5, 4, 5, NULL, 'Bpk. Firdaus', '081282000013', 'Depok', '2022-07-15', 'Keluar'),
(14, '14', '0092001014', 'Yoga Pratama', 'L', 'Bogor', '2010-03-22', 16, 'SMA', '2022/2023', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Program 3 Tahun', 'Qurtuba Jilid 4', 10, 2.0, 4, 5, NULL, 'Bpk. Sutrisno', '081282000014', 'Bogor', '2022-07-15', 'Keluar'),
(15, '15', '0092001015', 'Reno Wijaya', 'L', 'Jakarta', '2010-05-11', 16, 'SMA', '2022/2023', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Program 3 Tahun', 'Qurtuba Jilid 3', 10, 1.0, 5, 6, NULL, 'Bpk. Heri', '081282000015', 'Jakarta Pusat', '2022-07-15', 'Keluar'),
(16, '16', '0092001016', 'Kevin Maulana', 'L', 'Bekasi', '2009-11-04', 17, 'SMA', '2022/2023', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Program 3 Tahun', 'Qurtuba Jilid 5', 10, 3.0, 5, 6, NULL, 'Bpk. Suryadi', '081282000016', 'Bekasi', '2022-07-15', 'Keluar'),
(17, '17', '0092001017', 'Nabil Al-Ghozali', 'L', 'Tangerang', '2010-07-14', 16, 'SMA', '2022/2023', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Program 3 Tahun', 'Qurtuba Jilid 5', 10, 2.5, 4, 5, NULL, 'Ibu Halimah', '081282000017', 'Tangerang', '2022-07-15', 'Keluar'),
(18, '18', '0092001018', 'Dimas Anggara', 'L', 'Jakarta', '2010-09-28', 16, 'SMA', '2022/2023', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Program 3 Tahun', 'Qurtuba Jilid 4', 10, 1.5, 5, 6, NULL, 'Bpk. Supardi', '081282000018', 'Jakarta Utara', '2022-07-15', 'Keluar'),

-- Angkatan 2023/2024 (12 Santri: 8 Aktif Tahfidz Lanjutan, 4 Keluar)
(19, '19', '0103001019', 'Muhammad Al-Fatih Sulaiman', 'L', 'Jakarta', '2010-03-14', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 30, 22.0, 1, 2, 3, 'Bpk. H. Sulaiman', '081210000003', 'Jl. Kebagusan Raya No. 10', '2023-07-15', 'Aktif'),
(20, '20', '0103001020', 'Zubair bin Awwam', 'L', 'Jakarta', '2009-12-20', 17, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 30, 25.5, 1, 2, NULL, 'Ibu Khairiyah', '081283000020', 'Tebet Barat', '2023-07-15', 'Aktif'),
(21, '21', '0103001021', 'Sa''ad bin Abi Waqqash', 'L', 'Solo', '2010-03-08', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 30, 18.5, 1, 2, NULL, 'Bpk. Joko Susilo', '081283000021', 'Manggarai Selatan', '2023-07-15', 'Aktif'),
(22, '22', '0103001022', 'Abdullah Azzam Pratama', 'L', 'Depok', '2010-07-22', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 20, 16.0, 1, 2, NULL, 'Ibu Hendra Pratama', '081283000022', 'Lenteng Agung', '2023-07-15', 'Aktif'),
(23, '23', '0103001023', 'Ibrahim Khalilullah', 'L', 'Tangerang', '2010-11-28', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 20, 15.0, 1, 2, NULL, 'Bpk. Hasan Basri', '081283000023', 'Cipete Utara', '2023-07-15', 'Aktif'),
(24, '24', '0103001024', 'Thalhah Ubaidillah', 'L', 'Bogor', '2010-10-09', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 20, 14.5, 1, 2, NULL, 'Bpk. Burhanuddin', '081283000024', 'Kalibata Utara', '2023-07-15', 'Aktif'),
(25, '25', '0103001025', 'Asma Dzatun Nithaqain', 'P', 'Jakarta', '2009-10-15', 17, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 30, 24.0, 1, 11, NULL, 'Ibu Aisyah', '081283000025', 'Radio Dalam', '2023-07-15', 'Aktif'),
(26, '26', '0103001026', 'Sumayyah Syahidah', 'P', 'Bandung', '2010-06-07', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Ahmad Fauzi (Tahfidz Lanjutan A)', 'Tahun ke-3 (Tingkat Akhir)', 'Al-Qur''an / Tajwid', 30, 20.0, 1, 11, NULL, 'Bpk. Dadang Hermawan', '081283000026', 'Gandaria I', '2023-07-15', 'Aktif'),
(27, '27', '0103001027', 'Fahmi Idris', 'L', 'Jakarta', '2010-04-12', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-3 (Tingkat Akhir)', 'Qurtuba Jilid 6', 15, 5.0, 2, 3, NULL, 'Bpk. Idris', '081283000027', 'Jakarta Timur', '2023-07-15', 'Keluar'),
(28, '28', '0103001028', 'Reza Pahlevi', 'L', 'Bekasi', '2010-08-19', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-3 (Tingkat Akhir)', 'Qurtuba Jilid 5', 15, 3.0, 2, 3, NULL, 'Bpk. Herman', '081283000028', 'Bekasi', '2023-07-15', 'Keluar'),
(29, '29', '0103001029', 'Rendi Kurniawan', 'L', 'Depok', '2010-09-02', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-3 (Tingkat Akhir)', 'Qurtuba Jilid 5', 15, 4.0, 2, 3, NULL, 'Bpk. Kurnia', '081283000029', 'Depok', '2023-07-15', 'Keluar'),
(30, '30', '0103001030', 'Fajar Shidiq', 'L', 'Bogor', '2010-05-15', 16, 'SMA', '2023/2024', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-3 (Tingkat Akhir)', 'Qurtuba Jilid 4', 15, 2.5, 2, 3, NULL, 'Bpk. Shidiq', '081283000030', 'Bogor', '2023-07-15', 'Keluar'),

-- Angkatan 2024/2025 (15 Santri: 12 Aktif Tahfidz Lanjutan, 3 Keluar)
(31, '31', '0114001031', 'Najwa Shihab Al-Qurtubi', 'P', 'Jakarta', '2011-09-12', 15, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 8.5, 2, 11, 3, 'Bpk. H. Sulaiman', '081210000003', 'Jl. Kebagusan Raya No. 10', '2024-07-15', 'Aktif'),
(32, '32', '0114001032', 'Fathan Mubina Ramadhan', 'L', 'Jakarta', '2011-08-17', 15, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 9.0, 2, 3, NULL, 'Ibu Fatimah', '081284000032', 'Cilandak Tengah', '2024-07-15', 'Aktif'),
(33, '33', '0114001033', 'Yusuf Manshur Al-Ayyubi', 'L', 'Bandung', '2012-02-14', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 7.5, 2, 3, NULL, 'Bpk. Ridwan Kamiluddin', '081284000033', 'Kemang Timur', '2024-07-15', 'Aktif'),
(34, '34', '0114001034', 'Usamah bin Zaid', 'L', 'Jakarta', '2011-07-16', 15, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 8.0, 2, 3, NULL, 'Bpk. Zainal Abidin', '081284000034', 'Bukit Duri', '2024-07-15', 'Aktif'),
(35, '35', '0114001035', 'Mush''ab bin Umair', 'L', 'Depok', '2011-05-04', 15, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Abdurrahman (Tahfidz Lanjutan B)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 10.0, 2, 3, NULL, 'Bpk. Umair Rasyid', '081284000035', 'Margonda', '2024-07-15', 'Aktif'),
(36, '36', '0114001036', 'Rasyid Ridha Hidayat', 'L', 'Jakarta', '2012-05-19', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Gharib', 10, 5.5, 3, 4, NULL, 'Bpk. Taufiq Hidayat', '081284000036', 'Pasar Minggu', '2024-07-15', 'Aktif'),
(37, '37', '0114001037', 'Zaidan Khairul Anam', 'L', 'Bogor', '2012-01-11', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Gharib', 10, 5.0, 3, 4, NULL, 'Ibu Siti Mulyadi', '081284000037', 'Jagakarsa II', '2024-07-15', 'Aktif'),
(38, '38', '0114001038', 'Hudzaifah Al-Yamani', 'L', 'Jakarta', '2012-06-30', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 10, 6.0, 3, 4, NULL, 'Bpk. Anwar Sadat', '081284000038', 'Bangka II', '2024-07-15', 'Aktif'),
(39, '39', '0114001039', 'Kayla Azzahra Putri', 'P', 'Jakarta', '2012-03-18', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Gharib', 10, 6.5, 3, 14, NULL, 'Bpk. Bambang Sutrisno', '081284000039', 'Ragunan Raya', '2024-07-15', 'Aktif'),
(40, '40', '0114001040', 'Hanifah Muslimah', 'P', 'Jakarta', '2012-04-21', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 10, 7.0, 3, 14, NULL, 'Ibu Muhadjir', '081284000040', 'Fatmawati', '2024-07-15', 'Aktif'),
(41, '41', '0114001041', 'Salma Salsabila', 'P', 'Depok', '2011-12-03', 15, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 9.5, 3, 14, NULL, 'Bpk. Nurhadi', '081284000041', 'Cinere Raya', '2024-07-15', 'Aktif'),
(42, '42', '0114001042', 'Nusaibah binti Ka''ab', 'P', 'Jakarta', '2011-09-25', 15, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Al-Qur''an / Tajwid', 15, 8.0, 3, 14, NULL, 'Bpk. Wahyu Hidayat', '081284000042', 'Kebayoran Baru', '2024-07-15', 'Aktif'),
(43, '43', '0114001043', 'Arif Rahman', 'L', 'Bekasi', '2012-01-30', 14, 'SMP', '2024/2025', 'Tahfidz Lanjutan', 'Kelompok Musyrif Ust. Salman Al-Farisi (Tahfidz Lanjutan C)', 'Tahun ke-2 (Tingkat Menengah)', 'Qurtuba Jilid 6', 10, 2.0, 3, 4, NULL, 'Bpk. Rahman', '081284000043', 'Bekasi', '2024-07-15', 'Keluar'),
(44, '44', '0114001044', 'Dani Kurnia', 'L', 'Jakarta', '2012-03-11', 14, 'SMP', '2024/2025', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-2 (Tingkat Menengah)', 'Qurtuba Jilid 5', 10, 1.5, 4, 5, NULL, 'Ibu Aminah', '081284000044', 'Jakarta', '2024-07-15', 'Keluar'),
(45, '45', '0114001045', 'Rizky Hidayat', 'L', 'Tangerang', '2012-06-05', 14, 'SMP', '2024/2025', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-2 (Tingkat Menengah)', 'Qurtuba Jilid 5', 10, 2.0, 4, 5, NULL, 'Bpk. Hidayat', '081284000045', 'Tangerang', '2024-07-15', 'Keluar'),

-- Angkatan 2025/2026 (13 Santri: 11 Aktif Tahsin Dasar, 2 Keluar)
(46, '46', '0125001046', 'Naufal Dzakwan Hakim', 'L', 'Bekasi', '2013-09-03', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 6', 5, 2.5, 4, 5, 3, 'Bpk. H. Sulaiman', '081210000003', 'Jl. Kebagusan Raya No. 10', '2025-07-15', 'Aktif'),
(47, '47', '0125001047', 'Muadz bin Jabal Saputra', 'L', 'Depok', '2013-01-05', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 5', 5, 2.0, 4, 5, NULL, 'Ibu Eko Saputra', '081285000047', 'Mampang IV', '2025-07-15', 'Aktif'),
(48, '48', '0125001048', 'Anas bin Malik Wijaya', 'L', 'Jakarta', '2013-04-12', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 6', 5, 2.0, 4, 5, NULL, 'Bpk. Hendro Wijaya', '081285000048', 'Pancoran Timur', '2025-07-15', 'Aktif'),
(49, '49', '0125001049', 'Ammar bin Yasir', 'L', 'Bekasi', '2013-11-02', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 4', 3, 1.5, 4, 5, NULL, 'Bpk. Yasser Arafat', '081285000049', 'Kampung Melayu', '2025-07-15', 'Aktif'),
(50, '50', '0125001050', 'Haritsah Syuhada', 'L', 'Jakarta', '2013-02-25', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Zaid bin Tsabit (Tahsin Dasar A)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 5', 5, 2.0, 4, 5, NULL, 'Bpk. Syamsul Maarif', '081285000050', 'Casablanca', '2025-07-15', 'Aktif'),
(51, '51', '0125001051', 'Syifa Khoirunnisa', 'P', 'Bogor', '2013-08-29', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 6', 5, 3.0, 5, 12, NULL, 'Bpk. Dedi Kurniawan', '081285000051', 'Ciganjur', '2025-07-15', 'Aktif'),
(52, '52', '0125001052', 'Aqila Mufidah Zahra', 'P', 'Tangerang', '2014-01-14', 12, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 5', 3, 2.0, 5, 12, NULL, 'Ibu Firmanzah', '081285000052', 'Pondok Labu', '2025-07-15', 'Aktif'),
(53, '53', '0125001053', 'Shafiyyah Huyay', 'P', 'Bekasi', '2013-12-19', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 4', 3, 1.5, 5, 12, NULL, 'Bpk. Agus Salim', '081285000053', 'Senopati', '2025-07-15', 'Aktif'),
(54, '54', '0125001054', 'Ummu Sulaim Rumaisya', 'P', 'Jakarta', '2013-05-08', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Iqra / Pra-Tahsin Dasar', 3, 1.0, 5, 6, NULL, 'Bpk. Ahmad', '081285000054', 'Blok M', '2025-07-15', 'Aktif'),
(55, '55', '0125001055', 'Qonita Hafizhah Zahirah', 'P', 'Bogor', '2013-02-02', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Al-Qur''an / Gharib', 5, 4.0, 5, 6, NULL, 'Bpk. Ibrahim Khalil', '081285000055', 'Dharmawangsa', '2025-07-15', 'Aktif'),
(56, '56', '0125001056', 'Bilal Al-Ghifari', 'L', 'Jakarta', '2014-03-10', 12, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Iqra / Pra-Tahsin Dasar', 3, 0.5, 5, 6, NULL, 'Ibu Siti Aminah', '081285000056', 'Pasar Minggu', '2025-07-15', 'Aktif'),
(57, '57', '0125001057', 'Faris Al-Ghazi', 'L', 'Depok', '2013-07-19', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Iqra / Pra-Tahsin Dasar', 3, 0.5, 5, 6, NULL, 'Bpk. Ghazi', '081285000057', 'Depok', '2025-07-15', 'Keluar'),
(58, '58', '0125001058', 'Hafizh Al-Mubarok', 'L', 'Bogor', '2013-11-23', 13, 'SMP', '2025/2026', 'Tahsin Dasar', 'Kelompok Musyrif Ust. Hamzah Fansuri (Tahsin Dasar B)', 'Tahun ke-1 (Tingkat Dasar)', 'Qurtuba Jilid 3', 3, 1.0, 5, 6, NULL, 'Bpk. Mubarok', '081285000058', 'Bogor', '2025-07-15', 'Keluar');

-- ----------------------------------------------------------------------------
-- 6. TABEL TARGET TAHFIDZ
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `target_tahfidz`;
CREATE TABLE `target_tahfidz` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `semester` ENUM('Ganjil','Genap') NOT NULL DEFAULT 'Genap',
  `tahun_ajaran` VARCHAR(20) NOT NULL DEFAULT '2025/2026',
  `juz_target` VARCHAR(50) NOT NULL,
  `target_halaman` INT NOT NULL DEFAULT 20,
  `tercapai_halaman` INT NOT NULL DEFAULT 0,
  `persentase_capaian` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `batas_waktu` DATE DEFAULT NULL,
  `status_target` ENUM('Belum Tercapai','Proses','Tercapai') NOT NULL DEFAULT 'Proses',
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_target_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `target_tahfidz` (`santri_id`, `semester`, `tahun_ajaran`, `juz_target`, `target_halaman`, `tercapai_halaman`, `persentase_capaian`, `batas_waktu`, `status_target`, `keterangan`) VALUES
(19, 'Genap', '2025/2026', 'Juz 8 & Juz 9 (Target 25 Juz)', 40, 36, 90.00, '2026-12-20', 'Proses', 'Persiapan Ujian Tasmi 5 Juz bersama Ust. Ridwan'),
(20, 'Genap', '2025/2026', 'Juz 5 - Juz 6 (Khatam 30 Juz)', 40, 40, 100.00, '2026-12-20', 'Tercapai', 'Alhamdulillah tuntas sebelum batas waktu'),
(31, 'Genap', '2025/2026', 'Juz 22', 20, 16, 80.00, '2026-12-20', 'Proses', 'Konsisten setoran ziyadah Subuh'),
(46, 'Genap', '2025/2026', 'Juz 29', 20, 15, 75.00, '2026-12-20', 'Proses', 'Fokus memantapkan kelancaran Juz 29');

-- ----------------------------------------------------------------------------
-- 7. TABEL SETORAN (tb_setoran)
-- Kategori: Tahsin Dasar, Ziyadah, Murajaah, Hafalan Hadis & Kitab,
--           Evaluasi Tertutup 10 Halaman, Ujian Tasmi 5 Juz
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `setoran_tahfidz`;
CREATE TABLE `setoran_tahfidz` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `pengajar_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jenis_setoran` ENUM('Tahsin Dasar','Ziyadah','Murajaah','Hafalan Hadis & Kitab','Evaluasi Tertutup 10 Halaman','Ujian Tasmi 5 Juz') NOT NULL DEFAULT 'Ziyadah',
  `juz` INT NOT NULL DEFAULT 30,
  `surah_awal` VARCHAR(60) NOT NULL,
  `ayat_awal` INT NOT NULL DEFAULT 1,
  `surah_akhir` VARCHAR(60) NOT NULL,
  `ayat_akhir` INT NOT NULL DEFAULT 10,
  `jumlah_halaman` DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  `jumlah_baris` INT NOT NULL DEFAULT 15,
  `nilai_kelancaran` INT NOT NULL DEFAULT 90,
  `nilai_tajwid` INT NOT NULL DEFAULT 90,
  `nilai_makhraj` INT NOT NULL DEFAULT 90,
  `predikat` ENUM('Mumtaz','Jayyid Jiddan','Jayyid','Maqbul','Rosib') NOT NULL DEFAULT 'Mumtaz',
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_setoran_tahfidz_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_setoran_tahfidz_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `setoran_tahfidz` (`santri_id`, `pengajar_id`, `tanggal`, `jenis_setoran`, `juz`, `surah_awal`, `ayat_awal`, `surah_akhir`, `ayat_akhir`, `jumlah_halaman`, `jumlah_baris`, `nilai_kelancaran`, `nilai_tajwid`, `nilai_makhraj`, `predikat`, `catatan`) VALUES
(19, 2, CURDATE(), 'Ziyadah', 9, 'Al-A''raf', 88, 'Al-A''raf', 170, 10.0, 150, 95, 94, 95, 'Mumtaz', 'Mencapai akumulasi 10 halaman, lanjut Evaluasi Tertutup'),
(19, 2, CURDATE(), 'Murajaah', 10, 'Al-Anfal', 1, 'Al-Anfal', 75, 8.0, 120, 96, 95, 96, 'Mumtaz', 'Murajaah sesi Subuh lancar tanpa teguran'),
(19, 3, CURDATE(), 'Hafalan Hadis & Kitab', 1, 'Arbain Nawawi Hadis 1', 1, 'Arbain Nawawi Hadis 5', 5, 2.0, 30, 94, 92, 93, 'Mumtaz', 'Setoran hafalan hadis sesi Ashar & matan kitab'),
(19, 1, CURDATE(), 'Evaluasi Tertutup 10 Halaman', 9, 'Al-A''raf', 88, 'Al-A''raf', 170, 10.0, 150, 95, 95, 94, 'Mumtaz', 'Lulus Evaluasi Tertutup 10 Halaman bersama Musyrif'),
(20, 1, CURDATE(), 'Ujian Tasmi 5 Juz', 25, 'Juz 21', 1, 'Juz 25', 100, 100.0, 1500, 96, 96, 97, 'Mumtaz', 'Ujian Tasmi 5 Juz sekali duduk bersama Mudir Ust. Ridwan'),
(31, 11, CURDATE(), 'Ziyadah', 22, 'Al-Ahzab', 1, 'Al-Ahzab', 30, 4.0, 60, 92, 91, 93, 'Mumtaz', 'Bacaan tartil dan makhraj sangat baik'),
(46, 5, CURDATE(), 'Tahsin Dasar', 30, 'Qurtuba Jilid 6 Hal 18', 1, 'Qurtuba Jilid 6 Hal 19', 15, 2.0, 30, 89, 90, 88, 'Jayyid Jiddan', 'Perbaikan makhraj dan mad wajib muttashil');

-- ----------------------------------------------------------------------------
-- 8. TABEL EVALUASI TERTUTUP 10 HALAMAN & TASMI' 5 JUZ
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ujian_tasmi`;
CREATE TABLE `ujian_tasmi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `penguji_id` INT NOT NULL,
  `tanggal_ujian` DATE NOT NULL,
  `jenis_ujian` VARCHAR(50) NOT NULL DEFAULT 'Evaluasi Tertutup 10 Halaman',
  `juz_diujikan` VARCHAR(50) NOT NULL,
  `halaman_awal` INT NOT NULL DEFAULT 582,
  `halaman_akhir` INT NOT NULL DEFAULT 591,
  `kesalahan_fathah_lupa` INT NOT NULL DEFAULT 0,
  `kesalahan_tajwid` INT NOT NULL DEFAULT 0,
  `nilai_akhir` DECIMAL(5,2) NOT NULL DEFAULT 95.00,
  `predikat` ENUM('Mumtaz','Jayyid Jiddan','Jayyid','Maqbul','Mengulang') NOT NULL DEFAULT 'Mumtaz',
  `status_lulus` ENUM('Lulus','Mengulang') NOT NULL DEFAULT 'Lulus',
  `catatan_penguji` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tasmi_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tasmi_penguji` FOREIGN KEY (`penguji_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ujian_tasmi` (`santri_id`, `penguji_id`, `tanggal_ujian`, `jenis_ujian`, `juz_diujikan`, `halaman_awal`, `halaman_akhir`, `kesalahan_fathah_lupa`, `kesalahan_tajwid`, `nilai_akhir`, `predikat`, `status_lulus`, `catatan_penguji`) VALUES
(19, 1, CURDATE(), 'Evaluasi Tertutup 10 Halaman', 'Juz 9 (Hal 162-171)', 162, 171, 1, 1, 95.00, 'Mumtaz', 'Lulus', 'Lulus evaluasi tertutup 10 halaman, diizinkan lanjut halaman baru'),
(20, 1, CURDATE(), 'Ujian Tasmi 5 Juz', 'Juz 21 - Juz 25', 402, 501, 2, 1, 96.50, 'Mumtaz', 'Lulus', 'Ujian Tasmi 5 Juz bersama Mudir Ust. Ridwan sangat mutqin');

-- ----------------------------------------------------------------------------
-- 9. TABEL SETORAN TAHSIN (Tingkat Dasar - Tahun ke-1)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `setoran_tahsin`;
CREATE TABLE `setoran_tahsin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `pengajar_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `metode_jilid` VARCHAR(60) NOT NULL DEFAULT 'Qurtuba Jilid 1',
  `halaman` VARCHAR(20) NOT NULL,
  `baris` VARCHAR(20) DEFAULT '1-15',
  `nilai_makhraj` INT NOT NULL DEFAULT 85,
  `nilai_mad` INT NOT NULL DEFAULT 85,
  `nilai_ghunnah` INT NOT NULL DEFAULT 85,
  `predikat` ENUM('Lancar','Kurang Lancar','Mengulang') NOT NULL DEFAULT 'Lancar',
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tahsin_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tahsin_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `setoran_tahsin` (`santri_id`, `pengajar_id`, `tanggal`, `metode_jilid`, `halaman`, `baris`, `nilai_makhraj`, `nilai_mad`, `nilai_ghunnah`, `predikat`, `catatan`) VALUES
(46, 5, CURDATE(), 'Qurtuba Jilid 6', '18', '1-15', 89, 90, 88, 'Lancar', 'Siap diajukan ujian kenaikan ke Al-Qur''an'),
(54, 6, CURDATE(), 'Iqra / Pra-Tahsin Dasar', '22', '1-12', 84, 83, 85, 'Lancar', 'Perbaikan makhraj huruf ain dan ha');

-- ----------------------------------------------------------------------------
-- 10. TABEL UJIAN KENAIKAN JILID TAHSIN
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ujian_tahsin`;
CREATE TABLE `ujian_tahsin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `penguji_id` INT NOT NULL,
  `tanggal_ujian` DATE NOT NULL,
  `jilid_asal` VARCHAR(50) NOT NULL,
  `jilid_tujuan` VARCHAR(50) NOT NULL,
  `nilai_makharijul_huruf` INT NOT NULL DEFAULT 85,
  `nilai_shifatul_huruf` INT NOT NULL DEFAULT 85,
  `nilai_ahkamul_huruf` INT NOT NULL DEFAULT 85,
  `nilai_mad_waqaf` INT NOT NULL DEFAULT 85,
  `nilai_rata_rata` DECIMAL(5,2) NOT NULL DEFAULT 85.00,
  `keputusan` ENUM('Naik Jilid','Belum Naik / Mengulang') NOT NULL DEFAULT 'Naik Jilid',
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ujian_tahsin_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ujian_tahsin_penguji` FOREIGN KEY (`penguji_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ujian_tahsin` (`santri_id`, `penguji_id`, `tanggal_ujian`, `jilid_asal`, `jilid_tujuan`, `nilai_makharijul_huruf`, `nilai_shifatul_huruf`, `nilai_ahkamul_huruf`, `nilai_mad_waqaf`, `nilai_rata_rata`, `keputusan`, `catatan`) VALUES
(46, 5, CURDATE(), 'Qurtuba Jilid 6', 'Al-Qur''an / Tajwid', 90, 88, 89, 91, 89.50, 'Naik Jilid', 'Selamat naik ke tingkat Al-Qur''an dan Gharib');

-- ----------------------------------------------------------------------------
-- 11. TABEL MASTER MUTABAAH YAUMIYAH (22 Butir Amalan: 03.00 - 21.00 WIB)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `master_mutabaah`;
CREATE TABLE `master_mutabaah` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_amalan` VARCHAR(20) NOT NULL UNIQUE,
  `nama_amalan` VARCHAR(120) NOT NULL,
  `kategori` ENUM('Ibadah Wajib','Ibadah Sunnah','Tilawah & Dzikir','Adab & Akhlak') NOT NULL DEFAULT 'Ibadah Sunnah',
  `target_harian` VARCHAR(60) DEFAULT 'Dilaksanakan setiap hari',
  `poin_amalan` INT NOT NULL DEFAULT 10,
  `status_aktif` ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `master_mutabaah` (`id`, `kode_amalan`, `nama_amalan`, `kategori`, `target_harian`, `poin_amalan`, `status_aktif`) VALUES
(1, 'MTB-01', 'Bangun Dini Hari Pukul 03.00 WIB', 'Adab & Akhlak', '03.00 WIB', 10, 'Aktif'),
(2, 'MTB-02', 'Qiyamul Lail / Tahajud & Witir Berjamaah', 'Ibadah Sunnah', 'Min. 2+1 Rakaat', 15, 'Aktif'),
(3, 'MTB-03', 'Dzikir & Istighfar Waktu Sahur', 'Tilawah & Dzikir', 'Sebelum Subuh', 10, 'Aktif'),
(4, 'MTB-04', 'Shalat Sunnah Qabliyah Subuh', 'Ibadah Sunnah', '2 Rakaat', 10, 'Aktif'),
(5, 'MTB-05', 'Shalat Subuh Berjamaah Tepat Waktu', 'Ibadah Wajib', 'Berjamaah di Masjid', 20, 'Aktif'),
(6, 'MTB-06', 'Dzikir Pagi (Al-Ma''tsurat)', 'Tilawah & Dzikir', 'Ba''da Subuh', 10, 'Aktif'),
(7, 'MTB-07', 'Setoran Halaqah Utama Subuh', 'Tilawah & Dzikir', '04.30 - 06.30 WIB', 15, 'Aktif'),
(8, 'MTB-08', 'Piket Kebersihan Asrama & Masjid', 'Adab & Akhlak', 'Sesuai Jadwal Piket', 10, 'Aktif'),
(9, 'MTB-09', 'Merapikan Tempat Tidur & Lemari Pribadi', 'Adab & Akhlak', 'Setiap Pagi', 10, 'Aktif'),
(10, 'MTB-10', 'Shalat Sunnah Dhuha', 'Ibadah Sunnah', 'Min. 4 Rakaat', 10, 'Aktif'),
(11, 'MTB-11', 'Mengikuti Halaqah Pagi / Dhuha (Tahsin / Muraja''ah)', 'Tilawah & Dzikir', '08.00 - 11.00 WIB', 15, 'Aktif'),
(12, 'MTB-12', 'Shalat Dzuhur Berjamaah di Masjid', 'Ibadah Wajib', 'Berjamaah di Masjid', 20, 'Aktif'),
(13, 'MTB-13', 'Shalat Sunnah Rawatib Dzuhur (Qabliyah & Ba''diyah)', 'Ibadah Sunnah', '4+2 Rakaat', 10, 'Aktif'),
(14, 'MTB-14', 'Mengikuti Pelajaran Kitab / Kondisi Siang', 'Adab & Akhlak', '13.00 - 14.30 WIB', 10, 'Aktif'),
(15, 'MTB-15', 'Qailulah / Istirahat Siang Tertib', 'Adab & Akhlak', 'Tertib Asrama', 5, 'Aktif'),
(16, 'MTB-16', 'Shalat Ashar Berjamaah di Masjid', 'Ibadah Wajib', 'Berjamaah di Masjid', 20, 'Aktif'),
(17, 'MTB-17', 'Halaqah Sore / Ashar (Hafalan Hadis)', 'Tilawah & Dzikir', '16.00 - 17.30 WIB', 15, 'Aktif'),
(18, 'MTB-18', 'Dzikir Petang (Al-Ma''tsurat)', 'Tilawah & Dzikir', 'Ba''da Ashar', 10, 'Aktif'),
(19, 'MTB-19', 'Shalat Maghrib Berjamaah & Tilawah / Kajian Jumat', 'Ibadah Wajib', '18.00 - 19.30 WIB', 20, 'Aktif'),
(20, 'MTB-20', 'Shalat Isya Berjamaah & Rawatib Ba''diyah', 'Ibadah Wajib', 'Berjamaah + 2 Rakaat', 20, 'Aktif'),
(21, 'MTB-21', 'Tilawah Al-Qur''an Mandiri Harian', 'Tilawah & Dzikir', 'Min. 1 Juz / Hari', 15, 'Aktif'),
(22, 'MTB-22', 'Evaluasi Malam & Tidur Tepat Waktu (21.00 WIB)', 'Adab & Akhlak', 'Pukul 21.00 WIB', 10, 'Aktif');

-- ----------------------------------------------------------------------------
-- 12. TABEL ISI MUTABAAH SANTRI
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `mutabaah_santri`;
CREATE TABLE `mutabaah_santri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `master_mutabaah_id` INT NOT NULL,
  `status_pelaksanaan` ENUM('Ya','Tidak','Uzur') NOT NULL DEFAULT 'Ya',
  `jumlah_capaian` VARCHAR(60) DEFAULT 'Tuntas',
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `diinput_oleh` VARCHAR(60) DEFAULT 'Musyrif Asrama / A Yudha',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mutabaah_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mutabaah_master` FOREIGN KEY (`master_mutabaah_id`) REFERENCES `master_mutabaah`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mutabaah_santri` (`santri_id`, `tanggal`, `master_mutabaah_id`, `status_pelaksanaan`, `jumlah_capaian`, `keterangan`, `diinput_oleh`) VALUES
(19, CURDATE(), 1, 'Ya', '03.00 WIB', 'Bangun tepat waktu bersama jamaah asrama', 'Musyrif Asrama / A Yudha'),
(19, CURDATE(), 2, 'Ya', '4 Rakaat + 3 Witir', 'Qiyamul lail berjamaah', 'Musyrif Asrama / A Yudha'),
(19, CURDATE(), 5, 'Ya', 'Berjamaah di Masjid', 'Shaf pertama', 'Musyrif Asrama / A Yudha'),
(19, CURDATE(), 10, 'Ya', '4 Rakaat', 'Tuntas sesi Dhuha', 'Musyrif Asrama / A Yudha'),
(19, CURDATE(), 21, 'Ya', '1.5 Juz', 'Selesai ba''da Maghrib', 'Musyrif Asrama / A Yudha'),
(31, CURDATE(), 5, 'Ya', 'Berjamaah', 'Tepat waktu', 'Musyrif Asrama / A Yudha'),
(31, CURDATE(), 21, 'Ya', '1 Juz', 'Tilawah mandiri tuntas', 'Musyrif Asrama / A Yudha');

-- ----------------------------------------------------------------------------
-- 13. TABEL MASTER PELANGGARAN & POIN (12 Butir: Ringan 5-10, Sedang 15-30, Berat 50-100)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `jenis_pelanggaran`;
CREATE TABLE `jenis_pelanggaran` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_pelanggaran` VARCHAR(20) NOT NULL UNIQUE,
  `nama_pelanggaran` VARCHAR(150) NOT NULL,
  `kategori` ENUM('Ringan','Sedang','Berat') NOT NULL DEFAULT 'Ringan',
  `poin` INT NOT NULL DEFAULT 5,
  `sanksi_pembinaan` VARCHAR(200) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `jenis_pelanggaran` (`id`, `kode_pelanggaran`, `nama_pelanggaran`, `kategori`, `poin`, `sanksi_pembinaan`) VALUES
(1, 'PLG-01', 'Terlambat mengikuti halaqah', 'Ringan', 5, 'Teguran lisan dan tugas kebersihan oleh musyrif'),
(2, 'PLG-02', 'Tidak melaksanakan piket kebersihan', 'Ringan', 10, 'Teguran lisan dan tugas kebersihan oleh musyrif'),
(3, 'PLG-03', 'Tidur saat pengajian / halaqah berlangsung', 'Ringan', 5, 'Teguran lisan dan tugas kebersihan oleh musyrif'),
(4, 'PLG-04', 'Tidak merapikan tempat tidur asrama', 'Ringan', 5, 'Teguran lisan dan tugas kebersihan oleh musyrif'),
(5, 'PLG-05', 'Berbicara kasar / kurang sopan', 'Sedang', 15, 'Pemanggilan oleh musyrif, pembinaan kedisiplinan, dan Kartu Kuning (SP-1)'),
(6, 'PLG-06', 'Mengabaikan shalat berjamaah tanpa uzur', 'Sedang', 20, 'Pemanggilan oleh musyrif, pembinaan kedisiplinan, dan Kartu Kuning (SP-1)'),
(7, 'PLG-07', 'Keluar area asrama tanpa izin musyrif pendamping', 'Sedang', 25, 'Pemanggilan oleh musyrif, pembinaan kedisiplinan, dan Kartu Kuning (SP-1)'),
(8, 'PLG-08', 'Alpa halaqah tanpa keterangan resmi', 'Sedang', 30, 'Pemanggilan oleh musyrif, pembinaan kedisiplinan, dan Kartu Kuning (SP-1)'),
(9, 'PLG-09', 'Membawa ponsel (handphone) secara sembunyi-sembunyi', 'Berat', 50, 'Kartu Merah, pemanggilan Orang Tua Santri, serta evaluasi oleh dewan asatidz dan pimpinan pesantren'),
(10, 'PLG-10', 'Berkelahi atau melakukan kekerasan fisik', 'Berat', 75, 'Kartu Merah, pemanggilan Orang Tua Santri, serta evaluasi oleh dewan asatidz dan pimpinan pesantren'),
(11, 'PLG-11', 'Mencuri barang milik santri / lembaga', 'Berat', 100, 'Kartu Merah, pemanggilan Orang Tua Santri, serta evaluasi oleh dewan asatidz dan pimpinan pesantren'),
(12, 'PLG-12', 'Melakukan tindakan amoral / pelanggaran adab berat', 'Berat', 100, 'Kartu Merah, pemanggilan Orang Tua Santri, serta evaluasi oleh dewan asatidz dan pimpinan pesantren');

-- ----------------------------------------------------------------------------
-- 14. TABEL PELANGGARAN SANTRI
-- Ambang Akumulasi Status:
--   0 - 14 Poin  : Aman
--   15 - 29 Poin : Teguran Lisan
--   30 - 49 Poin : Kartu Kuning / SP-1
--   >= 50 Poin   : Kartu Merah / Evaluasi Beasiswa
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `pelanggaran_santri`;
CREATE TABLE `pelanggaran_santri` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `jenis_pelanggaran_id` INT NOT NULL,
  `pelapor_id` INT DEFAULT NULL,
  `tanggal` DATE NOT NULL,
  `kronologi` TEXT DEFAULT NULL,
  `poin_saat_itu` INT NOT NULL DEFAULT 5,
  `tindakan_pembinaan` VARCHAR(255) DEFAULT NULL,
  `status_penanganan` ENUM('Proses','Selesai','Panggilan Orang Tua Santri') NOT NULL DEFAULT 'Selesai',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pelanggaran_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pelanggaran_jenis` FOREIGN KEY (`jenis_pelanggaran_id`) REFERENCES `jenis_pelanggaran`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pelanggaran_pelapor` FOREIGN KEY (`pelapor_id`) REFERENCES `pengajar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pelanggaran_santri` (`santri_id`, `jenis_pelanggaran_id`, `pelapor_id`, `tanggal`, `kronologi`, `poin_saat_itu`, `tindakan_pembinaan`, `status_penanganan`) VALUES
(36, 1, 8, CURDATE(), 'Terlambat 10 menit pada Sesi 2 - Subuh / Halaqah Utama', 5, 'Teguran lisan dan tugas kebersihan teras masjid oleh musyrif', 'Selesai'),
(47, 7, 8, CURDATE(), 'Keluar area asrama ke warung sekitar tanpa izin musyrif pendamping', 25, 'Teguran lisan & pembinaan kedisiplinan musyrif asrama', 'Proses'),
(37, 8, 8, CURDATE(), 'Alpa mengikuti sesi halaqah tanpa keterangan resmi', 30, 'Penerbitan Kartu Kuning / SP-1 dan pembinaan khusus', 'Proses'),
(48, 9, 9, CURDATE(), 'Kedapatan membawa ponsel secara sembunyi-sembunyi di asrama', 50, 'Kartu Merah, pemanggilan Orang Tua Santri & evaluasi beasiswa', 'Panggilan Orang Tua Santri');

-- ----------------------------------------------------------------------------
-- 15. TABEL PRESENSI 7 SESI KEGIATAN HARIAN (tb_absensi)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `presensi_halaqah`;
CREATE TABLE `presensi_halaqah` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `pengajar_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `sesi_halaqah` VARCHAR(80) NOT NULL DEFAULT 'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)',
  `status_kehadiran` ENUM('Hadir','Sakit','Izin','Alpa') NOT NULL DEFAULT 'Hadir',
  `waktu_hadir` TIME DEFAULT '04:30:00',
  `keterangan` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_presensi_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_presensi_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `presensi_halaqah` (`santri_id`, `pengajar_id`, `tanggal`, `sesi_halaqah`, `status_kehadiran`, `waktu_hadir`, `keterangan`) VALUES
(19, 8, CURDATE(), 'Sesi 1 - Dini Hari / Tahajud (03.00 - 04.30)', 'Hadir', '03:05:00', 'Qiyamul lail berjamaah'),
(19, 2, CURDATE(), 'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)', 'Hadir', '04:30:00', 'Setoran ziyadah & murajaah'),
(19, 2, CURDATE(), 'Sesi 3 - Pagi / Dhuha (08.00 - 11.00)', 'Hadir', '08:00:00', 'Shalat Dhuha & murajaah lanjutan'),
(19, 3, CURDATE(), 'Sesi 4 - Siang / Kondisional (13.00 - 14.30)', 'Hadir', '13:00:00', 'Shalat Dzuhur & pelajaran kitab'),
(19, 2, CURDATE(), 'Sesi 5 - Sore / Ashar: Halaqah Hadis (16.00 - 17.30)', 'Hadir', '16:00:00', 'Halaqah hafalan hadis'),
(19, 8, CURDATE(), 'Sesi 6 - Maghrib: Tilawah / Kajian Jumat (18.00 - 19.30)', 'Hadir', '18:05:00', 'Tilawah mandiri di masjid'),
(19, 15, CURDATE(), 'Sesi 7 - Ba''da Isya: Evaluasi & Mutaba''ah (19.30 - 21.00)', 'Hadir', '19:35:00', 'Evaluasi mutabaah harian'),
(31, 11, CURDATE(), 'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)', 'Hadir', '04:32:00', 'Hadir tepat waktu'),
(46, 5, CURDATE(), 'Sesi 2 - Subuh / Halaqah Utama (04.30 - 06.30)', 'Hadir', '04:35:00', 'Hadir halaqah tahsin dasar');

-- ----------------------------------------------------------------------------
-- 16. TABEL RAPOR SEMESTER (Evaluasi 6 Bulanan)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rapor_semester`;
CREATE TABLE `rapor_semester` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `santri_id` INT NOT NULL,
  `pengajar_id` INT NOT NULL,
  `semester` ENUM('Ganjil','Genap') NOT NULL DEFAULT 'Genap',
  `tahun_ajaran` VARCHAR(20) NOT NULL DEFAULT '2025/2026',
  `total_hafalan_juz` DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  `capaian_ziyadah_halaman` INT NOT NULL DEFAULT 20,
  `nilai_tahfidz` DECIMAL(5,2) NOT NULL DEFAULT 90.00,
  `nilai_tahsin` DECIMAL(5,2) NOT NULL DEFAULT 90.00,
  `nilai_mutabaah` DECIMAL(5,2) NOT NULL DEFAULT 90.00,
  `nilai_akhlak` CHAR(2) NOT NULL DEFAULT 'A',
  `jumlah_hadir` INT NOT NULL DEFAULT 110,
  `jumlah_sakit` INT NOT NULL DEFAULT 1,
  `jumlah_izin` INT NOT NULL DEFAULT 1,
  `jumlah_alpa` INT NOT NULL DEFAULT 0,
  `total_poin_pelanggaran` INT NOT NULL DEFAULT 0,
  `catatan_muhaffizh` TEXT DEFAULT NULL,
  `catatan_mudir` TEXT DEFAULT NULL,
  `status_publikasi` ENUM('Draft','Terbit') NOT NULL DEFAULT 'Terbit',
  `tanggal_terbit` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_rapor_santri` FOREIGN KEY (`santri_id`) REFERENCES `santri`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rapor_pengajar` FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rapor_semester` (`santri_id`, `pengajar_id`, `semester`, `tahun_ajaran`, `total_hafalan_juz`, `capaian_ziyadah_halaman`, `nilai_tahfidz`, `nilai_tahsin`, `nilai_mutabaah`, `nilai_akhlak`, `jumlah_hadir`, `jumlah_sakit`, `jumlah_izin`, `jumlah_alpa`, `total_poin_pelanggaran`, `catatan_muhaffizh`, `catatan_mudir`, `status_publikasi`, `tanggal_terbit`) VALUES
(19, 2, 'Genap', '2025/2026', 22.0, 42, 95.00, 94.00, 96.50, 'A', 120, 0, 1, 0, 0, 'Alhamdulillah ananda Al-Fatih istiqamah dalam setoran ziyadah Subuh, murajaah, serta hafalan hadis.', 'Pertahankan mutqin hafalan 22 juz dan adab mulia sebagai santri teladan Rumah Qur''an Qurtuba.', 'Terbit', CURDATE()),
(31, 11, 'Genap', '2025/2026', 8.5, 28, 92.50, 93.00, 94.00, 'A', 118, 1, 0, 0, 0, 'Perkembangan hafalan dan adab sangat baik selama tahun ke-2.', 'Teruskan semangat menuju target 15 juz.', 'Terbit', CURDATE());

-- ----------------------------------------------------------------------------
-- 17. VIEW KOMPATIBILITAS CLASS DIAGRAM (tb_*)
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW `tb_user` AS
SELECT `id` AS `id_user`, `nama_lengkap`, `username`, `password`, `role`, `jabatan_label`, `no_hp`, `foto`, `status_aktif`, `created_at`
FROM `users`;

CREATE OR REPLACE VIEW `tb_santri` AS
SELECT `id_santri`, `nis`, `nisn`, `nama_santri`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `usia`, `jenjang`, `tahun_angkatan`, `kategori_halaqah`, `kelompok_musyrif`, `kelas_program`, `jilid_tahsin`, `target_juz`, `total_hafalan_juz`, `halaqah_id`, `pengajar_id`, `id_user_orang_tua`, `nama_orang_tua`, `no_hp_orang_tua`, `alamat`, `tanggal_masuk`, `status_santri`, `created_at`
FROM `santri`;

CREATE OR REPLACE VIEW `tb_absensi` AS
SELECT `id` AS `id_absensi`, `santri_id` AS `id_santri`, `pengajar_id` AS `id_pengajar`, `tanggal`, `sesi_halaqah`, `status_kehadiran` AS `status_hadir`, `waktu_hadir`, `keterangan`, `created_at`
FROM `presensi_halaqah`;

CREATE OR REPLACE VIEW `tb_setoran` AS
SELECT `id` AS `id_setoran`, `santri_id` AS `id_santri`, `pengajar_id` AS `id_pengajar`, `tanggal`, `jenis_setoran` AS `kategori`,
       CONCAT('Juz ', `juz`, ': ', `surah_awal`, ' (', `ayat_awal`, ') - ', `surah_akhir`, ' (', `ayat_akhir`, ')') AS `detail_capaian`,
       `predikat` AS `nilai`, `catatan` AS `catatan_ustadz`, `jumlah_halaman`, `jumlah_baris`, `nilai_kelancaran`, `nilai_tajwid`, `nilai_makhraj`, `created_at`
FROM `setoran_tahfidz`;

CREATE OR REPLACE VIEW `tb_master_mutabaah` AS
SELECT `id` AS `id_item`, `kode_amalan`, `nama_amalan` AS `nama_ibadah`, `kategori`, `target_harian`, `poin_amalan`, `status_aktif`, `created_at`
FROM `master_mutabaah`;

CREATE OR REPLACE VIEW `tb_mutabaah` AS
SELECT `id` AS `id_mutabaah`, `santri_id` AS `id_santri`, `master_mutabaah_id` AS `id_item`, `tanggal`, `status_pelaksanaan`, `jumlah_capaian`, `keterangan`, `diinput_oleh`, `created_at`
FROM `mutabaah_santri`;

CREATE OR REPLACE VIEW `tb_master_pelanggaran` AS
SELECT `id` AS `id_jenis_pelanggaran`, `kode_pelanggaran`, `nama_pelanggaran`, `kategori` AS `kategori_sanksi`, `poin` AS `bobot_poin`, `sanksi_pembinaan`, `created_at`
FROM `jenis_pelanggaran`;

CREATE OR REPLACE VIEW `tb_pelanggaran` AS
SELECT `id` AS `id_pelanggaran`, `santri_id` AS `id_santri`, `jenis_pelanggaran_id` AS `id_jenis_pelanggaran`, `pelapor_id` AS `id_pengajar`, `tanggal`, `kronologi`, `poin_saat_itu`, `tindakan_pembinaan`, `status_penanganan`, `created_at`
FROM `pelanggaran_santri`;

SET FOREIGN_KEY_CHECKS = 1;
