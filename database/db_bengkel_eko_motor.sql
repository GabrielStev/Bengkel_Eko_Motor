-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 06:00 PM
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
-- Database: `db_bengkel_eko_motor`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_jasa`
--

CREATE TABLE `detail_jasa` (
  `no_transaksi` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `tarif` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `no_pembelian` varchar(20) NOT NULL,
  `part_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL CHECK (`qty` > 0),
  `harga` decimal(12,2) NOT NULL CHECK (`harga` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pembelian`
--

INSERT INTO `detail_pembelian` (`no_pembelian`, `part_id`, `qty`, `harga`) VALUES
('BEL-69BDD489FB5235E6', 9, 2, 150000.00),
('BEL-91A18249ABEEA4D2', 4, 2, 150000.00),
('BEL-D61BBC04B41CE2E3', 5, 1, 30000.00);

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `id_detail` int(11) NOT NULL,
  `no_transaksi` varchar(20) NOT NULL,
  `id_sparepart` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL CHECK (`jumlah` > 0),
  `harga_satuan` decimal(12,2) NOT NULL CHECK (`harga_satuan` >= 0),
  `subtotal` decimal(12,2) NOT NULL CHECK (`subtotal` >= 0),
  `nama_snapshot` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_penjualan`
--

INSERT INTO `detail_penjualan` (`id_detail`, `no_transaksi`, `id_sparepart`, `jumlah`, `harga_satuan`, `subtotal`, `nama_snapshot`) VALUES
(1, 'TRX-0011', 1, 1, 45000.00, 45000.00, NULL),
(2, 'TRX-0011', 7, 1, 35000.00, 35000.00, NULL),
(3, 'TRX-0012', 9, 1, 195000.00, 195000.00, NULL),
(4, 'TRX-0013', 3, 1, 20000.00, 20000.00, NULL),
(5, 'TRX-0013', 7, 1, 30000.00, 30000.00, NULL),
(6, 'TRX-DDF77E3F63DC7395', 2, 1, 35000.00, 35000.00, 'Kampas Rem Depan Beat'),
(7, 'TRX-DDF77E3F63DC7395', 6, 1, 35000.00, 35000.00, 'Jasa Servis Ringan / Tune Up'),
(8, 'TRX-DDF77E3F63DC7395', 7, 1, 15000.00, 15000.00, 'Jasa Ganti Oli & Filter'),
(9, 'TRX-D2FD59B17BA7B596', 1, 1, 45000.00, 45000.00, 'Oli Mesin Federal Supreme'),
(10, 'TRX-D2FD59B17BA7B596', 5, 1, 40000.00, 40000.00, 'Filter Udara Vario 125'),
(11, 'TRX-D2FD59B17BA7B596', 10, 1, 30000.00, 30000.00, 'Kampas Rem Belakang'),
(12, 'TRX-1AB4FCEB208B099C', 1, 1, 45000.00, 45000.00, 'Oli Mesin Federal Supreme'),
(13, 'TRX-1AB4FCEB208B099C', 3, 1, 20000.00, 20000.00, 'Busi NGK Standard'),
(14, 'TRX-1AB4FCEB208B099C', 6, 1, 35000.00, 35000.00, 'Jasa Servis Ringan / Tune Up'),
(15, 'TRX-1AB4FCEB208B099C', 10, 1, 30000.00, 30000.00, 'Kampas Rem Belakang'),
(16, 'TRX-E5FD6D657F088B04', 7, 1, 15000.00, 15000.00, 'Jasa Ganti Oli & Filter'),
(17, 'TRX-46FD085C40EF27CD', 1, 1, 45000.00, 45000.00, 'Oli Mesin Federal Supreme'),
(18, 'TRX-94E2BC9542E449E8', 3, 1, 20000.00, 20000.00, 'Busi NGK Standard');

-- --------------------------------------------------------

--
-- Table structure for table `jasa`
--

CREATE TABLE `jasa` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tarif` decimal(12,2) NOT NULL CHECK (`tarif` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jasa`
--

INSERT INTO `jasa` (`id`, `nama`, `tarif`) VALUES
(1, 'Servis ringan', 50000.00),
(2, 'Ganti oli', 15000.00);

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int(11) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `pelat` varchar(20) NOT NULL,
  `model` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `pelanggan_id`, `pelat`, `model`) VALUES
(1, 1, 'AB 1234 AA', 'Mio'),
(2, 2, 'AA 4949 BM', 'Scoopy');

-- --------------------------------------------------------

--
-- Table structure for table `mutasi_stok`
--

