-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 16, 2026 at 07:14 PM
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
-- Database: `notes_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `sex` enum('female','male') NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `user_type` enum('admin','user') NOT NULL DEFAULT 'user',
  `user_status` enum('active','not_active') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `phone`, `sex`, `password_hash`, `profile_picture`, `user_type`, `user_status`, `created_at`) VALUES
(1, 'abubakr ali', 'abubakr.ali', 'me@gmail.com', NULL, 'male', '$2y$10$uRCw/I2HCLGtsHk46JlTu.wK0gjcHW9et/pzGpixxDLYf8SNWRsEq', NULL, 'user', 'active', '2026-01-06 06:05:18'),
(2, 'Abubakr Omar', 'abubakr.omar', 'abubakr@gmail.com', NULL, 'male', '$2y$10$vzxGGjne1QYR21PPwEetY.q7bFJq0D7uEXlqXP9jGBY2UwyVgLrUi', NULL, 'user', 'active', '2026-01-06 06:08:31'),
(3, 'abubakr ali', 'abubakr.ali1', 'abubakrtrial@gmail.com', NULL, 'male', '$2y$10$LrG8EDjNNF42YgOthXLHneexi6yUo24g3UI8owhnIrkTwgaa0NPvi', NULL, 'user', 'active', '2026-01-06 07:14:33'),
(4, 'Layla', 'layla', 'layla@gmail.com', NULL, 'male', '$2y$10$KNjBk4AOBktilRPJuQTpjuLRQef0mLWRIPM4ZBLLHu.OZNhPTAvUK', NULL, 'user', 'active', '2026-01-06 07:17:01'),
(5, 'Hayder', 'hayder', 'hayder@gmail.com', NULL, 'male', '$2y$10$kgq0zImoFCrzigb5ovVXbeAB6sTxTzBhFlB7j.KpS43s5BtUjqsb6', NULL, 'user', 'active', '2026-01-06 07:22:24'),
(6, 'abubakr', 'admin-abubakr', 'admin@gmail.com', '0634110178', 'male', '$2y$10$s0RjdJ6Tm5.l7c5UUjz1VeiMUs.32HeS6oztACZ2LPtubyBqJjjk6', 'uploads/avatars/user_6_1768203278.jpg', 'user', 'active', '2026-01-12 06:02:44'),
(7, 'Amira Ali', 'amira.ali', 'Amira@gmail.com', NULL, 'female', '$2y$10$s04dBIQ.Jk/5En/F5m3pZeOryh32TIWPC6bqgGUzpBFlWsLbmTJii', NULL, 'user', 'active', '2026-01-12 07:58:09'),
(8, 'osman ali', 'osman.ali', 'osman@gmail.com', NULL, 'male', '$2y$10$4MtiHQ1ADI0oWgjld1VyRu04bXt3bFMJUZLG.tv2jn0aFwkdft0qW', NULL, 'user', 'active', '2026-01-12 08:11:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
