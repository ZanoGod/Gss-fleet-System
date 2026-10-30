-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2026 at 09:40 AM
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
(11, '2026_10_28_160000_add_laravel_insurance_audit_users', 5);

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
('8Lktf2IhGg2jDwU2JbrZfmhmBmF2wijHbnv2xz8g', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMkdZWHB3OEprTWpkZkFiRGJTa25OTDVDdWM4dm13S1BIUU1vU1JRNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvdXNlciI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZWI1OGI4NmVkMDBiYmE0NGU2Y2NhNmRkMWU1OWFhZmJmYmM5NWZkNzRiZDBkYmE3MWJjNGExYmMwMjczYWIzNyI7fQ==', 1793349566),
('JzTgavyzAboJyjcx1JRnLlYW86Jz9iikcClODjdd', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiTFFuQ29LWlk4VVpCV2xKcE9oTkVRd1BuYVBBWTV6SWNDWW1EWURiZiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvdXNlciI7czo1OiJyb3V0ZSI7Tjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMmY4NWFhYjVmYjFmMDg1OTBhMGQ5MDQwZTVmYjRmMmNkYjM0NDE2MGU3ODRhYzc0OTQ4ZWIzNWRjNmFiZTBhYiI7fQ==', 1793163741),
('ZRJ2ahid73Q3CkMkPfWW2ug6SFxnpmFmYzal0HuB', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSmR2aWczTThHWEYyQXhqYWJiS0pLUnFtNzdPOEFQdVUwQW8xZ0VsciI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvdXNlciI7czo1OiJyb3V0ZSI7Tjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiZWI1OGI4NmVkMDBiYmE0NGU2Y2NhNmRkMWU1OWFhZmJmYmM5NWZkNzRiZDBkYmE3MWJjNGExYmMwMjczYWIzNyI7fQ==', 1793248462);

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
(3, 'Aung Aung', 'aung@local.gss', NULL, '$2y$12$/MKYtLagOZclKuUO2zb/suu2QXc.Vj41qCn4CkCK3RqFIx4uGM9lO', NULL, '2026-10-28 02:07:28', '2026-10-28 02:07:28', 'aung');

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `operators`
--
ALTER TABLE `operators`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
