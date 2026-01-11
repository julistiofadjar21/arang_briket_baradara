-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Jan 2026 pada 06.05
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_bara_dara`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`, `role`, `created_at`, `foto`) VALUES
(3, 'admin', 'admin@gmail.com', '$2y$10$ECdx/N12Sb75knnb4TMLz.sZP9fMO98mctZmgDB0gknmVBRK3.djS', 'admin', '2024-12-24 11:36:56', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `bahan_baku`
--

CREATE TABLE `bahan_baku` (
  `id` int(11) NOT NULL,
  `nama_bahan` varchar(100) DEFAULT NULL,
  `harga_jual` int(11) DEFAULT NULL,
  `biaya_produksi` int(11) DEFAULT NULL,
  `jumlah_terjual` int(11) DEFAULT NULL,
  `stok` int(11) DEFAULT NULL,
  `gambar` varchar(200) DEFAULT NULL,
  `link` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bahan_baku`
--

INSERT INTO `bahan_baku` (`id`, `nama_bahan`, `harga_jual`, `biaya_produksi`, `jumlah_terjual`, `stok`, `gambar`, `link`) VALUES
(1, 'Kulit Kemiri', 40000, 25000, 160, 0, 'kulit kemiri.jpg', 'bijikemiri.html'),
(2, 'Tempurung Kelapa', 45000, 20000, 219, 8, 'arang-batok.jpg', 'tempurungkelapa.html'),
(3, 'Serbuk Kayu', 30000, 15000, 187, 4, 'serbuk kayu.jpg', 'serbukkayu.html'),
(4, 'Sekam Padi', 30000, 18000, 104, 1, 'sekam padi.jpg', 'sekampadi.html');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `kode_pesanan` varchar(12) DEFAULT NULL,
  `nama_pembeli` varchar(100) NOT NULL,
  `bahan_baku` varchar(100) NOT NULL,
  `jumlah_kg` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status_pesanan` varchar(20) DEFAULT 'Belum Lunas',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `stok_dipotong` tinyint(1) NOT NULL DEFAULT 0,
  `subtotal` int(11) NOT NULL DEFAULT 0,
  `diskon_persen` int(11) NOT NULL DEFAULT 0,
  `diskon_rp` int(11) NOT NULL DEFAULT 0,
  `bonus_kg` int(11) NOT NULL DEFAULT 0,
  `gratis_ongkir` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `kode_pesanan`, `nama_pembeli`, `bahan_baku`, `jumlah_kg`, `total_harga`, `tanggal`, `status_pesanan`, `bukti_transfer`, `stok_dipotong`, `subtotal`, `diskon_persen`, `diskon_rp`, `bonus_kg`, `gratis_ongkir`) VALUES
(3, 'BRD03', 'Julistio Fadjar', 'Serbuk Kayu', 4, 60000, '2025-02-05', 'Lunas', 'bukti/1762092800_bukti_pembayaran_jpg', 0, 0, 0, 0, 0, 0),
(4, 'BRD04', 'Sappa Sao', 'Kulit Kemiri', 3, 120000, '2025-02-10', 'Lunas', 'bukti/1762093074_bukti_pembayaran_jpg', 0, 0, 0, 0, 0, 0),
(5, 'BRD05', 'Bunga Putri', 'Kulit Kemiri', 3, 120000, '2025-03-09', 'Lunas', 'bukti/1762139547_bukti_pembayaran_jpg', 0, 0, 0, 0, 0, 0),
(6, 'BRD06', 'Dimas Saputra', 'Kulit Kemiri', 1, 40000, '2025-03-22', 'Lunas', 'bukti/1763129950_kesimpulan_jpg', 0, 0, 0, 0, 0, 0),
(7, 'BRD07', 'Salsabila Al-Mugni', 'Tempurung', 14, 630000, '2025-04-25', 'Lunas', 'bukti/1763276527_kesimpulan_jpg', 0, 0, 0, 0, 0, 0),
(8, 'BRD08', 'Kayla Cahyani Aurelia', 'Tempurung', 2, 90000, '2025-04-02', 'Lunas', 'bukti/1765950736_penarikan_jpg', 0, 0, 0, 0, 0, 0),
(9, 'BRD09', 'Fahrel Rafa Rizky', 'Serbuk Kayu', 1, 30000, '2025-02-17', 'Lunas', 'bukti/1766892996_2_png', 0, 0, 0, 0, 0, 0),
(10, 'BRD10', 'Safiya Anindita', 'Sekam Padi', 4, 120000, '2025-02-24', 'Lunas', 'bukti/1766986286_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(11, 'BRD11', 'Rahmat Saputra', 'Tempurung', 3, 135000, '2025-02-12', 'Lunas', 'bukti/1766986390_Screenshot_2025_09_22_131649_png', 0, 0, 0, 0, 0, 0),
(12, 'BRD12', 'Arya Prasetya', 'Kulit Kemiri', 2, 80000, '2025-03-11', 'Lunas', 'bukti/1767072163_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(13, 'BRD13', 'Uki Kurniawan', 'Tempurung', 1, 45000, '2025-03-06', 'Lunas', 'bukti/1767072203_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(14, 'BRD14', 'Ana Andany Ramadani', 'Tempurung Kelapa', 2, 90000, '2025-02-27', 'Lunas', 'bukti/1767073262_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(15, 'BRD15', 'Alya Putri Candrawati', 'Tempurung Kelapa', 1, 45000, '2025-03-07', 'Lunas', 'bukti/1767073434_Screenshot_2025_12_10_213834_png', 0, 0, 0, 0, 0, 0),
(16, 'BRD16', 'Elvano Pradipta', 'Kulit Kemiri', 2, 80000, '2025-03-18', 'Lunas', 'bukti/1767073513_Screenshot_2025_12_01_143442_png', 0, 0, 0, 0, 0, 0),
(17, 'BRD17', 'Shaqueel', 'Sekam Padi', 2, 60000, '2025-03-24', 'Lunas', 'bukti/1767075430_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(19, 'BRD19', 'Riqar Muhammad', 'Tempurung Kelapa', 1, 45000, '2025-02-16', 'Lunas', 'bukti/1767080973_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(20, 'BRD20', 'Kirana Rahmani', 'Tempurung Kelapa', 1, 45000, '2025-04-16', 'Lunas', 'bukti/1767083012_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(22, 'BRD22', 'Bayu Mahendra Putra ', 'Kulit Kemiri', 1, 40000, '2025-05-04', 'Lunas', 'bukti/1767235387_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(23, 'BRD23', 'Sri Murniati', 'Kulit Kemiri', 2, 80000, '2025-05-09', 'Lunas', 'bukti/1767236023_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(24, 'BRD24', 'Muhammad Ali', 'Tempurung Kelapa', 1, 45000, '2025-05-10', 'Lunas', 'bukti/1767236143_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(25, 'BRD25', 'Muhammad Fawwaz', 'Kulit Kemiri', 1, 40000, '2025-05-13', 'Lunas', 'bukti/1767236846_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(26, 'BRD26', 'Rismawati', 'Kulit Kemiri', 1, 40000, '2025-05-15', 'Lunas', 'bukti/1767236998_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(27, 'BRD27', 'Khaerul Amin', 'Tempurung Kelapa', 1, 45000, '2025-05-18', 'Lunas', 'bukti/1767237355_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(29, 'BRD29', 'Qorim Baitullah', 'Kulit Kemiri', 1, 40000, '2025-05-20', 'Lunas', 'bukti/1767238295_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(30, 'BRD30', 'Rara Selsadila', 'Tempurung Kelapa', 1, 45000, '2025-06-09', 'Lunas', 'bukti/1767238591_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(31, 'BRD31', 'Muhammad Haekal', 'Serbuk Kayu', 1, 30000, '2025-06-11', 'Lunas', 'bukti/1767238734_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(32, 'BRD32', 'Dilan', 'Kulit Kemiri', 1, 40000, '2025-06-15', 'Lunas', 'bukti/1767238969_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(33, 'BRD33', 'Milea Almahira', 'Kulit Kemiri', 2, 80000, '2025-06-16', 'Menolak', 'bukti/1767239533_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(34, 'BRD34', 'Olivia Isabella', 'Tempurung Kelapa', 1, 45000, '2025-07-02', 'Lunas', 'bukti/1767239983_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(35, 'BRD35', 'Kirana Olia Putri', 'Kulit Kemiri', 1, 40000, '2025-07-12', 'Lunas', 'bukti/1767240635_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(36, 'BRD36', 'Dahlia Polan', 'Kulit Kemiri', 1, 40000, '2025-07-14', 'Lunas', 'bukti/1767241407_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(37, 'BRD37', 'Ahmad Zayn', 'Sekam Padi', 1, 30000, '2025-07-21', 'Lunas', 'bukti/1767241483_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(43, 'BRD43', 'Muhammad Yono', 'Kulit Kemiri', 1, 40000, '2025-08-03', 'Lunas', 'bukti/1767245543_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(44, 'BRD44', 'Khaka', 'Tempurung Kelapa', 1, 45000, '2025-08-09', 'Lunas', 'bukti/1767245695_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(45, 'BRD45', 'Layla Olip', 'Serbuk Kayu', 1, 30000, '2025-08-12', 'Menolak', 'bukti/1767246126_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(46, 'BRD46', 'Nayara Zafira', 'Serbuk Kayu', 1, 30000, '2025-08-21', 'Lunas', 'bukti/1767246892_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(47, 'BRD47', 'Ilham Nugraha', 'Serbuk Kayu', 1, 30000, '2025-09-03', 'Menolak', 'bukti/1767253781_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(48, 'BRD48', 'Fajar Boy Wiliam', 'Sekam Padi', 1, 30000, '2025-09-06', 'Lunas', 'bukti/1767253841_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(49, 'BRD49', 'Daffa Al-Fatih', 'Tempurung Kelapa', 2, 90000, '2025-09-28', 'Lunas', 'bukti/1767254167_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(50, 'BRD50', 'Raihan Halilintar', 'Kulit Kemiri', 2, 80000, '2025-10-09', 'Lunas', 'bukti/1767338920_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(51, 'BRD51', 'Wiwik Permata Sari', 'Serbuk Kayu', 2, 60000, '2025-10-14', 'Lunas', 'bukti/1767342135_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(52, 'BRD52', 'Ernata Aulia', 'Kulit Kemiri', 2, 80000, '2025-10-22', 'Lunas', 'bukti/1767342188_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(53, 'BRD53', 'Olia Putra Ona', 'Sekam Padi', 1, 30000, '2025-11-05', 'Lunas', 'bukti/1767342311_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(54, 'BRD54', 'Surya Pameng', 'Tempurung Kelapa', 1, 45000, '2025-11-13', 'Menolak', 'bukti/1767342414_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(55, 'BRD55', 'Tono Wijaya', 'Tempurung Kelapa', 1, 45000, '2025-11-19', 'Lunas', 'bukti/1767342526_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(56, 'BRD56', 'Alif Wahidin Dadi', 'Sekam Padi', 1, 30000, '2025-11-26', 'Lunas', 'bukti/1767342690_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(57, 'BRD57', 'Erina Cimoyela', 'Sekam Padi', 2, 60000, '2025-12-01', 'Lunas', 'bukti/1767343040_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(58, 'BRD58', 'Qhalik Makmur', 'Serbuk Kayu', 4, 120000, '2025-12-02', 'Menolak', 'bukti/1767343126_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(59, 'BRD59', 'Syachiqa Ahwan', 'Serbuk Kayu', 2, 60000, '2025-12-19', 'Lunas', 'bukti/1767343537_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(60, 'BRD60', 'Ulia Harjanti', 'Sekam Padi', 2, 60000, '2025-12-25', 'Lunas', 'bukti/1767343711_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(61, 'BRD61', 'Andi Hidayat', 'Tempurung Kelapa', 2, 90000, '2026-01-02', 'Lunas', 'bukti/1767343783_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(62, 'BRD62', 'Haruka Obami', 'Tempurung Kelapa', 2, 90000, '2026-01-02', 'Menolak', 'bukti/1767343842_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(63, 'BRD63', 'Nayla Syasya', 'Kulit Kemiri', 4, 160000, '2026-01-02', 'Lunas', 'bukti/1767345048_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(64, 'BRD64', 'Elsa Caysril', 'Tempurung Kelapa', 2, 90000, '2026-01-02', 'Lunas', 'bukti/1767355192_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(65, 'BRD65', 'Oksi Serli', 'Sekam Padi', 3, 90000, '2026-01-02', 'Lunas', 'bukti/1767357874_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(66, 'BRD66', 'Nikita Putri', 'Kulit Kemiri', 4, 160000, '2026-01-02', 'Lunas', 'bukti/1767358037_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(67, 'BRD67', 'Muhammad Ilham Fadlan', 'Sekam Padi', 5, 150000, '2026-01-03', 'Lunas', 'bukti/1767419614_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(68, 'BRD68', 'Kaelan Kiyyara ', 'Serbuk Kayu', 7, 210000, '2026-01-03', 'Lunas', 'bukti/1767419786_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(69, 'BRD69', 'Bimo Rifaldy\r\n', 'Tempurung Kelapa', 2, 81000, '2026-01-03', 'Lunas', 'bukti/1767420019_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(70, 'BRD70', 'Salsabila', 'Serbuk Kayu', 3, 90000, '2026-01-03', 'Lunas', 'bukti/1767420508_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(71, 'BRD71', 'Rafaela Alvaro', 'Sekam Padi', 1, 27000, '2026-01-03', 'Lunas', 'bukti/1767420664_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(72, 'BRD72', 'Rizky Arif Harimo', 'Serbuk Kayu', 7, 178500, '2026-01-03', 'Lunas', 'bukti/1767445145_Bukti_Transfer_jpeg', 1, 210000, 15, 31500, 1, 1),
(73, 'BRD73', 'JulistioFadjar21', 'Kulit Kemiri', 4, 160000, '2026-01-07', 'Lunas', 'bukti/1767768881_Bukti_Transfer_jpeg', 1, 160000, 0, 0, 0, 0),
(74, 'BRD74', 'JULISTIO FADJAR ANUGRAH SAO', 'Sekam Padi', 4, 120000, '2026-01-11', 'Lunas', 'bukti/1768098433_bukti_pesanan_49_1767254167_Bukti_Transfer_jpeg_jpeg', 1, 120000, 0, 0, 0, 0),
(75, 'BRD75', 'JULISTIO FADJAR ANUGRAH SAO', 'Kulit Kemiri', 2, 72000, '2026-01-11', 'Lunas', 'bukti/1768099025_bukti_pesanan_49_1767254167_Bukti_Transfer_jpeg_jpeg', 1, 80000, 10, 8000, 0, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `alamat` varchar(255) NOT NULL,
  `vip_level` tinyint(1) NOT NULL DEFAULT 0,
  `total_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `role`, `alamat`, `vip_level`, `total_kg`, `foto`) VALUES
(25, 'Salsabila', 'Salsabila01@gmail.com', '$2y$10$aM0pJOFiT2AUYrsVw9zG/.8bwA25rmS1l5qSjTczuUIUmep8n9Jk6', 'user', 'Jln. Ujung Pandang', 1, 18.00, 'uploads/profil/salsabila_1767261915_cbc15d27.jpg'),
(27, 'Cacabila', 'Cacabila01@gmail.com', '$2y$10$XS7QQ/awzW0d4XREQEdBoeAJWwnTokX/bLeBpqrk.tHdAxOuSlyuy', 'user', 'Jln. Kenanga Raya', 2, 9.00, 'uploads/profil/cacabila_1767261509_727cace9.jpg'),
(28, 'JulistioFadjar21', 'Julistio21@gmail.com', '$2y$10$7.4t1QRF5ciLf6n7.mBOsezw8cLZx8JrPJMTOJcpQuv4DzFGX66Pe', 'user', 'Jln. M Yusuf Majid', 2, 4.00, 'uploads/profil/julistiofadjar21_1767768858_8a267776.jpg'),
(29, 'JULISTIO FADJAR ANUGRAH SAO', 'julistyofadjar21@gmail.com', '$2y$10$tbxMHiEoQalGzkeWDjCozesVyrJeFEUgM7b7sbFm.uAs8CMozSsce', 'user', 'parepare', 2, 6.00, 'uploads/profil/julistio_fadjar_anugrah_sao_1768064731_ef4ac0b5.jpg');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `bahan_baku`
--
ALTER TABLE `bahan_baku`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `uq_kode_pesanan` (`kode_pesanan`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `bahan_baku`
--
ALTER TABLE `bahan_baku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
