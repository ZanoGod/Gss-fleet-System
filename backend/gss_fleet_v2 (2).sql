-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2026 at 09:23 AM
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
-- Database: `gss_fleet_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_links`
--

CREATE TABLE `admin_user_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `guest_company_name` varchar(255) NOT NULL,
  `car_id` int(10) UNSIGNED DEFAULT NULL,
  `custom_car_name` varchar(255) DEFAULT NULL,
  `secondary_car_id` int(10) UNSIGNED DEFAULT NULL,
  `driver_id` int(10) UNSIGNED DEFAULT NULL,
  `custom_driver_name` varchar(255) DEFAULT NULL,
  `operator_id` int(10) UNSIGNED DEFAULT NULL,
  `operator_name` varchar(150) NOT NULL,
  `even_odd` enum('Even','Odd') DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Confirm','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `guest_company_name`, `car_id`, `custom_car_name`, `secondary_car_id`, `driver_id`, `custom_driver_name`, `operator_id`, `operator_name`, `even_odd`, `start_date`, `end_date`, `status`, `remark`, `created_at`, `updated_at`) VALUES
(46, 'Mr.Fukuzawa Mr.okamoto Mr.Nakamura', 4, '', NULL, 5, '', 8, 'Hein Htet Zaw', '', '2026-06-03', '2026-06-04', 'Confirm', '', '2026-06-15 21:07:29', '2026-06-15 21:11:09'),
(47, 'Mr.Fukuzawa Mr.okamoto Mr.Nakamura', 4, '', NULL, 5, '', 8, 'Hein Htet Zaw', '', '2026-06-05', '2026-06-05', 'Confirm', '', '2026-06-15 21:10:57', '2026-06-15 21:10:57'),
(49, 'Sumitomo Electric /  Mr. Asai', 17, '', NULL, 16, '', 7, 'Thae Oo Min Eain', '', '2026-06-04', '2026-06-05', 'Confirm', '4L-5868(Alphard)   Ko Aung Kyaw Moe(09443079246)\r\n7H-5898(Alphard)   Ko Thar 09253387208', '2026-06-15 21:26:34', '2026-06-16 00:49:52'),
(50, 'Toyota Myanmar', 6, '', NULL, 1, '', 2, 'Nan Yee Mon Theint', '', '2026-06-02', '2026-06-03', 'Cancelled', '', '2026-06-15 21:28:08', '2026-06-15 21:28:08'),
(51, 'Toyota Myanmar', 6, '', NULL, 1, '', 2, 'Nan Yee Mon Theint', '', '2026-06-04', '2026-06-05', 'Cancelled', '', '2026-06-15 21:28:57', '2026-06-15 21:28:57'),
(52, 'Mr. Sato , Mr. Umesh', 1, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-02', '2026-06-03', 'Confirm', '', '2026-06-15 21:30:23', '2026-06-15 21:30:23'),
(53, 'Mr. Sato , Mr. Umesh', 1, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-04', '2026-06-05', 'Confirm', '', '2026-06-15 21:31:03', '2026-06-15 21:31:03'),
(54, 'Golden Dowa', 6, '', NULL, 14, '', 2, 'Nan Yee Mon Theint', '', '2026-06-07', '2026-06-07', 'Confirm', '', '2026-06-15 21:32:24', '2026-06-15 21:32:24'),
(55, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-12', '2026-06-13', 'Confirm', '', '2026-06-15 21:34:02', '2026-06-15 21:34:02'),
(56, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-14', '2026-06-15', 'Confirm', '', '2026-06-15 21:34:29', '2026-06-15 21:34:29'),
(57, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-16', '2026-06-17', 'Confirm', '', '2026-06-15 21:34:53', '2026-06-15 21:34:53'),
(58, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-18', '2026-06-19', 'Confirm', '', '2026-06-15 21:35:48', '2026-06-15 21:35:48'),
(59, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 9, 'Ngwe Sin Thar', '', '2026-06-20', '2026-06-21', 'Confirm', '', '2026-06-15 21:36:18', '2026-06-15 21:36:18'),
(60, 'Mr. Hidetoshi Fukui', 1, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-22', '2026-06-22', 'Confirm', '', '2026-06-15 21:37:00', '2026-06-15 21:37:00'),
(61, 'Mo Mo Finance', 2, '', NULL, 10, '', 2, 'Nan Yee Mon Theint', '', '2026-06-04', '2026-06-05', 'Cancelled', '', '2026-06-15 21:48:35', '2026-06-15 21:48:35'),
(62, 'Itsumiya', 6, '', NULL, 14, '', 16, 'Kaung Min Thant', '', '2026-06-05', '2026-06-05', 'Confirm', '', '2026-06-16 00:34:37', '2026-06-16 00:34:37'),
(63, 'SCSK Corporation Guest Name     : Mr. Yoichiro Iida', 6, '', NULL, 2, '', 11, 'Chu Saung Eain', '', '2026-06-08', '2026-06-09', 'Confirm', '', '2026-06-16 00:36:22', '2026-06-16 00:36:22'),
(64, 'SCSK Corporation Guest Name     : Mr. Yoichiro Iida', 6, '', NULL, 2, '', 11, 'Chu Saung Eain', '', '2026-06-10', '2026-06-11', 'Confirm', '', '2026-06-16 00:36:55', '2026-06-16 00:36:55'),
(65, 'Myanmar Legal', 18, '', NULL, 17, '', 2, 'Nan Yee Mon Theint', '', '2026-06-02', '2026-06-12', 'Confirm', '6E-7556(Alphard)\r\nU Thein Myint\r\n(09-5101632)', '2026-06-16 00:38:16', '2026-06-16 00:49:11'),
(66, 'Mother Brain Agency', 8, '', 9, 15, '', 4, 'Chaw Su May', '', '2026-06-01', '2026-06-02', 'Confirm', '', '2026-06-16 00:39:13', '2026-06-16 00:39:13'),
(67, 'Mother Brain Agency', 8, '', 9, 15, '', 4, 'Chaw Su May', '', '2026-06-03', '2026-06-04', 'Confirm', '', '2026-06-16 00:39:51', '2026-06-16 00:39:51'),
(68, 'Mother Brain Agency', 8, '', 9, 15, '', 4, 'Chaw Su May', '', '2026-06-05', '2026-06-05', 'Confirm', '', '2026-06-16 00:40:18', '2026-06-16 00:40:18'),
(69, 'Mother Brain Agency', 10, '', NULL, 8, '', 4, 'Chaw Su May', '', '2026-06-04', '2026-06-04', 'Cancelled', '', '2026-06-16 00:41:13', '2026-06-16 00:41:13'),
(70, 'JFE Steel/ Chiyoda San', 5, '', NULL, 22, '', 12, 'Khine Zar', NULL, '2026-06-23', '2026-06-23', 'Confirm', '', '2026-06-16 00:41:44', '2026-06-22 00:38:02'),
(71, 'Mr. Shuji MIWA', 19, '', NULL, 16, '', 16, 'Kaung Min Thant', 'Even', '2026-06-06', '2026-06-06', 'Confirm', 'Outside Car & Driver', '2026-06-16 00:42:56', '2026-06-17 22:29:35'),
(72, 'Mr. Masaki Horaguchi / Mitsubishi', 20, '', NULL, 20, '', 10, 'Htet Htet Hlaing', '', '2026-06-01', '2026-06-05', 'Confirm', '', '2026-06-16 01:01:07', '2026-06-16 01:01:07'),
(73, 'U Aung (Sayarsan REN)', 4, '', NULL, 8, '', 16, 'Kaung Min Thant', NULL, '2026-06-07', '2026-06-07', 'Confirm', '', '2026-06-16 01:02:13', '2026-06-17 22:24:48'),
(74, 'Mr. Hiromi Deguchi + 4 local', 17, '', NULL, 17, '', 17, 'Win Kyaw Aung', '', '2026-06-16', '2026-06-18', 'Confirm', 'June 16  (Yangon - Hpa-An)\r\nJune 17  (Hpa-An Area Usage)\r\nJune 18  (Hpa-An - Yangon)    / 5F/7337 (Alphard) / Ko Than Naing Soe', '2026-06-16 01:04:57', '2026-06-16 01:15:31'),
(76, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 3, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-16', '2026-06-17', 'Confirm', '', '2026-06-16 01:10:14', '2026-06-16 01:12:04'),
(77, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 3, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-18', '2026-06-19', 'Confirm', '', '2026-06-16 01:11:30', '2026-06-16 01:11:55'),
(78, 'Satoshi Watanabe', 3, '', NULL, 3, '', 8, 'Hein Htet Zaw', '', '2026-06-14', '2026-06-14', 'Confirm', '', '2026-06-16 01:14:18', '2026-06-16 01:14:18'),
(79, 'Konoike Construction', 6, '', NULL, 23, '', 8, 'Hein Htet Zaw', NULL, '2026-06-25', '2026-06-25', 'Confirm', '', '2026-06-16 01:16:39', '2026-06-23 00:37:20'),
(80, 'Toyota Myanmar', 5, '', NULL, 15, '', 7, 'Thae Oo Min Eain', '', '2026-06-15', '2026-06-16', 'Confirm', '', '2026-06-16 01:17:34', '2026-06-16 01:17:34'),
(81, 'Toyota Myanmar', 5, '', NULL, 15, '', 7, 'Thae Oo Min Eain', '', '2026-06-17', '2026-06-17', 'Confirm', '', '2026-06-16 01:18:04', '2026-06-16 01:18:04'),
(82, 'Mr. Marumo', 4, '', NULL, 2, '', 8, 'Hein Htet Zaw', '', '2026-06-23', '2026-06-23', 'Confirm', '', '2026-06-16 01:19:12', '2026-06-16 01:19:12'),
(83, 'Mr. Marumo', 4, '', NULL, 14, '', 8, 'Hein Htet Zaw', NULL, '2026-06-25', '2026-06-25', 'Confirm', '', '2026-06-16 01:20:06', '2026-06-23 00:13:27'),
(84, 'Mr. Yuji Kanashiki (License Academy)', 5, '', NULL, 24, '', 4, 'Chaw Su May', NULL, '2026-06-25', '2026-06-25', 'Confirm', '', '2026-06-16 01:20:34', '2026-06-23 00:36:38'),
(85, 'Mr. Kita', 21, '', NULL, 8, '', 9, 'Ngwe Sin Thar', '', '2026-06-13', '2026-06-14', 'Confirm', '', '2026-06-16 01:22:41', '2026-06-16 01:22:41'),
(86, 'Mr. Kanemura (Tenbells) 1 Pax', 21, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-06-25', '2026-06-26', 'Confirm', '', '2026-06-16 01:23:29', '2026-06-23 00:21:11'),
(87, 'Mr. Kanemura (Tenbells) 1 Pax', 6, '', NULL, 1, '', 4, 'Chaw Su May', '', '2026-06-27', '2026-06-27', 'Confirm', '', '2026-06-16 01:23:54', '2026-06-16 01:33:29'),
(88, 'Shonan’s Japanese customer', 9, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-06-25', '2026-06-26', 'Confirm', '', '2026-06-16 01:24:28', '2026-06-23 00:14:02'),
(89, 'Shonan’s Japanese customer', 9, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-06-27', '2026-06-28', 'Confirm', '', '2026-06-16 01:24:52', '2026-06-23 00:14:25'),
(90, 'Hitachi Limited  Mr. Yanagida & Mr. Andi', 22, '', NULL, 1, '', 10, 'Htet Htet Hlaing', '', '2026-06-20', '2026-06-20', 'Confirm', '', '2026-06-16 01:25:47', '2026-06-17 00:39:13'),
(98, 'SMBC Mr. Nakai Ms. Sabae Khine', 2, '', NULL, 21, '', 1, 'Ko Kaung Min Thant', NULL, '2026-06-22', '2026-06-22', 'Confirm', '22 Jun, RGN ~ NYT UB-103 06:15 ~ 07:10\r\n22 Jun, NYT ~ RGN UB-208 18:15 ~ 19:10\r\nFull Day', '2026-06-18 21:11:48', '2026-06-18 21:13:10'),
(99, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 30, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-07-07', '2026-07-08', 'Confirm', '07 Jul 2026 (TG 303) 18:00 Arrival Airport Pickup\r\n08 Jul 2026 Full Day Use\r\n09 Jul 2026 Full Day Use\r\n10 Jul 2026 (TG 304) 19:00 Airport Transfer', '2026-06-18 21:19:05', '2026-07-01 00:51:04'),
(100, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 30, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-07-09', '2026-07-10', 'Confirm', '07 Jul 2026 (TG 303) 18:00 Arrival Airport Pickup\r\n08 Jul 2026 Full Day Use\r\n09 Jul 2026 Full Day Use\r\n10 Jul 2026 (TG 304) 19:00 Airport Transfer', '2026-06-18 21:22:32', '2026-07-01 00:51:23'),
(101, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 13, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-07-28', '2026-07-29', 'Confirm', '28 Jul 2026 (TG 303) 18:00 Arrival Airport Pickup\r\n29 Jul 2026 Full Day Use\r\n30 Jul 2026 Full Day Use\r\n31 Jul 2026 (TG 304) 19:00 Airport Transfer', '2026-06-18 21:26:57', '2026-07-30 20:39:09'),
(102, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 13, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-07-30', '2026-07-31', 'Confirm', '28 Jul 2026 (TG 303) 18:00 Arrival Airport Pickup\r\n29 Jul 2026 Full Day Use\r\n30 Jul 2026 Full Day Use\r\n31 Jul 2026 (TG 304) 19:00 Airport Transfer', '2026-06-18 21:27:19', '2026-07-30 20:39:19'),
(103, 'Shonan’s Japanese customer', 9, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-06-24', '2026-06-24', 'Confirm', '', '2026-06-22 00:27:32', '2026-06-22 00:27:32'),
(104, 'Ms. Pan Dong Fang', 1, '', NULL, 1, '', 13, 'Zin Me Yu Lay', NULL, '2026-06-23', '2026-06-23', 'Confirm', '', '2026-06-22 00:40:21', '2026-06-22 00:40:21'),
(106, 'Ko Nyein Chan Aung', 22, '', NULL, 1, '', 2, 'Nan Yee Mon Theint', NULL, '2026-06-29', '2026-06-30', 'Confirm', '', '2026-06-23 00:24:26', '2026-06-24 00:51:29'),
(107, 'Ko Nyein Chan Aung', 22, '', NULL, 1, '', 2, 'Nan Yee Mon Theint', NULL, '2026-07-01', '2026-07-01', 'Confirm', '', '2026-06-23 00:25:42', '2026-06-24 00:52:28'),
(108, 'JFE Engineering', 13, '', NULL, 3, '', 16, 'Kaung Min Thant', NULL, '2026-07-03', '2026-07-04', 'Confirm', '', '2026-06-23 21:44:18', '2026-06-30 22:20:01'),
(109, 'JFE Engineering', 4, '', NULL, 3, '', 1, 'Ko Kaung Min Thant', NULL, '2026-07-05', '2026-07-06', 'Confirm', '', '2026-06-23 21:44:50', '2026-06-24 01:14:18'),
(110, 'JFE Engineering', 13, '', NULL, 3, '', 16, 'Kaung Min Thant', NULL, '2026-07-07', '2026-07-08', 'Confirm', '', '2026-06-23 21:45:29', '2026-06-30 22:16:48'),
(111, 'JFE Engineering', 24, '', NULL, 2, '', 1, 'Ko Kaung Min Thant', NULL, '2026-07-05', '2026-07-06', 'Confirm', '', '2026-06-23 21:49:04', '2026-06-24 01:16:27'),
(112, 'JFE Engineering', 24, '', NULL, 2, '', 1, 'Ko Kaung Min Thant', NULL, '2026-07-07', '2026-07-08', 'Confirm', '', '2026-06-23 21:50:37', '2026-06-24 01:16:52'),
(114, 'Chiyoda - Ma Yin Mon', 22, '', NULL, 22, '', 10, 'Htet Htet Hlaing', NULL, '2026-06-25', '2026-06-25', 'Confirm', 'Yangon - Naypyidaw - Yangon ( Day return )', '2026-06-24 00:24:23', '2026-06-24 00:27:00'),
(115, 'Mr.Motoi (M&N Fashion)', 30, '', NULL, 1, '', 12, 'Khine Zar', NULL, '2026-07-16', '2026-07-16', 'Confirm', 'Sedona Hotel > Suitstar Garment (Bago)', '2026-06-24 00:26:35', '2026-07-09 01:52:33'),
(116, 'Mr. Ito', 23, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-07-01', '2026-07-02', 'Confirm', 'JUL 1 → Full day (Airport transfer and meeting)\r\n\r\nJUL 2 → Full day (Meeting) \r\n\r\nJUL 3 → Half day / Morning (Airport transfer)', '2026-06-24 00:40:00', '2026-06-24 00:49:30'),
(117, 'Mr. Ito', 23, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-07-03', '2026-07-03', 'Confirm', 'JUL 1 → Full day (Airport transfer and meeting)\r\n\r\nJUL 2 → Full day (Meeting) \r\n\r\nJUL 3 → Half day / Morning (Airport transfer)', '2026-06-24 00:41:09', '2026-06-24 00:49:13'),
(118, 'JFE Engineering Kako Toshio(6, July ~ 9, July) ,  Miyoshi Manami(7, July ~ 10, July)', 23, '', NULL, 22, '', 1, 'Ko Kaung Min Thant', NULL, '2026-07-06', '2026-07-07', 'Confirm', '', '2026-06-26 00:45:42', '2026-06-30 21:49:52'),
(119, 'JFE Engineering  Kako Toshio(6, July ~ 9, July) ,  Miyoshi Manami(7, July ~ 10, July)', 23, '', NULL, 22, '', 16, 'Kaung Min Thant', NULL, '2026-07-08', '2026-07-09', 'Confirm', '', '2026-06-26 00:46:17', '2026-06-30 22:15:40'),
(120, 'JFE Engineering  Kako Toshio(6, July ~ 9, July) ,  Miyoshi Manami(7, July ~ 10, July)', 23, '', NULL, 22, '', 16, 'Kaung Min Thant', NULL, '2026-07-10', '2026-07-10', 'Confirm', '', '2026-06-26 00:49:35', '2026-06-30 21:51:12'),
(121, 'Mr. Atsushi Inoue / Myanmar Legal', 29, '', NULL, 25, '', 2, 'Nan Yee Mon Theint', NULL, '2026-07-08', '2026-07-09', 'Confirm', '6E/7556\r\nDriver. Ko Thein Nyunt\r\n095101632', '2026-06-28 21:02:31', '2026-06-30 22:51:14'),
(122, 'Mr. Atsushi Inoue / Myanmar Legal', 29, '', NULL, 25, '', 2, 'Nan Yee Mon Theint', NULL, '2026-07-10', '2026-07-11', 'Confirm', '6E/7556\r\nDriver. Ko Thein Nyunt\r\n095101632', '2026-06-28 21:02:57', '2026-06-30 22:51:06'),
(123, 'Mr. Atsushi Inoue / Myanmar Legal', 29, '', NULL, 25, '', 2, 'Nan Yee Mon Theint', NULL, '2026-07-12', '2026-07-12', 'Confirm', '6E/7556\r\nDriver. Ko Thein Nyunt\r\n095101632', '2026-06-28 21:03:33', '2026-06-30 22:51:00'),
(124, 'Brycen Myanmar Co.,ltd Mr. Takada.', 22, '', NULL, 1, '', 8, 'Hein Htet Zaw', NULL, '2026-07-12', '2026-07-13', 'Confirm', '', '2026-06-28 21:04:07', '2026-07-03 00:34:07'),
(125, 'Brycen Myanmar Co.,ltd Mr. Takada.', 22, '', NULL, 1, '', 8, 'Hein Htet Zaw', NULL, '2026-07-14', '2026-07-14', 'Confirm', '', '2026-06-28 21:04:42', '2026-07-01 00:49:35'),
(126, 'SMBC', 3, '', NULL, 15, '', 16, 'Kaung Min Thant', 'Even', '2026-07-08', '2026-07-08', 'Confirm', 'Airport Pick up + Full day use', '2026-07-06 04:34:57', '2026-07-06 04:36:10'),
(127, 'SMBC', 13, '', NULL, 3, '', 1, 'Ko Kaung Min Thant', 'Odd', '2026-07-09', '2026-07-09', 'Confirm', 'Full day use\r\nLotte to SMBC and Lotte', '2026-07-06 04:35:45', '2026-07-06 04:36:33'),
(128, 'SMBC', 3, '', NULL, 3, '', 16, 'Kaung Min Thant', 'Even', '2026-07-10', '2026-07-10', 'Confirm', 'Airport Transfer', '2026-07-06 04:37:11', '2026-07-06 04:37:11'),
(129, 'Mr. Ueki (Ren Group)', 30, '', NULL, 1, '', 16, 'Kaung Min Thant', NULL, '2026-07-17', '2026-07-17', 'Confirm', 'TG-301 10:35, Airport Pick Up', '2026-07-09 00:21:05', '2026-07-15 00:59:36'),
(130, 'Hitachi Limited', 30, '', NULL, 8, '', 10, 'Htet Htet Hlaing', NULL, '2026-07-12', '2026-07-12', 'Confirm', '4Pax, Pickup at Taungoo Pathi hotel to Yangon airport', '2026-07-09 01:43:10', '2026-07-09 01:43:10'),
(131, 'Mr. Kita', 21, '', NULL, 3, '', 9, 'Ngwe Sin Thar', NULL, '2026-07-11', '2026-07-12', 'Confirm', '', '2026-07-09 01:44:52', '2026-07-13 20:40:04'),
(132, 'SMBC', 13, '', NULL, 2, '', 16, 'Kaung Min Thant', NULL, '2026-07-10', '2026-07-11', 'Confirm', 'Yangon ~ Naypyitaw ~ Yangon', '2026-07-09 01:45:57', '2026-07-09 01:46:13'),
(134, 'Nikken International', 21, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-07-19', '2026-07-19', 'Confirm', 'July 19th (Sunday)\r\nWyndham Grand Hotel → Star City Golf Club → Restaurant → Wyndham Grand Hotel', '2026-07-13 20:35:44', '2026-07-20 20:35:31'),
(135, 'Fujita', 4, '', NULL, 26, '', 16, 'Kaung Min Thant', NULL, '2026-08-02', '2026-08-03', 'Confirm', '', '2026-07-13 22:44:43', '2026-07-30 00:50:37'),
(136, 'Fujita', 4, '', NULL, 26, '', 16, 'Kaung Min Thant', NULL, '2026-08-04', '2026-08-05', 'Confirm', '', '2026-07-13 22:45:20', '2026-07-30 00:50:57'),
(137, 'Fujita', 13, '', NULL, 27, '', 16, 'Kaung Min Thant', NULL, '2026-08-02', '2026-08-03', 'Confirm', '', '2026-07-13 22:46:17', '2026-07-30 00:50:12'),
(138, 'Fujita', 13, '', NULL, 27, '', 16, 'Kaung Min Thant', NULL, '2026-08-04', '2026-08-04', 'Confirm', '', '2026-07-13 22:46:46', '2026-07-30 00:49:53'),
(139, 'Mr. Yokota', 21, '', NULL, 2, '', 8, 'Hein Htet Zaw', NULL, '2026-07-16', '2026-07-17', 'Confirm', '', '2026-07-15 00:58:06', '2026-07-15 00:58:06'),
(140, 'Mr. Yokota', 21, '', NULL, 2, '', 8, 'Hein Htet Zaw', NULL, '2026-07-18', '2026-07-18', 'Confirm', '', '2026-07-15 00:58:36', '2026-07-15 00:58:36'),
(141, 'Mr. Kita', 14, '', NULL, 8, '', 9, 'Ngwe Sin Thar', NULL, '2026-07-18', '2026-07-19', 'Confirm', '', '2026-07-16 01:17:54', '2026-07-17 03:15:04'),
(142, 'Mr. Kosuge (1pax)', 23, '', NULL, 22, '', 10, 'Htet Htet Hlaing', NULL, '2026-07-19', '2026-07-19', 'Confirm', '', '2026-07-16 01:18:21', '2026-07-17 01:31:28'),
(143, 'Mother Brain', 4, '', NULL, 15, '', 4, 'Chaw Su May', NULL, '2026-07-17', '2026-07-17', 'Confirm', '', '2026-07-16 03:45:55', '2026-07-16 03:45:55'),
(144, 'Mr. Ichinoki (Toko Myanmar)', NULL, '', NULL, NULL, '', 8, 'Hein Htet Zaw', NULL, '2026-07-26', '2026-07-31', 'Confirm', 'Ko Yan Paing Oo\r\n3K/4384 (Alphard)', '2026-07-17 03:16:49', '2026-07-17 03:16:49'),
(145, 'New Jec', 4, '', NULL, 3, '', 11, 'Chu Saung Eain', NULL, '2026-07-26', '2026-07-27', 'Confirm', '', '2026-07-21 20:36:38', '2026-07-30 20:37:46'),
(146, 'New Jec', 4, '', NULL, 3, '', 11, 'Chu Saung Eain', NULL, '2026-07-28', '2026-07-29', 'Confirm', '', '2026-07-21 20:37:11', '2026-07-30 20:38:19'),
(147, 'New Jec', 4, '', NULL, 3, '', 11, 'Chu Saung Eain', NULL, '2026-07-30', '2026-07-31', 'Confirm', '', '2026-07-21 20:37:52', '2026-07-30 20:37:34'),
(148, 'New Jec', 4, '', NULL, 3, '', 11, 'Chu Saung Eain', NULL, '2026-08-01', '2026-08-01', 'Confirm', '', '2026-07-21 20:38:32', '2026-07-30 20:38:06'),
(149, 'License Academy School Fair Tour', 21, '', 22, 2, '', 4, 'Chaw Su May', NULL, '2026-07-29', '2026-07-30', 'Confirm', '', '2026-07-22 00:19:44', '2026-07-24 02:03:15'),
(150, 'License Academy School Fair Tour', 21, '', 22, 2, '', 4, 'Chaw Su May', NULL, '2026-07-31', '2026-08-01', 'Confirm', '', '2026-07-22 00:20:07', '2026-07-24 02:03:31'),
(152, 'License Academy School Fair Tour', 22, '', NULL, 2, '', 4, 'Chaw Su May', NULL, '2026-08-02', '2026-08-03', 'Confirm', '2K/6398', '2026-07-22 00:20:53', '2026-07-24 02:03:44'),
(153, 'Mr. Kita', 21, '', NULL, NULL, '', 9, 'Ngwe Sin Thar', NULL, '2026-07-25', '2026-07-26', 'Confirm', 'Ko Pyae Phyo Aung', '2026-07-23 02:53:55', '2026-07-23 20:57:09'),
(154, 'SMBC', 21, '', 23, 3, '', 16, 'Kaung Min Thant', NULL, '2026-08-03', '2026-08-04', 'Confirm', '', '2026-07-23 02:54:54', '2026-07-30 00:45:16'),
(155, 'SMBC', 21, '', 23, 3, '', 16, 'Kaung Min Thant', NULL, '2026-08-05', '2026-08-06', 'Confirm', '', '2026-07-23 02:55:25', '2026-07-30 00:46:03'),
(156, 'Mr. Morita & Mr. Inoue', 23, '', NULL, NULL, '', 12, 'Khine Zar', NULL, '2026-07-27', '2026-07-28', 'Confirm', 'Ko Thet Naung Naung', '2026-07-23 20:53:41', '2026-07-23 20:53:41'),
(157, 'Shonan’s Japanese', 9, '', NULL, 15, '', 12, 'Khine Zar', NULL, '2026-07-30', '2026-07-31', 'Confirm', '', '2026-07-24 02:02:35', '2026-07-30 20:38:56'),
(158, 'Shonan’s Japanese', 9, '', NULL, 15, '', 12, 'Khine Zar', NULL, '2026-08-01', '2026-08-01', 'Confirm', '', '2026-07-24 02:02:59', '2026-07-27 01:16:48'),
(159, 'Ohmidas Myanmar', 22, '', NULL, 1, '', 5, 'Nan Toe', NULL, '2026-08-05', '2026-08-06', 'Confirm', 'August 5 Airport pickup at 6pm\r\nAugust 6,7,8  Yangon area Full day use.', '2026-07-24 02:09:19', '2026-07-30 00:52:58'),
(160, 'Ohmidas Myanmar', 22, '', NULL, 1, '', 5, 'Nan Toe', NULL, '2026-08-07', '2026-08-08', 'Confirm', 'August 5 Airport pickup at 6pm\r\nAugust 6,7,8  Yangon area Full day use.', '2026-07-24 02:10:17', '2026-07-30 00:52:29'),
(161, 'Mr. Hideo Sumiyoshi', 21, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-09-04', '2026-09-04', 'Confirm', 'September 4th (Fri) Da Nang to Yangon, train 8M455, arriving at 21:30.\r\n\r\nSaloon \r\n\r\n\r\n• Yangon Airport → Phawt Kan City Mart → Marina Residence\r\nNumber of passengers: 2', '2026-07-30 20:43:28', '2026-08-30 22:24:33'),
(162, 'HIS', 30, '', NULL, 2, '', 13, 'Zin Me Yu Lay', NULL, '2026-09-09', '2026-09-10', 'Confirm', '', '2026-07-30 20:44:16', '2026-08-30 21:58:49'),
(163, 'HIS', 30, '', NULL, 2, '', 13, 'Zin Me Yu Lay', NULL, '2026-09-11', '2026-09-12', 'Confirm', '', '2026-07-30 20:44:45', '2026-08-30 21:58:31'),
(164, 'IOM Myanmar', NULL, '', NULL, NULL, '', 5, 'Nan Toe', NULL, '2026-08-02', '2026-08-03', 'Cancelled', '02Aug & 07 Aug, RGN-NPT-RGN (3~6Aug, NPT use) 08AugRGN airport transfer', '2026-07-30 20:59:02', '2026-07-30 22:49:08'),
(165, 'IOM Myanmar', NULL, '', NULL, NULL, '', 5, 'Nan Toe', NULL, '2026-08-04', '2026-08-05', 'Cancelled', '02Aug & 07 Aug, RGN-NPT-RGN (3~6Aug, NPT use) 08AugRGN airport transfer', '2026-07-30 20:59:23', '2026-07-30 22:49:45'),
(166, 'IOM Myanmar', NULL, '', NULL, NULL, '', 5, 'Nan Toe', NULL, '2026-08-06', '2026-08-07', 'Cancelled', '', '2026-07-30 20:59:59', '2026-07-30 22:49:28'),
(167, 'IOM Myanmar', NULL, '', NULL, NULL, '', 5, 'Nan Toe', NULL, '2026-08-08', '2026-08-08', 'Cancelled', '02Aug & 07 Aug, RGN-NPT-RGN (3~6Aug, NPT use) 08AugRGN airport transfer', '2026-07-30 21:00:44', '2026-07-30 22:49:17'),
(168, 'Mr Mukai.', 13, '', NULL, 3, '', 8, 'Hein Htet Zaw', NULL, '2026-08-09', '2026-08-10', 'Confirm', '', '2026-08-03 00:55:59', '2026-08-03 00:55:59'),
(169, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 30, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-08-18', '2026-08-19', 'Confirm', '18 Aug 2026 (TG 303) 18:00 Arrival Airport Pickup\r\n19 Aug 2026 Full Day Use\r\n20 Aug 2026 Full Day Use\r\n21 Aug 2026 (TG 304) 19:00 Airport Transfer', '2026-08-03 21:14:59', '2026-08-03 21:29:04'),
(170, 'Mr. Naka & Mr. Kubota (Tenbells) 2 Pax', 30, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-08-20', '2026-08-21', 'Confirm', '18 Aug 2026 (TG 303) 18:00 Arrival Airport Pickup\r\n19 Aug 2026 Full Day Use\r\n20 Aug 2026 Full Day Use\r\n21 Aug 2026 (TG 304) 19:00 Airport Transfer', '2026-08-03 21:15:23', '2026-08-03 21:29:24'),
(171, 'JFE Steel/ Chiyoda San', 21, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-08-10', '2026-08-11', 'Confirm', '', '2026-08-04 03:42:16', '2026-08-07 00:04:11'),
(172, 'JFE Steel/ Chiyoda San', 21, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-08-12', '2026-08-13', 'Confirm', '', '2026-08-04 03:42:34', '2026-08-07 00:04:35'),
(181, 'Izuyoshi(Ma Khine Su)', 30, '', NULL, 1, '', 12, 'Khine Zar', NULL, '2026-08-17', '2026-08-17', 'Confirm', '', '2026-08-05 00:54:29', '2026-08-13 01:14:29'),
(182, 'Tenbells', 11, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-09-08', '2026-09-09', 'Confirm', '', '2026-08-05 02:49:57', '2026-09-02 20:20:18'),
(183, 'Tenbells', 11, '', NULL, NULL, '', 4, 'Chaw Su May', NULL, '2026-09-10', '2026-09-11', 'Confirm', '', '2026-08-05 02:50:20', '2026-09-02 20:21:05'),
(184, 'Mr. Kita', 21, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-08', '2026-08-09', 'Confirm', '', '2026-08-06 01:03:45', '2026-08-07 00:04:52'),
(185, 'Mr. Kanemura (1 Pax)', 21, '', NULL, 2, '', 4, 'Chaw Su May', NULL, '2026-08-19', '2026-08-19', 'Confirm', '~19 Aug 2026 (TG 303) 18:00 Arrival Airport Pickup ~ Wishton Hotel\r\n~22 Aug 2026 (TG 304) 19:00 Airport Transfer from Wishton Hotel', '2026-08-10 02:09:56', '2026-08-18 21:04:21'),
(186, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-14', '2026-08-15', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 22th-Aug.', '2026-08-10 02:30:27', '2026-08-20 21:54:10'),
(187, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-18', '2026-08-19', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 29th-Aug.', '2026-08-10 02:31:12', '2026-08-10 02:34:46'),
(188, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-20', '2026-08-21', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 29th-Aug.', '2026-08-10 02:32:31', '2026-08-10 02:34:40'),
(189, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-22', '2026-08-22', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 22th-Aug.', '2026-08-10 02:32:54', '2026-08-20 21:53:53'),
(190, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-24', '2026-08-25', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 29th-Aug.', '2026-08-10 02:33:18', '2026-08-10 02:34:28'),
(191, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-26', '2026-08-27', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 29th-Aug.', '2026-08-10 02:33:47', '2026-08-10 02:34:22'),
(192, 'JFE Engineering', 23, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-28', '2026-08-29', 'Confirm', '14th-Aug.(evening), 15th-Aug., 18th-Aug. to 29th-Aug.', '2026-08-10 02:34:07', '2026-08-10 02:34:17'),
(193, 'Mr. Yasuhara Family', 13, '', NULL, 22, '', 13, 'Zin Me Yu Lay', NULL, '2026-08-21', '2026-08-22', 'Confirm', '21.08.2026 ETA 22:25 Airport Pickup to Golden Hill Tower. \r\n22.08.2026 Half day from afternoon.\r\n23.08.2026 Will confirm later.', '2026-08-12 00:36:25', '2026-08-12 00:36:43'),
(194, 'Mr. Yasuhara Family', 13, '', NULL, 22, '', 13, 'Zin Me Yu Lay', NULL, '2026-08-23', '2026-08-23', 'Confirm', '21.08.2026 ETA 22:25 Airport Pickup to Golden Hill Tower. \r\n22.08.2026 Half day from afternoon.\r\n23.08.2026 Will confirm later.', '2026-08-12 00:37:11', '2026-08-12 00:37:11'),
(195, 'Mr. Masanori Mizuno', NULL, '', NULL, NULL, '', 8, 'Hein Htet Zaw', NULL, '2026-10-09', '2026-10-10', 'Pending', '9.10.2026 Airport pick up and sight seeing\r\n\r\n10.10.2026 Bago Day Return \r\n\r\n11.10.2026 Transfer', '2026-08-13 01:17:02', '2026-08-14 04:02:29'),
(196, 'Mr. Masanori Mizuno', NULL, '', NULL, NULL, '', 8, 'Hein Htet Zaw', NULL, '2026-10-11', '2026-10-11', 'Pending', '9.10.2026 Airport pick up and sight seeing\r\n\r\n10.10.2026 Bago Day Return \r\n\r\n11.10.2026 Transfer', '2026-08-13 01:17:37', '2026-08-14 04:02:15'),
(197, 'Mitsubishi Corporation guest', 14, '', NULL, 1, '', 10, 'Htet Htet Hlaing', NULL, '2026-08-24', '2026-08-25', 'Confirm', '', '2026-08-13 02:37:49', '2026-08-14 03:25:00'),
(198, 'Mitsubishi Corporation guest', 14, '', NULL, 1, '', 10, 'Htet Htet Hlaing', NULL, '2026-08-26', '2026-08-27', 'Confirm', '', '2026-08-13 02:38:36', '2026-08-14 03:25:23'),
(199, 'Mitsubishi Corporation guest', 14, '', NULL, 1, '', 10, 'Htet Htet Hlaing', NULL, '2026-08-28', '2026-08-28', 'Confirm', '', '2026-08-13 02:39:09', '2026-08-14 03:25:46'),
(200, 'Mr. Aoyama', 11, '', NULL, 22, '', 8, 'Hein Htet Zaw', NULL, '2026-09-17', '2026-09-21', 'Confirm', 'September 17: Arrival at 18:00 on Thai Airways flight TG0303 at Yangon International Airport →  Wishton Hotel. \r\n\r\nSeptember 21: Depart from Wishton Hotel at 06:30 → Transportation to IIDA ELECTRONICS.\r\nOn this day, the customer will return from IIDA ELECTRONICS using IIDA\'s vehicle. \r\n\r\nSeptember 23: Depart from Wishton Hotel at 12:00 → Transportation to IIDA ELECTRONICS.\r\nPlease have the driver wait for approximately 4 hours. \r\nDepart from IIDA ELECTRONICS at 16:00 → Transportation to Yangon International Airport.\r\nEstimated arrival at the airport: 17:00. (Request 4)', '2026-08-17 02:33:51', '2026-09-13 22:11:37'),
(201, 'Mr. Aoyama', 11, '', NULL, 22, '', 8, 'Hein Htet Zaw', NULL, '2026-09-23', '2026-09-23', 'Confirm', 'September 17: Arrival at 18:00 on Thai Airways flight TG0303 at Yangon International Airport →  Wishton Hotel. \r\n\r\nSeptember 21: Depart from Wishton Hotel at 06:30 → Transportation to IIDA ELECTRONICS.\r\nOn this day, the customer will return from IIDA ELECTRONICS using IIDA\'s vehicle. \r\n\r\nSeptember 23: Depart from Wishton Hotel at 12:00 → Transportation to IIDA ELECTRONICS.\r\nPlease have the driver wait for approximately 4 hours. \r\nDepart from IIDA ELECTRONICS at 16:00 → Transportation to Yangon International Airport.\r\nEstimated arrival at the airport: 17:00. (Request 4)', '2026-08-17 02:34:33', '2026-09-13 22:11:08'),
(202, 'Mr. Kita', 21, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-15', '2026-08-16', 'Confirm', '', '2026-08-18 01:07:31', '2026-08-18 01:07:31'),
(203, 'Taisei Construction', NULL, '', NULL, NULL, '', 7, 'Thae Oo Min Eain', NULL, '2026-08-15', '2026-08-17', 'Confirm', '5E/9411 (Car Only)', '2026-08-18 01:08:28', '2026-08-18 01:08:28'),
(204, 'Keisoku Giken Myanmar', 6, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-27', '2026-08-29', 'Confirm', 'August 27: 17:00–22:00\r\n- August 29: 08:00–13:00\r\n- August 30: 11:00–16:00\r\n- August 31: 17:00–22:00\r\n- September 1: 17:00–22:00', '2026-08-18 02:31:45', '2026-08-18 22:22:06'),
(205, 'Keisoku Giken Myanmar', 6, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-30', '2026-08-31', 'Confirm', 'August 27: 17:00–22:00\r\n- August 29: 08:00–13:00\r\n- August 30: 11:00–16:00\r\n- August 31: 17:00–22:00\r\n- September 1: 17:00–22:00', '2026-08-18 02:32:25', '2026-08-18 22:22:39'),
(206, 'Keisoku Giken Myanmar', 6, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-09-01', '2026-09-01', 'Confirm', 'August 27: 17:00–22:00\r\n- August 29: 08:00–13:00\r\n- August 30: 11:00–16:00\r\n- August 31: 17:00–22:00\r\n- September 1: 17:00–22:00', '2026-08-18 02:33:06', '2026-08-18 22:23:12'),
(207, 'Mr. Atsushi Inoue', 4, '', NULL, 22, '', 12, 'Khine Zar', 'Odd', '2026-09-06', '2026-09-07', 'Confirm', '', '2026-08-18 03:03:43', '2026-08-30 21:51:26'),
(208, 'Mr. Atsushi Inoue', 4, '', NULL, 22, '', 12, 'Khine Zar', 'Odd', '2026-09-08', '2026-09-09', 'Confirm', '', '2026-08-18 03:05:06', '2026-08-30 21:51:56'),
(209, 'Mr. Atsushi Inoue', 4, '', NULL, 22, '', 12, 'Khine Zar', 'Odd', '2026-09-10', '2026-09-11', 'Confirm', '', '2026-08-18 03:05:26', '2026-08-30 21:52:31'),
(210, 'Mr. Atsushi Inoue', 4, '', NULL, 22, '', 12, 'Khine Zar', 'Odd', '2026-09-12', '2026-09-13', 'Confirm', '', '2026-08-18 03:05:45', '2026-08-30 21:52:58'),
(211, 'Mr. Kanemura  1 Pax', 21, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-08-22', '2026-08-22', 'Confirm', '~19 Aug 2026 (TG 303) 18:00 Arrival Airport Pickup ~ Wishton Hotel\r\n~22 Aug 2026 (TG 304) 19:00 Airport Transfer from Wishton Hotel', '2026-08-18 21:05:40', '2026-08-18 21:05:40'),
(212, 'Mr. Fukui', 4, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-20', '2026-08-24', 'Confirm', 'From the evening of August 20th to the morning of August 24th\r\n\r\nArrival: August 20th, 18:00, Arriving in Yangon, Flight TG303\r\n\r\nDeparture: August 24th, 11:25, Departing from Yangon, Flight TG302', '2026-08-18 21:06:29', '2026-08-18 21:06:29'),
(213, 'Mr. Kyoko Takahashi', 23, '', NULL, 29, '', 9, 'Ngwe Sin Thar', NULL, '2026-09-11', '2026-09-11', 'Confirm', 'Date and Time: September 11th (Fri), approximately 9:00 AM to 6:00 PM\r\nDeparture from Pan Pacific Hotel ➔ Yangon Japanese School (round trip) ➔ Sightseeing near Shwedagon Pagoda ➔ Arrival at Pan Pacific Hotel', '2026-08-18 22:40:57', '2026-09-08 01:05:12'),
(214, 'Mr. Shirakawa Kazuki', NULL, '', NULL, NULL, '', 8, 'Hein Htet Zaw', NULL, '2026-10-01', '2026-10-03', 'Pending', 'Type - Saloon \r\nOctober 1, Half day\r\nPickup 　　Yangon airport international and Yangon Area Visit\r\n\r\nOctober 2, 3 Full Day \r\nYangon Area Visit', '2026-08-19 01:33:33', '2026-08-19 01:33:33'),
(215, 'Mr. Kita', NULL, '', NULL, 8, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-22', '2026-08-23', 'Confirm', 'August 22 - 6:10 AM\r\nAugust 23 - 6:10 AM\r\n2K/6398', '2026-08-20 20:52:16', '2026-08-23 22:27:07'),
(216, 'Tenbells', 2, '', NULL, 1, '', 4, 'Chaw Su May', NULL, '2026-09-29', '2026-10-02', 'Confirm', '', '2026-08-23 22:48:02', '2026-09-11 02:59:03'),
(217, 'Tenbells', NULL, '', NULL, NULL, '', 4, 'Chaw Su May', NULL, '2026-10-20', '2026-10-23', 'Pending', '', '2026-08-23 22:48:45', '2026-08-23 22:48:45'),
(218, 'Mr. Kita', 21, '', NULL, 22, '', 9, 'Ngwe Sin Thar', NULL, '2026-08-29', '2026-08-30', 'Confirm', '', '2026-08-26 02:17:18', '2026-08-27 03:58:49'),
(219, 'Mr. Okinawa', 38, '', NULL, 21, '', 12, 'Khine Zar', NULL, '2026-09-17', '2026-09-17', 'Confirm', 'ခရီးသည်အရေအတွက် – ၆ ဦး\r\nသွားမည့်နေရာ – Pyawbwe\r\nခရီးအကွာအဝေး – ခန့်မှန်းခြေ ၂၀၀ km\r\nခရီးစဉ်ပြီးဆုံးမည့်နေရာ – Nay Pyi Taw Airport (ညနေ ၄:၃၀ နာရီ)\r\nလိုချင်သောကားအမျိုးအစား – ALPHARD TYPE', '2026-08-26 21:37:43', '2026-09-02 20:25:45'),
(220, 'Ms. Kana Manabe / Myanmar Legal', 13, '', NULL, 3, '', 12, 'Khine Zar', 'Odd', '2026-09-08', '2026-09-22', 'Confirm', '', '2026-08-30 22:00:34', '2026-08-30 22:00:34'),
(221, 'Ohmidas Myanmar Co., Ltd.', 9, '', NULL, 1, '', 12, 'Khine Zar', NULL, '2026-09-15', '2026-09-18', 'Confirm', '', '2026-09-01 04:10:17', '2026-09-11 03:06:51'),
(222, 'Mitsubishi Electric (Ko Nyein Kyaw Thu )', 39, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-09-07', '2026-09-08', 'Confirm', '', '2026-09-02 03:41:19', '2026-09-03 03:00:50'),
(224, 'Ohmidas Myanmar Co., Ltd.', 30, '', NULL, NULL, '', 12, 'Khine Zar', NULL, '2026-09-07', '2026-09-08', 'Confirm', 'စက်တင်ဘာလ ၇ ရက်နေ့ – Airport Transfer\r\n\r\nစက်တင်ဘာလ ၈ ရက်နေ့ – Full Day + Transfer\r\n\r\n Ko Myo Thet Tun', '2026-09-03 03:54:30', '2026-09-03 21:20:59'),
(225, 'Mr .Kita', 21, '', NULL, 2, '', 9, 'Ngwe Sin Thar', NULL, '2026-09-05', '2026-09-06', 'Confirm', '', '2026-09-03 21:19:31', '2026-09-03 21:19:31'),
(226, '-----------', 2, '', NULL, 29, '', 13, 'Zin Me Yu Lay', NULL, '2026-09-09', '2026-09-09', 'Confirm', 'Arrival September 9 (Wednesday) , TG301, 10:35 AM\r\n\r\nDeparture: September 13  (Sunday) , TG302, departing at 11:25 AM\r\n\r\nAlphard for transfer only please (Airport and Lotte Hotel)', '2026-09-08 00:58:48', '2026-09-08 01:02:53'),
(229, 'Mr.Yohei OSAKADA', 23, '', NULL, 29, '', 7, 'Thae Oo Min Eain', NULL, '2026-09-12', '2026-09-26', 'Confirm', 'Hotel : Park Royal Hotel\r\nDestination : Hotel - J&M Steel Solutions - others', '2026-09-11 00:37:36', '2026-09-11 02:09:33'),
(230, 'Mr. Kita', 21, '', NULL, 8, '', 9, 'Ngwe Sin Thar', NULL, '2026-09-12', '2026-09-13', 'Confirm', '', '2026-09-11 00:39:07', '2026-09-11 00:39:07'),
(235, 'Mr. Hiromi DEGUCHI + 3 Local pax', 4, '', NULL, 1, '', 17, 'Win Kyaw Aung', NULL, '2026-09-21', '2026-09-22', 'Confirm', '1 Night - 2 Days Plan\r\nSeptember 21 ( Yangon - Ngwe Saung )\r\nSeptember 22 ( Ngwe Saung - Yangon )', '2026-09-11 03:18:43', '2026-09-13 22:09:45'),
(236, 'SCSK', 39, '', NULL, 2, '', 11, 'Chu Saung Eain', NULL, '2026-09-17', '2026-09-24', 'Confirm', 'Arrival                 : 17th September 2026, Thursday\r\n\r\nDeparture          : 24th September 2026, Thursday\r\n\r\nBooking              : Full Day and 1 transfer on 24th September 2026', '2026-09-11 03:20:54', '2026-09-11 03:20:54'),
(239, 'Izoyashi', 30, '', NULL, 22, '', 12, 'Khine Zar', NULL, '2026-09-15', '2026-09-15', 'Confirm', '', '2026-09-13 22:13:31', '2026-09-13 22:13:31'),
(240, 'U Aung (Sayarsan REN)', NULL, '', 4, 1, '', 17, 'Win Kyaw Aung', NULL, '2026-09-19', '2026-09-19', 'Confirm', '', '2026-09-13 23:00:37', '2026-09-17 22:23:00'),
(241, 'shonan', 2, '', NULL, 30, '', 12, 'Khine Zar', NULL, '2026-09-22', '2026-09-25', 'Confirm', '', '2026-10-13 21:03:37', '2026-10-13 21:03:37'),
(242, 'Ms. Pan Dong Fang', 30, '', NULL, 31, '', 13, 'Zin Me Yu Lay', NULL, '2026-09-21', '2026-09-21', 'Confirm', '', '2026-10-13 21:12:17', '2026-10-13 21:12:17'),
(243, 'Ms. Pan Dong Fang', 9, '', NULL, 31, '', 13, 'Zin Me Yu Lay', NULL, '2026-09-22', '2026-09-22', 'Confirm', '', '2026-10-13 21:13:39', '2026-10-13 21:13:39'),
(244, 'Myanmar Legal', 40, '', NULL, 31, '', 12, 'Khine Zar', NULL, '2026-09-25', '2026-09-25', 'Confirm', '', '2026-10-13 21:24:53', '2026-10-13 21:24:53'),
(245, 'Masanori Mizuno', 40, '', NULL, 3, '', 8, 'Hein Htet Zaw', NULL, '2026-10-09', '2026-10-11', 'Confirm', '', '2026-10-13 21:35:19', '2026-10-13 21:35:19'),
(246, 'Shirakawa Kazuki', 43, '', NULL, 31, '', 8, 'Hein Htet Zaw', NULL, '2026-10-01', '2026-10-03', 'Confirm', '', '2026-10-13 21:39:11', '2026-10-13 21:39:11'),
(247, 'JAMAHA NGO, MR, ZAW MIN', 6, '', NULL, 2, '', 8, 'Hein Htet Zaw', NULL, '2026-10-01', '2026-10-03', 'Confirm', '', '2026-10-13 21:43:56', '2026-10-13 21:43:56'),
(248, 'Mr. Kita', 40, '', NULL, NULL, '', 9, 'Ngwe Sin Thar', NULL, '2026-10-03', '2026-10-04', 'Confirm', '', '2026-10-13 21:49:31', '2026-10-13 21:49:31'),
(250, 'Tenbells( Mr.Shigekazu Kanemura)', 30, '', NULL, 1, '', 8, 'Hein Htet Zaw', NULL, '2026-10-19', '2026-10-22', 'Confirm', '', '2026-11-02 20:37:51', '2026-11-02 20:37:51'),
(251, 'JFE Engineering', 39, '', NULL, 3, '', 7, 'Thae Oo Min Eain', NULL, '2026-11-27', '2026-11-30', 'Confirm', '', '2026-11-02 20:38:41', '2026-11-02 20:38:41'),
(252, 'Matsunaga Myanmar Co.Ltd (Mr.Hibino Tada)', 3, '', NULL, 8, '', 9, 'Ngwe Sin Thar', NULL, '2026-10-21', '2026-10-21', 'Confirm', '', '2026-11-02 20:39:38', '2026-11-02 20:41:16'),
(253, 'Matsunaga Myanmar Co.Ltd (Mr.Hibino Tada)', 3, '', NULL, 8, '', 9, 'Ngwe Sin Thar', NULL, '2026-10-23', '2026-10-23', 'Confirm', '', '2026-11-02 20:40:32', '2026-11-02 20:40:32'),
(254, 'Fujita Coporation', NULL, '', NULL, 2, '', 2, 'Nan Yee Mon Theint', NULL, '2026-10-20', '2026-10-23', 'Confirm', '9C/5997 (Mark II)', '2026-11-02 20:42:41', '2026-11-02 20:42:41'),
(255, 'MJTD', 39, '', NULL, 31, '', 10, 'Htet Htet Hlaing', NULL, '2026-10-19', '2026-10-21', 'Confirm', '', '2026-11-02 20:44:06', '2026-11-02 20:44:06'),
(256, 'Mr.Toukai Rin', 43, '', NULL, 5, '', 13, 'Zin Me Yu Lay', NULL, '2026-10-19', '2026-10-19', 'Confirm', '', '2026-11-02 20:45:01', '2026-11-02 20:45:01'),
(257, 'Mr.Toukai Rin', 43, '', NULL, 5, '', 13, 'Zin Me Yu Lay', NULL, '2026-10-21', '2026-10-21', 'Confirm', '', '2026-11-02 20:45:41', '2026-11-02 20:45:41'),
(258, 'Mr. Endo', 22, '', NULL, 5, '', 7, 'Thae Oo Min Eain', NULL, '2026-10-20', '2026-10-20', 'Confirm', '', '2026-11-02 20:46:54', '2026-11-02 20:46:54'),
(259, 'Mr.Kinoshita', 29, '', NULL, 25, '', 5, 'Nan Toe', NULL, '2026-10-19', '2026-10-22', 'Confirm', '5F/7337 / Mr. Than Naing Soe', '2026-11-02 20:48:09', '2026-11-02 20:48:09'),
(260, 'Mr. Kita', 29, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-10-17', '2026-10-18', 'Confirm', '9C/5997 (Mark II)', '2026-11-02 20:50:41', '2026-11-02 20:50:41'),
(261, 'Mr. Kubota', 43, '', NULL, NULL, '', 8, 'Hein Htet Zaw', NULL, '2026-10-20', '2026-10-20', 'Confirm', 'Ye Win Tun', '2026-11-02 20:53:43', '2026-11-02 20:53:43'),
(262, 'Mr. Kubota', 30, '', NULL, 1, '', 8, 'Hein Htet Zaw', NULL, '2026-10-23', '2026-10-23', 'Confirm', '', '2026-11-02 20:54:22', '2026-11-02 20:54:22'),
(263, 'Mr.Minoru Utsunomiya', 3, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-10-24', '2026-10-24', 'Confirm', '', '2026-11-02 20:55:09', '2026-11-02 20:55:09'),
(264, 'Mr. Kita', 43, '', NULL, 3, '', 5, 'Nan Toe', NULL, '2026-10-24', '2026-10-25', 'Confirm', '', '2026-11-02 20:56:16', '2026-11-02 20:56:16'),
(265, '3.Mr.Yoshiki YAMAMOTO   4.Mr. Hirofumi HAMAYOTSU 1. Mr.Koichi SANO  2. Mr.Shingo IWAHIRO', NULL, '', NULL, NULL, '', 7, 'Thae Oo Min Eain', NULL, '2026-10-27', '2026-10-30', 'Confirm', '9C/5997\r\nKo Chan Myae Win Htut', '2026-11-02 20:57:36', '2026-11-02 20:57:36'),
(266, 'Mr. Kazuisa', NULL, '', NULL, NULL, '', 5, 'Nan Toe', NULL, '2026-10-29', '2026-10-29', 'Confirm', '9C/5997\r\nKo Chan Myae Win Htut', '2026-11-02 20:58:30', '2026-11-02 20:58:30'),
(267, 'Mr.TAKADA(Brycen)', NULL, '', NULL, NULL, '', 8, 'Hein Htet Zaw', NULL, '2026-10-30', '2026-11-01', 'Confirm', '3P/6277 (Hi Ace) 14 Seaters\r\nKo Ye Min Zaw', '2026-11-02 21:00:11', '2026-11-02 21:00:11'),
(268, 'Mr. Kita', NULL, '', NULL, 2, '', 12, 'Khine Zar', NULL, '2026-10-31', '2026-10-31', 'Confirm', '9C/9557 (Mark II)', '2026-11-02 21:01:22', '2026-11-02 21:01:22'),
(269, 'Mr.Kobayashi', NULL, '', NULL, NULL, '', 12, 'Khine Zar', NULL, '2026-11-13', '2026-11-14', 'Pending', '', '2026-11-03 01:03:16', '2026-11-03 01:03:16');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1793349623),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1793349623;', 1793349623),
('laravel-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0', 'i:1;', 1793245339),
('laravel-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0:timer', 'i:1793245339;', 1793245339);

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(10) UNSIGNED NOT NULL,
  `car_type` varchar(100) NOT NULL,
  `plate_no` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `seat_capacity` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `availability_status` enum('Available','Assigned','Maintenance') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `car_type`, `plate_no`, `model_name`, `seat_capacity`, `availability_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Alphard', '2G/4165', '2002', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(2, 'Alphard', '2G/5649', '2002', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(3, 'Alphard', '4H/2590', '2003', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(4, 'Alphard', '3J/4760', '2005', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(5, 'Prado', '3M/8296', '2006', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(6, 'Prado', '7E/7850', '1999', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(7, 'Hi Ace 9 Seater', '1N/5660', '2007', 9, 'Available', 'Naypyitaw Office', '2026-06-04 03:47:59', '2026-11-09 04:19:05'),