CREATE TABLE `mutasi_stok` (
  `id` bigint(20) NOT NULL,
  `part_id` int(11) NOT NULL,
  `pengguna_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL CHECK (`jumlah` <> 0),
  `jenis` varchar(20) NOT NULL,
  `referensi` varchar(20) DEFAULT NULL,
  `alasan` varchar(500) DEFAULT NULL,
  `dibuat` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mutasi_stok`
--

INSERT INTO `mutasi_stok` (`id`, `part_id`, `pengguna_id`, `jumlah`, `jenis`, `referensi`, `alasan`, `dibuat`) VALUES
(1, 9, 1, 2, 'Pembelian', 'BEL-69BDD489FB5235E6', NULL, '2026-11-17 21:44:34'),
(2, 2, 2, -1, 'Penjualan', 'TRX-DDF77E3F63DC7395', NULL, '2026-11-17 21:46:23'),
(3, 6, 2, -1, 'Penjualan', 'TRX-DDF77E3F63DC7395', NULL, '2026-11-17 21:46:23'),
(4, 7, 2, -1, 'Penjualan', 'TRX-DDF77E3F63DC7395', NULL, '2026-11-17 21:46:23'),
(5, 1, 2, -1, 'Penjualan', 'TRX-D2FD59B17BA7B596', NULL, '2026-11-18 12:07:30'),
(6, 5, 2, -1, 'Penjualan', 'TRX-D2FD59B17BA7B596', NULL, '2026-11-18 12:07:30'),
(7, 10, 2, -1, 'Penjualan', 'TRX-D2FD59B17BA7B596', NULL, '2026-11-18 12:07:30'),
(8, 9, 1, 4, 'Penyesuaian', NULL, 'tambah pembelian', '2026-11-19 13:47:45'),
(9, 5, 1, 1, 'Pembelian', 'BEL-D61BBC04B41CE2E3', NULL, '2026-11-19 13:48:16'),
(10, 4, 1, 2, 'Pembelian', 'BEL-91A18249ABEEA4D2', NULL, '2026-11-19 16:55:38'),
(11, 9, 1, -3, 'Penyesuaian', NULL, 'contoh', '2026-11-19 16:58:02'),
(12, 1, 2, -1, 'Penjualan', 'TRX-1AB4FCEB208B099C', NULL, '2026-11-19 17:04:15'),
(13, 3, 2, -1, 'Penjualan', 'TRX-1AB4FCEB208B099C', NULL, '2026-11-19 17:04:15'),
(14, 6, 2, -1, 'Penjualan', 'TRX-1AB4FCEB208B099C', NULL, '2026-11-19 17:04:15'),
(15, 10, 2, -1, 'Penjualan', 'TRX-1AB4FCEB208B099C', NULL, '2026-11-19 17:04:15'),
(16, 7, 2, -1, 'Penjualan', 'TRX-E5FD6D657F088B04', NULL, '2026-11-22 23:25:29'),
(17, 1, 2, -1, 'Penjualan', 'TRX-46FD085C40EF27CD', NULL, '2026-11-22 23:50:38'),
(18, 3, 2, -1, 'Penjualan', 'TRX-94E2BC9542E449E8', NULL, '2026-11-22 23:51:02');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `telepon` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `nama`, `telepon`) VALUES
(1, 'Asep', '08123456789'),
(2, 'Joko', '08999999999');

-- --------------------------------------------------------

--
-- Table structure for table `pemasok`
--

