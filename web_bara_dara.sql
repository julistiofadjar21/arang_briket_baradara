-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2026 at 02:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `admin`
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
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`, `role`, `created_at`, `foto`) VALUES
(3, 'admin', 'admin@gmail.com', '$2y$10$ECdx/N12Sb75knnb4TMLz.sZP9fMO98mctZmgDB0gknmVBRK3.djS', 'admin', '2024-12-24 11:36:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bahan_baku`
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
-- Dumping data for table `bahan_baku`
--

INSERT INTO `bahan_baku` (`id`, `nama_bahan`, `harga_jual`, `biaya_produksi`, `jumlah_terjual`, `stok`, `gambar`, `link`) VALUES
(1, 'Kulit Kemiri', 40000, 25000, 154, 6, 'kulit kemiri.jpg', 'bijikemiri.html'),
(2, 'Tempurung Kelapa', 45000, 20000, 219, 8, 'arang-batok.jpg', 'tempurungkelapa.html'),
(3, 'Serbuk Kayu', 30000, 15000, 187, 4, 'serbuk kayu.jpg', 'serbukkayu.html'),
(4, 'Sekam Padi', 30000, 18000, 100, 5, 'sekam padi.jpg', 'sekampadi.html');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
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
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `kode_pesanan`, `nama_pembeli`, `bahan_baku`, `jumlah_kg`, `total_harga`, `tanggal`, `status_pesanan`, `bukti_transfer`, `stok_dipotong`, `subtotal`, `diskon_persen`, `diskon_rp`, `bonus_kg`, `gratis_ongkir`) VALUES
(3, 'BRD03', 'susi', 'Batubara', 4, 60000, '2025-11-05', 'Lunas', 'bukti/1762092800_bukti_pembayaran_jpg', 0, 0, 0, 0, 0, 0),
(4, 'BRD04', 'omtayo', 'Kulit Kemiri', 3, 120000, '2025-11-10', 'Lunas', 'bukti/1762093074_bukti_pembayaran_jpg', 0, 0, 0, 0, 0, 0),
(5, 'BRD05', 'rani', 'Kulit Kemiri', 3, 120000, '2025-11-03', 'Lunas', 'bukti/1762139547_bukti_pembayaran_jpg', 0, 0, 0, 0, 0, 0),
(6, 'BRD06', 'tio', 'Kulit Kemiri', 1, 40000, '2025-11-23', 'Lunas', 'bukti/1763129950_kesimpulan_jpg', 0, 0, 0, 0, 0, 0),
(7, 'BRD07', 'diky', 'Tempurung', 14, 630000, '2025-08-25', 'Lunas', 'bukti/1763276527_kesimpulan_jpg', 0, 0, 0, 0, 0, 0),
(8, 'BRD08', 'tio', 'Tempurung', 2, 90000, '2025-12-17', 'Lunas', 'bukti/1765950736_penarikan_jpg', 0, 0, 0, 0, 0, 0),
(9, 'BRD09', 'Sabil Cantik ', 'Serbuk Kayu', 1, 30000, '2025-12-28', 'Lunas', 'bukti/1766892996_2_png', 0, 0, 0, 0, 0, 0),
(10, 'BRD10', 'Sumanto', 'Sekam Padi', 4, 120000, '2025-12-29', 'Lunas', 'bukti/1766986286_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(11, 'BRD11', 'Rahmat Saputra', 'Tempurung', 3, 135000, '2025-12-29', 'Lunas', 'bukti/1766986390_Screenshot_2025_09_22_131649_png', 0, 0, 0, 0, 0, 0),
(12, 'BRD12', 'Salsabila', 'Kulit Kemiri', 2, 80000, '2025-12-30', 'Lunas', 'bukti/1767072163_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(13, 'BRD13', 'Salsabila', 'Tempurung', 1, 45000, '2025-12-30', 'Lunas', 'bukti/1767072203_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(14, 'BRD14', 'Cacabila', 'Tempurung Kelapa', 2, 90000, '2025-12-30', 'Lunas', 'bukti/1767073262_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(15, 'BRD15', 'Chacabilah', 'Tempurung Kelapa', 1, 45000, '2025-12-30', 'Lunas', 'bukti/1767073434_Screenshot_2025_12_10_213834_png', 0, 0, 0, 0, 0, 0),
(16, 'BRD16', 'Julistio Fadjar', 'Kulit Kemiri', 2, 80000, '2025-12-30', 'Lunas', 'bukti/1767073513_Screenshot_2025_12_01_143442_png', 0, 0, 0, 0, 0, 0),
(17, 'BRD17', 'Shaqueel', 'Sekam Padi', 2, 60000, '2025-12-30', 'Lunas', 'bukti/1767075430_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(19, 'BRD19', 'Riqar Muhammad', 'Tempurung Kelapa', 1, 45000, '2025-12-30', 'Lunas', 'bukti/1767080973_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(20, 'BRD20', 'Salsabila', 'Tempurung Kelapa', 1, 45000, '2025-12-30', 'Lunas', 'bukti/1767083012_Screenshot_2025_09_19_222759_png', 0, 0, 0, 0, 0, 0),
(22, 'BRD22', 'Salsabila', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767235387_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(23, 'BRD23', 'Sri Murniati', 'Kulit Kemiri', 2, 80000, '2026-01-01', 'Lunas', 'bukti/1767236023_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(24, 'BRD24', 'Muhammad Ali', 'Tempurung Kelapa', 1, 45000, '2026-01-01', 'Lunas', 'bukti/1767236143_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(25, 'BRD25', 'Muhammad Fawwaz', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767236846_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(26, 'BRD26', 'Sakura Felica', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767236998_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(27, 'BRD27', 'Khaerul Amin', 'Tempurung Kelapa', 1, 45000, '2026-01-01', 'Lunas', 'bukti/1767237355_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(29, 'BRD29', 'Qorim Baitullah', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767238295_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(30, 'BRD30', 'Rara Selsadila', 'Tempurung Kelapa', 1, 45000, '2026-01-01', 'Lunas', 'bukti/1767238591_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(31, 'BRD31', 'Muhammad Haekal', 'Serbuk Kayu', 1, 30000, '2026-01-01', 'Lunas', 'bukti/1767238734_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(32, 'BRD32', 'Dilan', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767238969_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(33, 'BRD33', 'Milea Salsabila', 'Kulit Kemiri', 2, 80000, '2026-01-01', 'Menolak', 'bukti/1767239533_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(34, 'BRD34', 'Olivia Salsabila', 'Tempurung Kelapa', 1, 45000, '2026-01-01', 'Lunas', 'bukti/1767239983_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(35, 'BRD35', 'Merpati Rosidin', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767240635_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(36, 'BRD36', 'Dahlia Polan', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767241407_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(37, 'BRD37', 'Acep Sumanto', 'Sekam Padi', 1, 30000, '2026-01-01', 'Lunas', 'bukti/1767241483_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(43, 'BRD43', 'Muhammad', 'Kulit Kemiri', 1, 40000, '2026-01-01', 'Lunas', 'bukti/1767245543_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(44, 'BRD44', 'Khaka', 'Tempurung Kelapa', 1, 45000, '2026-01-01', 'Lunas', 'bukti/1767245695_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(45, 'BRD45', 'Layla Olip', 'Serbuk Kayu', 1, 30000, '2026-01-01', 'Menolak', 'bukti/1767246126_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(46, 'BRD46', 'Julistio', 'Serbuk Kayu', 1, 30000, '2026-01-01', 'Lunas', 'bukti/1767246892_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(47, 'BRD47', 'Fadli', 'Serbuk Kayu', 1, 30000, '2026-01-01', 'Menolak', 'bukti/1767253781_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(48, 'BRD48', 'Fajar', 'Sekam Padi', 1, 30000, '2026-01-01', 'Lunas', 'bukti/1767253841_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(49, 'BRD49', 'Syakira', 'Tempurung Kelapa', 2, 90000, '2026-01-01', 'Lunas', 'bukti/1767254167_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(50, 'BRD50', 'Raihan Rekar', 'Kulit Kemiri', 2, 80000, '2026-01-02', 'Lunas', 'bukti/1767338920_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(51, 'BRD51', 'Wiwik Permata Sari', 'Serbuk Kayu', 2, 60000, '2026-01-02', 'Lunas', 'bukti/1767342135_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(52, 'BRD52', 'Ernata Aulia', 'Kulit Kemiri', 2, 80000, '2026-01-02', 'Lunas', 'bukti/1767342188_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(53, 'BRD53', 'Olia Putriy', 'Sekam Padi', 1, 30000, '2026-01-02', 'Lunas', 'bukti/1767342311_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(54, 'BRD54', 'Surya Pameng', 'Tempurung Kelapa', 1, 45000, '2026-01-02', 'Menolak', 'bukti/1767342414_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(55, 'BRD55', 'Hartono Dasar', 'Tempurung Kelapa', 1, 45000, '2026-01-02', 'Lunas', 'bukti/1767342526_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(56, 'BRD56', 'Alif Wahidin Dadi', 'Sekam Padi', 1, 30000, '2026-01-02', 'Lunas', 'bukti/1767342690_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(57, 'BRD57', 'Erina Cimoyela', 'Sekam Padi', 2, 60000, '2026-01-02', 'Lunas', 'bukti/1767343040_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(58, 'BRD58', 'Qhalik Makmur', 'Serbuk Kayu', 4, 120000, '2026-01-02', 'Menolak', 'bukti/1767343126_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(59, 'BRD59', 'Syachiqa Ahwan', 'Serbuk Kayu', 2, 60000, '2026-01-02', 'Lunas', 'bukti/1767343537_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(60, 'BRD60', 'Ulia Harjanti', 'Sekam Padi', 2, 60000, '2026-01-02', 'Lunas', 'bukti/1767343711_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(61, 'BRD61', 'Yaya', 'Tempurung Kelapa', 2, 90000, '2026-01-02', 'Lunas', 'bukti/1767343783_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(62, 'BRD62', 'Haruka', 'Tempurung Kelapa', 2, 90000, '2026-01-02', 'Menolak', 'bukti/1767343842_Bukti_Transfer_jpeg', 0, 0, 0, 0, 0, 0),
(63, 'BRD63', 'Tataulah', 'Kulit Kemiri', 4, 160000, '2026-01-02', 'Lunas', 'bukti/1767345048_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(64, 'BRD64', 'Elsa Caysril', 'Tempurung Kelapa', 2, 90000, '2026-01-02', 'Lunas', 'bukti/1767355192_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(65, 'BRD65', 'Salsabila', 'Sekam Padi', 3, 90000, '2026-01-02', 'Lunas', 'bukti/1767357874_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(66, 'BRD66', 'Salsabila', 'Kulit Kemiri', 4, 160000, '2026-01-02', 'Lunas', 'bukti/1767358037_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(67, 'BRD67', 'Julistio', 'Sekam Padi', 5, 150000, '2026-01-03', 'Lunas', 'bukti/1767419614_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(68, 'BRD68', 'Cacabila', 'Serbuk Kayu', 7, 210000, '2026-01-03', 'Lunas', 'bukti/1767419786_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(69, 'BRD69', 'Cacabila', 'Tempurung Kelapa', 2, 81000, '2026-01-03', 'Lunas', 'bukti/1767420019_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(70, 'BRD70', 'Salsabila', 'Serbuk Kayu', 3, 90000, '2026-01-03', 'Lunas', 'bukti/1767420508_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(71, 'BRD71', 'Salsabila', 'Sekam Padi', 1, 27000, '2026-01-03', 'Lunas', 'bukti/1767420664_Bukti_Transfer_jpeg', 1, 0, 0, 0, 0, 0),
(72, 'BRD72', 'Salsabila', 'Serbuk Kayu', 7, 178500, '2026-01-03', 'Lunas', 'bukti/1767445145_Bukti_Transfer_jpeg', 1, 210000, 15, 31500, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
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
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `email`, `password`, `role`, `alamat`, `vip_level`, `total_kg`, `foto`) VALUES
(12, 'susi', 'susi@gmail.com', '$2y$10$.hDWkGklFUPP9', 'user', '', 1, 11.00, NULL),
(13, 'diky', 'diky@gmail.com', '$2y$10$cETymmeh3viLE', 'user', '', 3, 61.00, NULL),
(14, 'Mikel', 'testmail@gmail.com', '$2y$10$C5/vrRlnPmWWQ', 'user', '', 3, 64.00, NULL),
(15, 'uceng', 'uceng@gmail.com', '$2y$10$1ex/O/Jct6J/o', 'user', '', 0, 0.00, NULL),
(16, 'bahrul', 'bahrul@gmail.com', '$2y$10$gfXCafT905iR4', 'user', '', 0, 0.00, NULL),
(25, 'Cacabila', 'Cacabila01@gmail.com', '$2y$10$XS7QQ/awzW0d4XREQEdBoeAJWwnTokX/bLeBpqrk.tHdAxOuSlyuy', 'user', 'Jln. Ujung Pandang', 2, 9.00, 'uploads/profil/cacabila_1767261509_727cace9.jpg'),
(26, 'Julistio', 'Julistio01@gmail.com', '$2y$10$fUYcjxSCOsxdr6YcaWoHdub6DtW69e30ncQdb4jeM3iFyJo4Jda9S', 'user', 'Jln. Pandang', 2, 5.00, 'uploads/profil/julistio_1767261834_179b6925.jpg'),
(27, 'Salsabila', 'Salsabila01@gmail.com', '$2y$10$aM0pJOFiT2AUYrsVw9zG/.8bwA25rmS1l5qSjTczuUIUmep8n9Jk6', 'user', 'Jln. Ujung Pandang', 1, 18.00, 'uploads/profil/salsabila_1767261915_cbc15d27.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bahan_baku`
--
ALTER TABLE `bahan_baku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `uq_kode_pesanan` (`kode_pesanan`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bahan_baku`
--
ALTER TABLE `bahan_baku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