(8, 'Hi Ace 9 Seater', '6N/4680', '2008', 9, 'Available', NULL, '2026-06-04 03:47:59', '2026-11-09 04:18:55'),
(9, 'Hi Ace 14 Seater', '7K/5572', '2005', 14, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(10, 'Ertiga Type II', '4N/4401', '2017', 7, 'Available', NULL, '2026-06-04 03:47:59', '2026-11-09 04:18:44'),
(11, 'Ertiga Type II', '7P/8951', '2017', 7, 'Available', 'Thanlyin Refinery 01 Jun', '2026-06-04 03:47:59', '2026-09-02 20:19:48'),
(12, 'Wish', '5I/2273', '2003', 7, 'Available', 'JFE Engineering 05-Jun / Nikken 08-Jun', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(13, 'Alphard', '9L/1268', '2006', 6, 'Available', 'available on 21.06.26', '2026-06-04 03:47:59', '2026-06-26 02:07:57'),
(14, 'Zinger', '6E/4489', '2012', 7, 'Available', '', '2026-06-04 03:47:59', '2026-06-04 03:47:59'),
(15, 'Mirage', '9N/3501', '2016', 4, 'Available', NULL, '2026-06-04 03:47:59', '2026-11-09 04:18:37'),
(16, 'Outside Car (Ko Than Naing Soe)', '5F/7337', '-', 4, 'Available', '', '2026-06-16 00:43:56', '2026-06-16 00:45:46'),
(17, 'Outside Car ( Myo Myanmar)', '1', '-', 4, 'Available', '', '2026-06-16 00:47:16', '2026-06-16 00:58:00'),
(18, 'Outside Car (Myo Myanmar)', '2', '-', 4, 'Available', '', '2026-06-16 00:48:54', '2026-06-16 00:57:46'),
(19, 'Outside Car', 'Nay Pyi Taw', '-', 4, 'Available', '', '2026-06-16 00:52:52', '2026-06-16 00:52:52'),
(20, 'Outside Car (Myo Myanmar)', '3', '-', 4, 'Available', '', '2026-06-16 00:59:09', '2026-06-16 00:59:35'),
(21, 'Toyota Allion', '2K-6398', '2010', 4, 'Available', '', '2026-06-16 01:22:13', '2026-06-16 01:22:13'),
(22, 'HiAce 9 seaters', '3P/1031', '2008', 9, 'Available', '', '2026-06-17 00:38:50', '2026-06-17 00:38:50'),
(23, 'Ertiga Type III', '9Q/6057', '2019', 4, 'Available', '', '2026-06-24 00:48:52', '2026-06-24 00:48:52'),
(24, 'Alphard', '2D/5467', '2003', 4, 'Available', '', '2026-06-24 01:15:53', '2026-06-24 01:15:53'),
(29, 'Outside Car', '-', '-', 4, 'Available', '', '2026-06-30 22:48:42', '2026-06-30 22:48:42'),
(30, 'Alphard', '2E/4515', '2002', 4, 'Available', '', '2026-07-01 00:48:40', '2026-07-01 00:48:40'),
(38, 'Alphard', '5E/9411', '2002', 4, 'Available', '', '2026-09-02 20:25:02', '2026-09-02 20:25:02'),
(39, 'Alphard', '3K/4384', '2003', 6, 'Available', '', '2026-09-03 03:00:08', '2026-09-03 03:00:08'),
(40, 'Allion', '2K/6398', '2010', 4, 'Available', '', '2026-10-13 21:23:59', '2026-10-13 21:23:59'),
(43, 'Attrage', '1Q/4147', '2018', 4, 'Available', '', '2026-10-13 21:38:28', '2026-10-13 21:38:28');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `off_day` varchar(50) DEFAULT NULL,
  `service_hour` varchar(100) DEFAULT NULL,
  `customer_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `driver_status` enum('Available','Assigned','Leave','Inactive') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `full_name`, `phone_number`, `license_no`, `driver_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Ko Myo Thet Tun', '09-777714566', NULL, 'Available', NULL, '2026-06-04 03:55:14', '2026-06-04 03:55:14'),
(2, 'Ko Han Win Aung', '09-420177161', NULL, 'Available', NULL, '2026-06-04 03:55:14', '2026-06-04 03:55:14'),
(3, 'Ko Pyae Phyo Aung', '09-424633929', NULL, 'Available', 'Available on 06 Jun', '2026-06-04 03:55:14', '2026-06-04 03:55:14'),
(4, 'Ko Hlaing Min Oo', '09-263992711', NULL, 'Available', 'Oncall Driver', '2026-06-04 03:55:14', '2026-06-04 03:55:14'),
(5, 'Ko Ye Win Tun', '09-790379710', NULL, 'Available', 'Oncall Driver', '2026-06-04 03:55:14', '2026-06-04 03:55:14'),
(6, 'Ko Myint Zaw Hlaing', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(7, 'U Zaw Khin', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(8, 'Ko Aye Min Tun', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(9, 'Ko Kyaw Htoo', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(10, 'Ko Aung Kyaw Moe', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(12, 'Ko Than Naing Soe', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(13, 'Ko Khine Win Aung', '', NULL, 'Available', NULL, '2026-06-08 00:16:20', '2026-06-08 00:16:20'),
(14, 'Ko Kyaw Swar Lwin', '-', NULL, 'Available', '', '2026-06-08 02:43:16', '2026-06-08 02:43:16'),
(15, 'Ko Soe Kyaw Kyaw', '-', NULL, 'Available', '', '2026-06-08 02:47:39', '2026-06-08 02:47:39'),
(16, 'Outside Driver', 'Naypyitaw', NULL, 'Available', '', '2026-06-16 00:44:14', '2026-06-16 00:52:12'),
(17, 'Outside Driver', 'Myo Myanmar 1', NULL, 'Available', '', '2026-06-16 00:46:26', '2026-06-16 00:54:10'),
(18, 'Outside Driver', 'Myo  Myanmar 2', NULL, 'Available', '', '2026-06-16 00:55:41', '2026-06-16 00:55:41'),
(19, 'Outside Driver', 'Ko Than Naing Soe', NULL, 'Available', '', '2026-06-16 00:56:02', '2026-06-16 00:56:02'),
(20, 'Outside Driver', 'Myo Myanmar  3', NULL, 'Available', '', '2026-06-16 01:00:22', '2026-06-16 01:00:22'),
(21, 'Ko Thein Win (Nay Pyi Taw)', '09-251081705', NULL, 'Available', '', '2026-06-18 21:12:56', '2026-06-18 21:12:56'),
(22, 'Ko Khin Maung Lwin', '-', NULL, 'Available', '', '2026-06-22 00:37:20', '2026-06-22 00:37:20'),
(23, '-', '-', NULL, 'Available', '', '2026-06-23 00:18:04', '2026-06-23 00:18:04'),
(24, 'Ko Zaw Zaw Aung', '-', NULL, 'Available', '', '2026-06-23 00:34:30', '2026-06-23 00:34:30'),
(25, 'Outside Driver', '-', NULL, 'Available', '', '2026-06-30 22:49:17', '2026-06-30 22:49:17'),
(26, 'Ko Ko Oo (On Call)', '09420084275', NULL, 'Available', '', '2026-07-30 00:48:34', '2026-07-30 00:48:34'),
(27, 'Ko Thet Naung Naung (On Call)', '09261257235', NULL, 'Available', '', '2026-07-30 00:49:00', '2026-07-30 00:49:00'),
(28, 'Ko Thein Win', '-', NULL, 'Available', 'Nay Pyi Taw', '2026-09-02 20:23:06', '2026-09-02 20:23:06'),
(29, 'Ko Pyae Phyo Zaw', '(09-421153061)', NULL, 'Available', '', '2026-09-08 00:59:28', '2026-09-08 00:59:28'),
(30, 'Ko Kyaw Thu Win', '09-673579962', NULL, 'Available', '', '2026-10-13 21:02:41', '2026-10-13 21:02:41'),
(31, 'Ko Pyae Phyo Zaw', '09-421153061', NULL, 'Available', '', '2026-10-13 21:11:34', '2026-10-13 21:11:34');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_claims`
--

CREATE TABLE `insurance_claims` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `policy_id` bigint(20) UNSIGNED NOT NULL,
  `incident_date` date NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` varchar(30) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_claim_events`
--

CREATE TABLE `insurance_claim_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `claim_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `changed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_documents`
--

CREATE TABLE `insurance_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `policy_id` bigint(20) UNSIGNED NOT NULL,
  `storage_key` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `replaces_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_policies`
--

CREATE TABLE `insurance_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `car_id` int(10) UNSIGNED DEFAULT NULL,
  `monthly_car_id` int(10) UNSIGNED DEFAULT NULL,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `policy_number` varchar(100) NOT NULL,
  `insurance_type` varchar(80) NOT NULL,
  `coverage_details` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `expiration_date` date NOT NULL,
  `premium_amount` decimal(14,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `coverage_amount` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `renewal_parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint(20) UNSIGNED DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_providers`
--

CREATE TABLE `insurance_providers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_10_21_102739_create_personal_access_tokens_table', 1),
(5, '2026_10_22_093722_add_username_to_users_table', 2),
(6, '2026_10_22_000001_add_username_to_users_table', 3),
(7, '2026_10_28_120000_create_legacy_fleet_domain_tables', 4),
(8, '2026_10_28_120100_create_admin_user_links_table', 4),
(9, '2026_10_28_120200_create_insurance_tables', 4),
(10, '2026_10_28_120300_create_monthly_contract_sequences_table', 4),
(11, '2026_10_28_160000_add_laravel_insurance_audit_users', 5),
(12, '2026_11_02_000000_create_vehicle_maintenances_table', 6);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_cars`
--

CREATE TABLE `monthly_cars` (
  `id` int(10) UNSIGNED NOT NULL,
  `car_type` varchar(100) NOT NULL,
  `plate_no` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `seat_capacity` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `availability_status` enum('Available','Assigned','Maintenance','Inactive') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_contracts`
--

CREATE TABLE `monthly_contracts` (
  `id` int(10) UNSIGNED NOT NULL,
  `contract_no` varchar(50) NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `assignment_type` enum('Driver Only','Vehicle Only','Vehicle and Driver') NOT NULL DEFAULT 'Vehicle Only',
  `car_id` int(10) UNSIGNED DEFAULT NULL,
  `driver_id` int(10) UNSIGNED DEFAULT NULL,
  `operator_id` int(10) UNSIGNED DEFAULT NULL,
  `operator_name` varchar(150) DEFAULT NULL,
  `even_odd` enum('Even','Odd') DEFAULT NULL,
  `operation_sheet` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `rental_currency` enum('MMK','USD') NOT NULL DEFAULT 'MMK',
  `rental_status` varchar(50) NOT NULL,
  `status` enum('Draft','Active','Expired','Renewed','Cancelled') NOT NULL DEFAULT 'Active',
  `renewal_parent_id` int(10) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `prepared_by` varchar(150) DEFAULT NULL,
  `invoice_sender` varchar(150) DEFAULT NULL,
  `checked_by` varchar(150) DEFAULT NULL,
  `off_day` varchar(100) DEFAULT NULL,
  `service_hour` varchar(100) DEFAULT NULL,
  `insurance_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `insurance_company` varchar(255) DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_contract_sequences`
--

CREATE TABLE `monthly_contract_sequences` (
  `month_key` varchar(6) NOT NULL,
  `next_value` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_drivers`
--

CREATE TABLE `monthly_drivers` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `license_no` varchar(100) DEFAULT NULL,
  `driver_status` enum('Available','Assigned','Leave','Inactive') NOT NULL DEFAULT 'Available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monthly_drivers`
--

INSERT INTO `monthly_drivers` (`id`, `full_name`, `phone_number`, `license_no`, `driver_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Tun Kyaw', '09-250806122', NULL, 'Available', '', '2026-08-12 02:08:58', '2026-08-12 02:08:58'),
(2, 'Kyaw Naing Win', '09-421040941', NULL, 'Available', '', '2026-08-12 02:09:34', '2026-08-12 02:09:34'),
(3, 'Hein Zin Wai', '09-788390394', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(4, 'Ye Zaw', '09-420306797', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(5, 'U Kyaw Kyaw', '09-420289558', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(6, 'Yan Paing Oo', '09-425885788', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(7, 'Thuya Min Oo', '09-767108212', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(8, 'Kyaw Zin', '09-420098080', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(9, 'Saw Viz Zin', '09-458555120', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(10, 'Zaw Wann', '09-767921671', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(11, 'Chit Ko Ko', '09-267765288', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(12, 'Zaw Thein', '09-799571691', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(13, 'Saw Lah May', '09-795263076', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(14, 'Moe Ko Ko', '09-441206534', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(15, 'Yan Paing', '09-420173187', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(16, 'Arkar Kyaw Lin', '09448017560', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(17, 'Zaw Nyi Nyi Win', '09-424275620', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(18, 'Than Shwe', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(19, 'Zaw Zaw Kyi Win', '09-257010304', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(20, 'Myint Zaw Hlaing', '09-451240134', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(21, 'Aung Kyaw Kyaw', '09 440046814', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(22, 'Than Htike Zaw', '09-421079326', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(23, 'Win Zaw Oo', '09-250298263', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(24, 'Zaw Win', '09-', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(25, 'Aung Myo Sint', '09-421077817', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(26, 'Kyi Than Tun', '09-798411569', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(27, 'Nyein Chan', '09-420034933', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(28, 'Thet Naung Naung', '09-261257235', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(29, 'Thoung Dan', '09-423695045', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(30, 'Kyaw Swar Lwin', '09-250151621', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(31, 'Aung Kyaw Moe', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(32, 'Zayar Htun', '09-440230578', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(33, 'Khin Maung Latt', '09-76007910', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(34, 'San Myo Hlaing', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(35, 'Kyaw Swar Myint', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(36, 'Kyaw Thu Win', '09-673579962', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(37, 'Win Myint Than', '09-970881888', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(38, 'Nyein Ko', '09-443739195', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(39, 'Myo Thaw', '09-957529080', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(40, 'Min Oo', '09-420265742', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(41, 'Zin Min Oo', '09-425002323', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(42, 'Aung Htet', '09-409765285', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(43, 'Soe Kyaw Kyaw', '09-795963340', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(44, 'Ye Thu', '09-795867944', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(45, 'Ko Ko Oo', '09-420084275', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(46, 'Thant Sin Aung', '09-420061613', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(47, 'Ko KO Kyaw', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(48, 'Chan Min Win', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(49, 'Than Htut', '09-428848976', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(50, 'Aung Thu', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(51, 'Zin Min Aung', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(52, 'Aung Myint Thu', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(53, 'Bo Ni Aung', '09-425845944', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(54, 'Win Naing', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(55, 'Nyan Win', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(56, 'Thuya Htet Aung', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(57, 'Myat Min Htet', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(58, 'Min Min Kyi win', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(59, 'Chit San Maung', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(60, 'Aung Myo Sat', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(61, 'Hein Htet', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(62, 'Myo Than Htut', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(63, 'Naing Lin Oo', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(64, 'Aung Ko Thet', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(65, 'Thya Naing', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(66, 'Zay Yar Min', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(67, 'Soe Moe Oo', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(68, 'Tun Tun Win', '', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(69, 'Yan Naing Tun', '09-422919962', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21'),
(70, 'Chit Ko Ko Maung', '09-789368012', NULL, 'Available', NULL, '2026-08-12 02:15:21', '2026-08-12 02:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `operators`
--

CREATE TABLE `operators` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `operator_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `operators`
--

INSERT INTO `operators` (`id`, `full_name`, `phone_number`, `operator_status`, `note`, `created_at`, `updated_at`) VALUES
(1, 'Ko Kaung Min Thant', '', 'Inactive', '', '2026-06-07 22:40:26', '2026-06-17 22:30:12'),
(2, 'Nan Yee Mon Theint', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(3, 'Min Thant', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(4, 'Chaw Su May', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(5, 'Nan Toe', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(6, 'Thin Thin Aye', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(7, 'Thae Oo Min Eain', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(8, 'Hein Htet Zaw', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(9, 'Ngwe Sin Thar', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(10, 'Htet Htet Hlaing', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(11, 'Chu Saung Eain', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(12, 'Khine Zar', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:00'),
(13, 'Zin Me Yu Lay', NULL, 'Active', NULL, '2026-06-07 23:00:00', '2026-06-07 23:00:50'),
(16, 'Kaung Min Thant', NULL, 'Active', NULL, '2026-06-15 03:14:34', '2026-06-15 03:14:34'),
(17, 'Win Kyaw Aung', NULL, 'Active', NULL, '2026-06-15 03:14:34', '2026-06-15 03:14:34');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('mXTYgHE9O4IOAAdPY1T48LoEUjgWPq4gRB4NqOFh', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.8078.4 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicWcyOFM4ZHBjMWFVV3RtcUVZRDJvcmcyZ2c3WXlJVFFxZURHU1hiZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvdXNlciI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794219009),
('NlJpG6qhHwYIgTdF3SUxYQ97g8CDNCWZX1ZelEBr', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.8078.4 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSTZlcHliYU83MFFHZUY2NWtweldhdG5pQnV4YWk2aXlGSTBReklybiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvdXNlciI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794218892),
('OS30L2PlxT3XrdsfsJqP9iDM4zAbctsAAbZCp2LE', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiOGVHTU9pelEwT2ZuU2VDRnVuRHNnbG5pbk1pNkdqYUoxR1U3d0ZLVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvbW9udGhseS1jb250cmFjdHMvc3VtbWFyeSI7czo1OiJyb3V0ZSI7Tjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZWI1OGI4NmVkMDBiYmE0NGU2Y2NhNmRkMWU1OWFhZmJmYmM5NWZkNzRiZDBkYmE3MWJjNGExYmMwMjczYWIzNyI7fQ==', 1794298193),
('owKBS9Xwk6oQcpesggLXHNB6N0g7OZOF6Tx6L4qs', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMk1ta0s5YUxqOE1uWHlCWFNFd251d2lwNkhxQTlUWnFvdXNjNEQyWCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvZGFzaGJvYXJkIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiJlYjU4Yjg2ZWQwMGJiYTQ0ZTZjY2E2ZGQxZTU5YWFmYmZiYzk1ZmQ3NGJkMGRiYTcxYmM0YTFiYzAyNzNhYjM3Ijt9', 1794283328),
('Rs29GaBR7OpVnNDTP3OX0RaLfRsJiP4f0SkMJb3S', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQkN0OXp0dTVtbVpGYTFHNm9HMzNKTUNBNFZ2NFo3MndQOENUWGZxSyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvZGFpbHktYm9va2luZ3Mvc3VtbWFyeSI7czo1OiJyb3V0ZSI7Tjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZWI1OGI4NmVkMDBiYmE0NGU2Y2NhNmRkMWU1OWFhZmJmYmM5NWZkNzRiZDBkYmE3MWJjNGExYmMwMjczYWIzNyI7fQ==', 1794298935),
('whAwFzzXnLcT6BLoa8TmVBemwQ2WUErc9thq95XY', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRmtrdlhCNjlqYkluUlV5MzB5RUxPR3Zqck12RzdBVmsyUlRuNm9ZQiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvZGFpbHktYm9va2luZ3Mvc3VtbWFyeSI7czo1OiJyb3V0ZSI7Tjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiYTA2YzY3ZmEzZmE0NWM3YjhhZmMyMDA2ZWNlODQ5MzQwNmZkOTRiMmFkZmYzMzliOTUzZjhhMWZjMjc5NzY5NSI7fQ==', 1794290766),
('yqjuqTB3cWuQSwg56z3Z7tTmyEbg1AnjRb47pTYQ', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/156.0.8078.4 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ3AzVUxwTVFIeFp0THpJTW5YVU5sNUFPeTBTQWJKQlVLa0pCc2F3cSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYW5jdHVtL2NzcmYtY29va2llIjtzOjU6InJvdXRlIjtzOjE5OiJzYW5jdHVtLmNzcmYtY29va2llIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1794218907);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `username`) VALUES
(1, 'GSS Administrator', 'admin@gss.com', NULL, '$2y$12$aqTnQD9R2O.msgn.CbHG8usuKoMFcTIK0vpY0lhQaXno8v45.iJAu', NULL, '2026-10-22 03:05:45', '2026-10-22 03:05:45', NULL),
(2, 'GSS Administrator', 'admin@gss.local', NULL, '$2y$12$tqI1GvMCFwYe1k.Y1Zx5C.yEEYEt0Ip4SgpjJY22OkJLN3r/oqcry', NULL, '2026-10-22 03:17:57', '2026-10-22 03:17:57', 'admin'),
(3, 'Aung Aung', 'aung@local.gss', NULL, '$2y$12$/MKYtLagOZclKuUO2zb/suu2QXc.Vj41qCn4CkCK3RqFIx4uGM9lO', NULL, '2026-10-28 02:07:28', '2026-10-28 02:07:28', 'aung'),
(4, 'test acc', 'test@gss.local', NULL, '$2y$12$MPlYQNfQgnBPMlwZYngacOSNDm/5Wr12eZLTjS04pxjSX0Eivjh9u', NULL, '2026-11-09 02:59:07', '2026-11-09 02:59:07', 'Test1'),
(5, 'Zin Wai Yan Oo', 'seteam@nicemyanmartravel.com', NULL, '$2y$12$o3X6pLJXE3.9Y70Uf7JWseE9NF4tVTKjV7oF0B3.Uwte0jLfjoUh6', NULL, '2026-11-09 21:30:48', '2026-11-09 21:30:48', 'zano'),
(6, 'KST', 'thwel4186@gmail.com', NULL, '$2y$12$tdU2hd.uDwBNOdwE/xRgwuoO4GeGb6bH5HYc/33sBiib.gdeb/L6a', NULL, '2026-11-09 21:49:35', '2026-11-09 21:49:35', 'Thwel');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_maintenances`
--

CREATE TABLE `vehicle_maintenances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `car_id` int(10) UNSIGNED DEFAULT NULL,
  `monthly_car_id` int(10) UNSIGNED DEFAULT NULL,
  `maintenance_type` varchar(30) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `started_at` date DEFAULT NULL,
  `completed_at` date DEFAULT NULL,
  `odometer_reading` int(10) UNSIGNED DEFAULT NULL,
  `service_provider` varchar(200) DEFAULT NULL,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `actual_cost` decimal(15,2) DEFAULT NULL,
  `currency` char(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_admins_username` (`username`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `admin_user_links`
--
ALTER TABLE `admin_user_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_user_links_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `admin_user_links_admin_id_unique` (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bookings_status_dates` (`status`,`start_date`,`end_date`),
  ADD KEY `idx_bookings_car_id` (`car_id`),
  ADD KEY `idx_bookings_secondary_car_id` (`secondary_car_id`),
  ADD KEY `idx_bookings_driver_id` (`driver_id`),
  ADD KEY `idx_bookings_operator_id` (`operator_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cars_plate_no_unique` (`plate_no`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `drivers_license_no_unique` (`license_no`);

--
-- Indexes for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `insurance_claims_reference_number_unique` (`reference_number`),
  ADD KEY `insurance_claims_policy_id_foreign` (`policy_id`);

--
-- Indexes for table `insurance_claim_events`
--
ALTER TABLE `insurance_claim_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `insurance_claim_events_claim_id_foreign` (`claim_id`),
  ADD KEY `insurance_claim_events_changed_by_foreign` (`changed_by`),
  ADD KEY `insurance_claim_events_changed_by_user_id_foreign` (`changed_by_user_id`);

--
-- Indexes for table `insurance_documents`
--
ALTER TABLE `insurance_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `insurance_documents_storage_key_unique` (`storage_key`),
  ADD UNIQUE KEY `insurance_documents_replaces_id_unique` (`replaces_id`),
  ADD KEY `insurance_documents_policy_id_foreign` (`policy_id`);

--
-- Indexes for table `insurance_policies`
--
ALTER TABLE `insurance_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `insurance_policy_term_unique` (`provider_id`,`policy_number`,`start_date`),
  ADD UNIQUE KEY `insurance_policies_renewal_parent_id_unique` (`renewal_parent_id`),
  ADD KEY `insurance_policies_created_by_foreign` (`created_by`),
  ADD KEY `insurance_car_dates` (`car_id`,`insurance_type`,`expiration_date`),
  ADD KEY `insurance_monthly_car_dates` (`monthly_car_id`,`insurance_type`,`expiration_date`),
  ADD KEY `insurance_due_dates` (`expiration_date`,`cancelled_at`,`archived_at`),
  ADD KEY `insurance_policies_created_by_user_id_foreign` (`created_by_user_id`);

--
-- Indexes for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_cars`
--
ALTER TABLE `monthly_cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `monthly_cars_plate_no_unique` (`plate_no`);

--
-- Indexes for table `monthly_contracts`
--
ALTER TABLE `monthly_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `monthly_contracts_contract_no_unique` (`contract_no`),
  ADD KEY `idx_monthly_contracts_car_id` (`car_id`),
  ADD KEY `idx_monthly_contracts_driver_id` (`driver_id`),
  ADD KEY `idx_monthly_contracts_operator_id` (`operator_id`),
  ADD KEY `idx_monthly_contracts_status_end_date` (`status`,`end_date`),
  ADD KEY `idx_monthly_contracts_renewal_parent_id` (`renewal_parent_id`),
  ADD KEY `monthly_contracts_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `monthly_contract_sequences`
--
ALTER TABLE `monthly_contract_sequences`
  ADD PRIMARY KEY (`month_key`);

--
-- Indexes for table `monthly_drivers`
--
ALTER TABLE `monthly_drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `monthly_drivers_license_no_unique` (`license_no`);

--
-- Indexes for table `operators`
--
ALTER TABLE `operators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `operators_full_name_unique` (`full_name`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- Indexes for table `vehicle_maintenances`
--
ALTER TABLE `vehicle_maintenances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_maintenances_car_id_foreign` (`car_id`),
  ADD KEY `vehicle_maintenances_monthly_car_id_foreign` (`monthly_car_id`),
  ADD KEY `vehicle_maintenances_created_by_foreign` (`created_by`),
  ADD KEY `vehicle_maintenances_status_scheduled_date_index` (`status`,`scheduled_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_user_links`
--
ALTER TABLE `admin_user_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_claim_events`
--
ALTER TABLE `insurance_claim_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_documents`
--
ALTER TABLE `insurance_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_policies`
--
ALTER TABLE `insurance_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `monthly_cars`
--
ALTER TABLE `monthly_cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_contracts`
--
ALTER TABLE `monthly_contracts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_drivers`
--
ALTER TABLE `monthly_drivers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `operators`
--
ALTER TABLE `operators`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `vehicle_maintenances`
--
ALTER TABLE `vehicle_maintenances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_user_links`
--
ALTER TABLE `admin_user_links`
  ADD CONSTRAINT `admin_user_links_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `admin_user_links_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_operator_id_foreign` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_secondary_car_id_foreign` FOREIGN KEY (`secondary_car_id`) REFERENCES `cars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `insurance_claims`
--
ALTER TABLE `insurance_claims`
  ADD CONSTRAINT `insurance_claims_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `insurance_policies` (`id`);

--
-- Constraints for table `insurance_claim_events`
--
ALTER TABLE `insurance_claim_events`
  ADD CONSTRAINT `insurance_claim_events_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `insurance_claim_events_changed_by_user_id_foreign` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `insurance_claim_events_claim_id_foreign` FOREIGN KEY (`claim_id`) REFERENCES `insurance_claims` (`id`);

--
-- Constraints for table `insurance_documents`
--
ALTER TABLE `insurance_documents`
  ADD CONSTRAINT `insurance_documents_policy_id_foreign` FOREIGN KEY (`policy_id`) REFERENCES `insurance_policies` (`id`),
  ADD CONSTRAINT `insurance_documents_replaces_id_foreign` FOREIGN KEY (`replaces_id`) REFERENCES `insurance_documents` (`id`);

--
-- Constraints for table `insurance_policies`
--
ALTER TABLE `insurance_policies`
  ADD CONSTRAINT `insurance_policies_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `insurance_policies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `insurance_policies_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `insurance_policies_monthly_car_id_foreign` FOREIGN KEY (`monthly_car_id`) REFERENCES `monthly_cars` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `insurance_policies_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `insurance_providers` (`id`),
  ADD CONSTRAINT `insurance_policies_renewal_parent_id_foreign` FOREIGN KEY (`renewal_parent_id`) REFERENCES `insurance_policies` (`id`);

--
-- Constraints for table `monthly_contracts`
--
ALTER TABLE `monthly_contracts`
  ADD CONSTRAINT `monthly_contracts_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `monthly_cars` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `monthly_contracts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `monthly_contracts_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `monthly_drivers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `monthly_contracts_operator_id_foreign` FOREIGN KEY (`operator_id`) REFERENCES `operators` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `monthly_contracts_renewal_parent_id_foreign` FOREIGN KEY (`renewal_parent_id`) REFERENCES `monthly_contracts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicle_maintenances`
--
ALTER TABLE `vehicle_maintenances`
  ADD CONSTRAINT `vehicle_maintenances_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `vehicle_maintenances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vehicle_maintenances_monthly_car_id_foreign` FOREIGN KEY (`monthly_car_id`) REFERENCES `monthly_cars` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
