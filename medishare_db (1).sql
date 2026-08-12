-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2026 at 11:55 AM
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
-- Database: `medishare_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_feed`
--

CREATE TABLE `activity_feed` (
  `id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_feed`
--

INSERT INTO `activity_feed` (`id`, `action_type`, `description`, `created_at`) VALUES
(1, 'wishlist', 'An NGO needs \"abc\"', '2026-04-15 07:32:22'),
(2, 'fulfil', 'A donor volunteered to fulfil a wishlist need for \"abc\"', '2026-04-15 07:35:54'),
(3, 'listing', 'A donor listed \"paracetamol\"', '2026-04-15 07:49:10'),
(4, 'request', 'An NGO requested \"paracetamol\"', '2026-04-15 07:50:03'),
(5, 'donation', 'A donor approved a request for \"paracetamol\"', '2026-04-15 07:50:20'),
(6, 'listing', 'A donor listed \"abc\"', '2026-04-15 08:25:28'),
(7, 'request', 'An NGO requested \"abc\"', '2026-04-15 08:29:55'),
(8, 'listing', 'A donor listed \"abcq\"', '2026-04-15 08:44:55'),
(9, 'donation', 'A donor approved a request for \"abc\"', '2026-04-15 08:46:51'),
(10, 'wishlist', 'An NGO needs \"paracetamol\"', '2026-04-15 08:47:45');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE `admin_notes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'hello', 'hello', 1, 5, '2026-04-15 07:30:44');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 6, 'medicine_listed', 'Donor \'Creative WebCrafters\' listed medicine: abc (Category: Antibiotic, Qty: 50)', '2026-04-13 19:28:22'),
(2, 5, 'ngo_verified', 'Admin verified NGO: \'chaka maka ngo\'', '2026-04-14 03:20:18'),
(3, 5, 'expired_cleanup', 'Admin cleaned up 0 expired medicine(s).', '2026-04-14 03:20:54'),
(4, 7, 'request_submitted', 'NGO \'chaka maka ngo\' requested medicine: \"abc\"', '2026-04-14 03:21:43'),
(5, 6, 'request_approved', 'Donor \'Creative WebCrafters\' approved request #2 for \"abc\" from NGO \'chaka maka ngo\'', '2026-04-14 03:23:57'),
(6, 5, 'csv_export', 'Admin exported CSV: users', '2026-04-14 04:17:58'),
(7, 5, 'csv_export', 'Admin exported CSV: donations', '2026-04-15 05:09:25'),
(8, 5, 'user_banned', 'Admin deleted user: \'Xyz\' (Role: donor)', '2026-04-15 05:13:32'),
(9, 5, 'ngo_verified', 'Admin verified NGO: \'Creative WebCrafters\'', '2026-04-15 05:15:08'),
(10, 5, 'announcement_created', 'Admin created announcement: \"hello\"', '2026-04-15 07:30:44'),
(11, 8, 'wishlist_added', 'NGO \'Creative WebCrafters\' posted wishlist: \"abc\"', '2026-04-15 07:32:22'),
(12, 9, 'wishlist_fulfilled', 'Donor \'CW\' fulfilled wishlist #1: \"abc\" for NGO \'Creative WebCrafters\'', '2026-04-15 07:35:54'),
(13, 5, 'ngo_verified', 'Admin verified NGO: \'Creative WebCrafters\'', '2026-04-15 07:47:01'),
(14, 9, 'medicine_listed', 'Donor \'CW\' listed medicine: paracetamol (Category: Painkiller, Qty: 150)', '2026-04-15 07:49:10'),
(15, 10, 'request_submitted', 'NGO \'Creative WebCrafters\' requested medicine: \"paracetamol\"', '2026-04-15 07:50:03'),
(16, 9, 'request_approved', 'Donor \'CW\' approved request #3 for \"paracetamol\" from NGO \'Creative WebCrafters\'', '2026-04-15 07:50:20'),
(17, 9, 'medicine_listed', 'Donor \'CW\' listed medicine: abc (Category: Antibiotic, Qty: 100)', '2026-04-15 08:25:28'),
(18, 10, 'request_submitted', 'NGO \'Creative WebCrafters\' requested medicine: \"abc\"', '2026-04-15 08:29:55'),
(19, 9, 'medicine_listed', 'Donor \'CW\' listed medicine: abcq (Category: Antibiotic, Qty: 100)', '2026-04-15 08:44:55'),
(20, 9, 'request_approved', 'Donor \'CW\' approved request #4 for \"abc\" from NGO \'Creative WebCrafters\'', '2026-04-15 08:46:51'),
(21, 10, 'wishlist_added', 'NGO \'Creative WebCrafters\' posted wishlist: \"paracetamol\"', '2026-04-15 08:47:45');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `expiry_date` date NOT NULL,
  `quantity_strips` int(11) NOT NULL,
  `category` enum('Antibiotic','Painkiller','Vitamin','Supplement','Other') NOT NULL DEFAULT 'Other',
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('available','requested','donated') DEFAULT 'available',
  `listed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`medicine_id`, `donor_id`, `medicine_name`, `expiry_date`, `quantity_strips`, `category`, `image_path`, `status`, `listed_at`) VALUES