CREATE TABLE `pemasok` (
  `id_pemasok` int(11) NOT NULL,
  `nama_pemasok` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pemasok`
--

INSERT INTO `pemasok` (`id_pemasok`, `nama_pemasok`, `no_hp`, `alamat`) VALUES
(1, 'PT Federal Kencana (Oli)', '081223344555', 'Jl. Ring Road Utara No. 12, Yogyakarta'),
(2, 'CV Astra Otoparts Jaya', '081334455666', 'Jl. Magelang Km. 5, Sleman'),
(3, 'Toko Sparepart Motor Barokah', '081556677889', 'Jl. Solo Km. 7, Depok, Sleman'),
(4, 'Distributor Ban IRC Jogja', '081889900112', 'Jl. Hos Cokroaminoto No. 45, Yogyakarta'),
(5, 'Mega Aki Sejahtera', '081990011223', 'Jl. Godean Km. 4, Yogyakarta');

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `no_pembelian` varchar(20) NOT NULL,
  `tanggal_pembelian` datetime NOT NULL,
  `id_pemasok` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `total_pembelian` decimal(12,2) NOT NULL CHECK (`total_pembelian` > 0),
  `status_bayar` enum('Lunas','Utang') NOT NULL,
  `token` char(32) DEFAULT NULL,
  `lunas_pada` datetime DEFAULT NULL,
  `lunas_oleh` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`no_pembelian`, `tanggal_pembelian`, `id_pemasok`, `id_pengguna`, `total_pembelian`, `status_bayar`, `token`, `lunas_pada`, `lunas_oleh`) VALUES
('BEL-69BDD489FB5235E6', '2026-11-17 21:44:34', 2, 1, 300000.00, 'Lunas', '36b24bf030f70f7738c9e41ebb179fca', '2026-11-17 21:44:34', 1),
('BEL-91A18249ABEEA4D2', '2026-11-19 16:55:38', 4, 1, 300000.00, 'Lunas', 'c8f0ac10f0a5749ee373b0cde8ba28d5', '2026-11-19 16:55:38', 1),
('BEL-D61BBC04B41CE2E3', '2026-11-19 13:48:16', 2, 1, 30000.00, 'Lunas', '545172cbc5d2ed1e5397cdf27a2fbd02', '2026-11-19 13:48:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `peran` enum('Pemilik','Kasir','Mekanik') NOT NULL,
  `status_aktif` enum('Aktif','Nonaktif') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama_lengkap`, `username`, `password_hash`, `peran`, `status_aktif`) VALUES
(1, 'Gabriel Steven', 'gabriel_owner', '$2y$10$aY9J.C/wLSAPtMgiBq8QE.MlrT6ERVt.YQzsCEHlSHNdRBH0.FNC2', 'Pemilik', 'Aktif'),
(2, 'Warid Akbar', 'warid_kasir', '$2y$10$fDJV4dhZ8GTNFgdM/EMtgey1BF2s9TKv8YljkWqsENXpnyFgT64pq', 'Kasir', 'Aktif'),
(3, 'Mekanik Bengkel', 'mekanik1', '$2y$10$ErD62xp3bT9DhGvsGqjyy.lze3AKbWB.QML/Kgta0FNWrbQgC.M0e', 'Mekanik', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `no_transaksi` varchar(20) NOT NULL,
  `tanggal_transaksi` datetime NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `total_bayar` decimal(12,2) NOT NULL CHECK (`total_bayar` > 0),
  `metode_bayar` enum('Tunai','Transfer','QRIS') NOT NULL,
  `servis_id` int(11) DEFAULT NULL,
  `pelanggan_id` int(11) DEFAULT NULL,
  `kendaraan_id` int(11) DEFAULT NULL,
  `token` char(32) DEFAULT NULL,
  `status` enum('Lunas','Batal') NOT NULL DEFAULT 'Lunas',
  `diterima` decimal(12,2) DEFAULT NULL,
  `alasan_batal` varchar(500) DEFAULT NULL,
  `batal_pada` datetime DEFAULT NULL,
  `batal_oleh` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`no_transaksi`, `tanggal_transaksi`, `id_pengguna`, `total_bayar`, `metode_bayar`, `servis_id`, `pelanggan_id`, `kendaraan_id`, `token`, `status`, `diterima`, `alasan_batal`, `batal_pada`, `batal_oleh`) VALUES