(3, 9, 'paracetamol', '2027-06-15', 150, 'Painkiller', 'med_69df42f67d83d1.85508837.jpg', 'donated', '2026-04-15 07:49:10'),
(4, 9, 'abc', '2026-05-07', 100, 'Antibiotic', 'med_69df4b78cd0e24.42019007.jpg', 'donated', '2026-04-15 08:25:28'),
(5, 9, 'abcq', '2026-05-12', 100, 'Antibiotic', 'med_69df50074139b4.96158672.jpg', 'available', '2026-04-15 08:44:55');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(7, 10, 'Your account has been verified. You can now request medicines.', 1, '2026-04-15 07:47:01'),
(8, 9, 'An NGO has requested your medicine: \"paracetamol\".', 1, '2026-04-15 07:50:03'),
(9, 10, 'Your request for \"paracetamol\" has been approved! Check donor contact details.', 1, '2026-04-15 07:50:20'),
(10, 9, 'An NGO has requested your medicine: \"abc\".', 1, '2026-04-15 08:29:55'),
(11, 10, 'Your request for \"abc\" has been approved! Check donor contact details.', 1, '2026-04-15 08:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `request_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` varchar(500) DEFAULT NULL,
  `request_note` varchar(500) DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `ngo_id`, `medicine_id`, `request_status`, `rejection_reason`, `request_note`, `request_date`) VALUES
(3, 10, 3, 'approved', NULL, NULL, '2026-04-15 07:50:03'),
(4, 10, 4, 'approved', NULL, NULL, '2026-04-15 08:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('donor','ngo','admin') DEFAULT 'donor',
  `contact_number` varchar(15) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text NOT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `contact_number`, `phone`, `address`, `is_verified`, `created_at`) VALUES
(5, 'Administrator', 'admin@medishare.com', '$2y$10$E7zkAtR4Ea/Ey7KRzYwZBOKWWCXXqKREXyqkZpxaL1z1ojSwvM27e', 'admin', '0000000000', '0000000000', 'Admin Office', 1, '2026-03-26 15:08:20'),
(9, 'CW', 'piyushpanchal828@gmail.com', '$2y$10$MXDCUc9bJLeUVPPQDVQd/OG4yGuRCn75gZdFfQR3rekMW4pULfzbq', 'donor', '7845123698', '7845123698', 'ejnfke jndjkejk wdwoq', 1, '2026-04-15 07:35:19'),
(10, 'Creative WebCrafters', 'hello.pkwebcrafters@gmail.com', '$2y$10$HNcauqmTAYjq5/PY4QJ16ex5UKXcsyueLbk8s2SIxLJuhFM.QzkqS', 'ngo', '7896541230', '7896541230', 'bxfbf', 1, '2026-04-15 07:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `category` enum('Antibiotic','Painkiller','Vitamin','Supplement','Other') NOT NULL DEFAULT 'Other',
  `quantity_needed` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `status` enum('open','fulfilled') NOT NULL DEFAULT 'open',
  `fulfilled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `ngo_id`, `medicine_name`, `category`, `quantity_needed`, `description`, `status`, `fulfilled_by`, `created_at`) VALUES
(2, 10, 'paracetamol', 'Painkiller', 10, NULL, 'open', NULL, '2026-04-15 08:47:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_feed`
--
ALTER TABLE `activity_feed`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `ngo_id` (`ngo_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ngo_id` (`ngo_id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_feed`
--
ALTER TABLE `activity_feed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `admin_notes`
--
ALTER TABLE `admin_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD CONSTRAINT `admin_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