('TRX-0011', '2026-11-02 09:30:00', 2, 80000.00, 'Tunai', NULL, NULL, NULL, NULL, 'Lunas', NULL, NULL, NULL, NULL),
('TRX-0012', '2026-11-02 11:15:00', 2, 195000.00, 'QRIS', NULL, NULL, NULL, NULL, 'Lunas', NULL, NULL, NULL, NULL),
('TRX-0013', '2026-11-03 13:00:00', 2, 50000.00, 'Tunai', NULL, NULL, NULL, NULL, 'Lunas', NULL, NULL, NULL, NULL),
('TRX-1AB4FCEB208B099C', '2026-11-19 17:04:15', 2, 130000.00, 'Transfer', NULL, 2, NULL, '03d7a418166fde16d7cc99be96e796a7', 'Lunas', 130000.00, NULL, NULL, NULL),
('TRX-46FD085C40EF27CD', '2026-11-22 23:50:38', 2, 45000.00, 'Transfer', NULL, NULL, NULL, '906f6e2b2451b6f285a394aafcfca473', 'Lunas', 45000.00, NULL, NULL, NULL),
('TRX-94E2BC9542E449E8', '2026-11-22 23:51:02', 2, 20000.00, 'Tunai', NULL, NULL, NULL, '3ad764620f0aad3ce88414ac04495fa9', 'Lunas', 50000.00, NULL, NULL, NULL),
('TRX-D2FD59B17BA7B596', '2026-11-18 12:07:30', 2, 115000.00, 'Tunai', NULL, 1, NULL, '54c856d4e34fb359ebb2ed8fa8f39d43', 'Lunas', 115000.00, NULL, NULL, NULL),
('TRX-DDF77E3F63DC7395', '2026-11-17 21:46:23', 2, 85000.00, 'QRIS', NULL, 1, NULL, '861d89d5d2abe244c65bc83554407aee', 'Lunas', 85000.00, NULL, NULL, NULL),
('TRX-E5FD6D657F088B04', '2026-11-22 23:25:29', 2, 15000.00, 'Tunai', NULL, NULL, NULL, '2075a924e70b299d91b7b9d51a83ff63', 'Lunas', 20000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `percobaan_login`
--

CREATE TABLE `percobaan_login` (
  `kunci` char(64) NOT NULL,
  `gagal` int(11) NOT NULL,
  `kedaluwarsa` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `servis`
--

CREATE TABLE `servis` (
  `id` int(11) NOT NULL,
  `kendaraan_id` int(11) NOT NULL,
  `mekanik_id` int(11) NOT NULL,
  `jasa_id` int(11) NOT NULL,
  `keluhan` varchar(500) NOT NULL,
  `catatan` varchar(1000) DEFAULT NULL,
  `dibuat` datetime NOT NULL DEFAULT current_timestamp(),
  `diperbarui` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('Menunggu','Dikerjakan','Selesai') NOT NULL DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servis`
--

INSERT INTO `servis` (`id`, `kendaraan_id`, `mekanik_id`, `jasa_id`, `keluhan`, `catatan`, `dibuat`, `diperbarui`, `status`) VALUES
(1, 1, 3, 1, 'Shock Keras', '', '2026-11-17 21:40:50', '2026-11-17 21:49:35', 'Dikerjakan'),
(2, 2, 3, 1, 'Gasnya Berat', 'selesai', '2026-11-19 17:06:45', '2026-11-22 21:47:02', 'Selesai');

-- --------------------------------------------------------

--
-- Table structure for table `servis_part`
--

CREATE TABLE `servis_part` (
  `servis_id` int(11) NOT NULL,
  `part_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL CHECK (`qty` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servis_part`
--

INSERT INTO `servis_part` (`servis_id`, `part_id`, `qty`) VALUES
(1, 2, 1),
(1, 6, 1),
(1, 7, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sparepart`
--

CREATE TABLE `sparepart` (
  `id_sparepart` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `harga_jual` decimal(12,2) NOT NULL CHECK (`harga_jual` >= 0),
  `stok` int(11) NOT NULL CHECK (`stok` >= 0),
  `stok_minimum` int(11) NOT NULL CHECK (`stok_minimum` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sparepart`
--

INSERT INTO `sparepart` (`id_sparepart`, `nama_barang`, `kategori`, `harga_jual`, `stok`, `stok_minimum`) VALUES
(1, 'Oli Mesin Federal Supreme', 'Sparepart', 45000.00, 22, 10),
(2, 'Kampas Rem Depan Beat', 'Sparepart', 35000.00, 14, 10),
(3, 'Busi NGK Standard', 'Sparepart', 20000.00, 28, 10),
(4, 'Ban Luar IRC 80/90-14', 'Sparepart', 160000.00, 10, 10),
(5, 'Filter Udara Vario 125', 'Sparepart', 40000.00, 10, 10),
(6, 'Jasa Servis Ringan / Tune Up', 'Jasa Servis', 35000.00, 0, 0),
(7, 'Jasa Ganti Oli & Filter', 'Jasa Servis', 15000.00, 0, 0),
(8, 'Bohlam Lampu Depan LED', 'Sparepart', 45000.00, 12, 10),
(9, 'Aki Kering GTZ5S', 'Sparepart', 195000.00, 9, 10),
(10, 'Kampas Rem Belakang', 'Sparepart', 30000.00, 12, 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_jasa`
--
ALTER TABLE `detail_jasa`
  ADD PRIMARY KEY (`no_transaksi`);

--
-- Indexes for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`no_pembelian`,`part_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `no_transaksi` (`no_transaksi`),
  ADD KEY `id_sparepart` (`id_sparepart`);

--
-- Indexes for table `jasa`
--
ALTER TABLE `jasa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pelat` (`pelat`),
  ADD KEY `pelanggan_id` (`pelanggan_id`);

--
-- Indexes for table `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `part_id` (`part_id`),
  ADD KEY `pengguna_id` (`pengguna_id`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pemasok`
--
ALTER TABLE `pemasok`
  ADD PRIMARY KEY (`id_pemasok`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`no_pembelian`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `id_pemasok` (`id_pemasok`),
  ADD KEY `id_pengguna` (`id_pengguna`),
  ADD KEY `lunas_oleh` (`lunas_oleh`),
  ADD KEY `pembelian_tanggal` (`tanggal_pembelian`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`no_transaksi`),
  ADD UNIQUE KEY `servis_id` (`servis_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `id_pengguna` (`id_pengguna`),
  ADD KEY `pelanggan_id` (`pelanggan_id`),
  ADD KEY `kendaraan_id` (`kendaraan_id`),
  ADD KEY `batal_oleh` (`batal_oleh`),
  ADD KEY `penjualan_tanggal` (`tanggal_transaksi`);

--
-- Indexes for table `percobaan_login`
--
ALTER TABLE `percobaan_login`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `servis`
--
ALTER TABLE `servis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kendaraan_id` (`kendaraan_id`),
  ADD KEY `jasa_id` (`jasa_id`),
  ADD KEY `tahap` (`mekanik_id`,`status`);

--
-- Indexes for table `servis_part`
--
ALTER TABLE `servis_part`
  ADD PRIMARY KEY (`servis_id`,`part_id`),
  ADD KEY `part_id` (`part_id`);

--
-- Indexes for table `sparepart`
--
ALTER TABLE `sparepart`
  ADD PRIMARY KEY (`id_sparepart`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `jasa`
--
ALTER TABLE `jasa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pemasok`
--
ALTER TABLE `pemasok`
  MODIFY `id_pemasok` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `servis`
--
ALTER TABLE `servis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sparepart`
--
ALTER TABLE `sparepart`
  MODIFY `id_sparepart` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_jasa`
--
ALTER TABLE `detail_jasa`
  ADD CONSTRAINT `detail_jasa_ibfk_1` FOREIGN KEY (`no_transaksi`) REFERENCES `penjualan` (`no_transaksi`);

--
-- Constraints for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `detail_pembelian_ibfk_1` FOREIGN KEY (`no_pembelian`) REFERENCES `pembelian` (`no_pembelian`),
  ADD CONSTRAINT `detail_pembelian_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `sparepart` (`id_sparepart`);

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`no_transaksi`) REFERENCES `penjualan` (`no_transaksi`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`id_sparepart`) REFERENCES `sparepart` (`id_sparepart`);

--
-- Constraints for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD CONSTRAINT `kendaraan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`);

--
-- Constraints for table `mutasi_stok`
--
ALTER TABLE `mutasi_stok`
  ADD CONSTRAINT `mutasi_stok_ibfk_1` FOREIGN KEY (`part_id`) REFERENCES `sparepart` (`id_sparepart`),
  ADD CONSTRAINT `mutasi_stok_ibfk_2` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `pembelian_ibfk_1` FOREIGN KEY (`id_pemasok`) REFERENCES `pemasok` (`id_pemasok`),
  ADD CONSTRAINT `pembelian_ibfk_2` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`),
  ADD CONSTRAINT `pembelian_ibfk_3` FOREIGN KEY (`lunas_oleh`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id_pengguna`),
  ADD CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`servis_id`) REFERENCES `servis` (`id`),
  ADD CONSTRAINT `penjualan_ibfk_3` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`),
  ADD CONSTRAINT `penjualan_ibfk_4` FOREIGN KEY (`kendaraan_id`) REFERENCES `kendaraan` (`id`),
  ADD CONSTRAINT `penjualan_ibfk_5` FOREIGN KEY (`batal_oleh`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Constraints for table `servis`
--
ALTER TABLE `servis`
  ADD CONSTRAINT `servis_ibfk_1` FOREIGN KEY (`kendaraan_id`) REFERENCES `kendaraan` (`id`),
  ADD CONSTRAINT `servis_ibfk_2` FOREIGN KEY (`mekanik_id`) REFERENCES `pengguna` (`id_pengguna`),
  ADD CONSTRAINT `servis_ibfk_3` FOREIGN KEY (`jasa_id`) REFERENCES `jasa` (`id`);

--
-- Constraints for table `servis_part`
--
ALTER TABLE `servis_part`
  ADD CONSTRAINT `servis_part_ibfk_1` FOREIGN KEY (`servis_id`) REFERENCES `servis` (`id`),
  ADD CONSTRAINT `servis_part_ibfk_2` FOREIGN KEY (`part_id`) REFERENCES `sparepart` (`id_sparepart`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
