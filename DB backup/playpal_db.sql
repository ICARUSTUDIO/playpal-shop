-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 03:21 PM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `playpal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `name`, `created_at`) VALUES
(1, 'Call of Duty', '2025-05-11 20:30:00'),
(2, 'Mortal Kombat', '2025-05-11 20:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `giveaways`
--

CREATE TABLE `giveaways` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `game_id` int(11) NOT NULL,
  `thumbnail` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `giveaways`
--

INSERT INTO `giveaways` (`id`, `title`, `description`, `game_id`, `thumbnail`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 'Call of Duty Epic Skin Giveaway', 'Win an exclusive Legendary Nikto Dark Side skin for Call of Duty Mobile! Enter now for a chance to dominate the battlefield.', 1, '682232a568d9c.jfif', '2025-05-01', '2025-05-20', 'active', '2025-05-12 09:08:49');

-- --------------------------------------------------------

--
-- Table structure for table `giveaway_entries`
--

CREATE TABLE `giveaway_entries` (
  `id` int(11) NOT NULL,
  `giveaway_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `giveaway_prizes`
--

CREATE TABLE `giveaway_prizes` (
  `id` int(11) NOT NULL,
  `giveaway_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `giveaway_prizes`
--

INSERT INTO `giveaway_prizes` (`id`, `giveaway_id`, `description`, `created_at`) VALUES
(9, 1, '200000 cp', '2025-05-15 11:21:51'),
(10, 1, 'Legendary Guns', '2025-05-15 11:21:51'),
(11, 1, 'Prestige', '2025-05-15 11:21:51'),
(12, 1, 'LEVEL 500', '2025-05-15 11:21:51');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `sale_discount` decimal(5,2) DEFAULT 0.00 CHECK (`sale_discount` >= 0 and `sale_discount` <= 100),
  `game_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_rentable` tinyint(1) DEFAULT 0,
  `rent_price` decimal(10,2) DEFAULT NULL,
  `product_type` enum('buy','rent') NOT NULL DEFAULT 'buy'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `sale_discount`, `game_id`, `created_at`, `is_rentable`, `rent_price`, `product_type`) VALUES
(1, 'Legendary Nikto Account', 'Premium Call of Duty account with Nikto Dark Side skin, Legendary rank, and exclusive weapons.', '99.99', '0.00', 1, '2025-05-11 20:30:00', 0, NULL, 'buy'),
(2, 'Scorpion Skin', 'Exclusive Scorpion skin with maxed-out abilities for Mortal Kombat.', '49.99', '0.00', 2, '2025-05-11 20:30:00', 0, NULL, 'buy'),
(3, 'Legendary Nikto Account', 'lggy', '11.00', '0.00', 1, '2025-05-11 21:51:17', 0, NULL, 'buy'),
(7, 'vv', 'hh', '55.00', '0.00', 2, '2025-05-13 18:51:07', 0, NULL, 'buy'),
(8, 'Legendary sc Account', 'gg', '24.00', '0.00', 2, '2025-05-13 19:34:58', 1, '24.00', 'buy'),
(10, 'Legendary sbz Account', 'sbz', '44.00', '0.00', 1, '2025-05-14 21:27:39', 1, '23.00', 'buy'),
(11, 'Legendary sbvvz Account', 'vvv', '44.00', '0.00', 1, '2025-05-14 21:48:34', 1, '23.00', 'buy');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `created_at`) VALUES
(1, 1, '682118e6eeb23.jfif', '2025-05-11 21:38:47'),
(2, 1, '682118e6eeddf.jfif', '2025-05-11 21:38:47'),
(3, 3, '68211bd5bdbb2.jfif', '2025-05-11 21:51:17'),
(4, 3, '68211bd5c302e.jfif', '2025-05-11 21:51:17'),
(9, 7, '6823949bbbced.jfif', '2025-05-13 18:51:07'),
(10, 8, '68239ee2f0104.jfif', '2025-05-13 19:34:59'),
(12, 10, '68250acbb1e90.png', '2025-05-14 21:27:39'),
(13, 11, '68250fb2ba854.jfif', '2025-05-14 21:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','reviewed','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `type` enum('buy','rent') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','moderator') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin123', 'admin', '2025-05-11 20:30:00'),
(2, 'moderator', 'mod123', 'moderator', '2025-05-11 20:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_visits`
--

CREATE TABLE `user_visits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `visit_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_visits`
--

INSERT INTO `user_visits` (`id`, `user_id`, `visit_time`) VALUES
(1, 1, '2025-05-11 21:18:03'),
(2, 1, '2025-05-11 21:27:41'),
(3, 1, '2025-05-11 21:35:51'),
(4, 1, '2025-05-11 21:38:21'),
(5, 1, '2025-05-11 21:38:32'),
(6, 1, '2025-05-11 21:38:46'),
(7, 1, '2025-05-11 21:40:38'),
(8, 1, '2025-05-11 21:40:48'),
(9, 1, '2025-05-11 21:41:00'),
(10, 1, '2025-05-11 21:49:41'),
(11, 1, '2025-05-11 21:51:17'),
(12, 1, '2025-05-12 07:59:58'),
(13, 1, '2025-05-12 08:00:27'),
(14, 1, '2025-05-12 08:33:10'),
(15, 1, '2025-05-12 08:34:29'),
(16, 1, '2025-05-12 08:35:46'),
(17, 1, '2025-05-12 08:38:17'),
(18, 1, '2025-05-12 08:52:23'),
(19, 1, '2025-05-12 09:08:57'),
(20, 1, '2025-05-12 17:14:36'),
(21, 1, '2025-05-12 17:15:29'),
(22, 1, '2025-05-12 17:22:03'),
(23, 1, '2025-05-12 17:23:05'),
(24, 1, '2025-05-12 17:24:48'),
(25, 1, '2025-05-12 17:25:21'),
(26, 1, '2025-05-12 17:25:39'),
(27, 1, '2025-05-12 17:40:53'),
(28, 1, '2025-05-12 17:41:31'),
(29, 1, '2025-05-12 18:10:31'),
(30, 1, '2025-05-12 18:52:44'),
(31, 1, '2025-05-13 17:06:07'),
(32, 1, '2025-05-13 17:08:45'),
(33, 1, '2025-05-13 17:08:58'),
(34, 1, '2025-05-13 17:09:11'),
(35, 1, '2025-05-13 17:09:19'),
(36, 1, '2025-05-13 17:09:21'),
(37, 1, '2025-05-13 17:09:24'),
(38, 1, '2025-05-13 17:19:14'),
(39, 1, '2025-05-13 17:19:20'),
(40, 1, '2025-05-13 17:20:00'),
(41, 1, '2025-05-13 17:20:09'),
(42, 1, '2025-05-13 17:20:13'),
(43, 1, '2025-05-13 17:20:19'),
(44, 1, '2025-05-13 17:20:21'),
(45, 1, '2025-05-13 17:39:17'),
(46, 1, '2025-05-13 17:40:24'),
(47, 1, '2025-05-13 17:50:08'),
(48, 1, '2025-05-13 18:26:24'),
(49, 1, '2025-05-13 18:30:53'),
(50, 1, '2025-05-13 18:31:29'),
(51, 1, '2025-05-13 18:31:30'),
(52, 1, '2025-05-13 18:31:31'),
(53, 1, '2025-05-13 18:31:31'),
(54, 1, '2025-05-13 18:41:22'),
(55, 1, '2025-05-13 18:42:30'),
(56, 1, '2025-05-13 18:51:03'),
(57, 1, '2025-05-13 18:51:07'),
(58, 1, '2025-05-13 18:51:23'),
(59, 1, '2025-05-13 18:51:27'),
(60, 1, '2025-05-13 18:52:47'),
(61, 1, '2025-05-13 18:52:49'),
(62, 1, '2025-05-13 19:14:27'),
(63, 1, '2025-05-13 19:30:27'),
(64, 1, '2025-05-13 19:30:58'),
(65, 1, '2025-05-13 19:33:41'),
(66, 1, '2025-05-13 19:34:35'),
(67, 1, '2025-05-13 19:34:58'),
(68, 1, '2025-05-14 20:57:53'),
(69, 1, '2025-05-14 21:26:25'),
(70, 1, '2025-05-14 21:27:39'),
(71, 1, '2025-05-14 21:48:34'),
(72, 1, '2025-05-15 10:54:28'),
(73, 1, '2025-05-15 10:59:01'),
(74, 1, '2025-05-15 10:59:52'),
(75, 1, '2025-05-15 11:00:20'),
(76, 1, '2025-05-15 11:01:40'),
(77, 1, '2025-05-15 11:02:07'),
(78, 1, '2025-05-15 11:02:47'),
(79, 1, '2025-05-15 11:03:36'),
(80, 1, '2025-05-15 11:04:13'),
(81, 1, '2025-05-15 11:20:15'),
(82, 1, '2025-05-15 11:20:25'),
(83, 1, '2025-05-15 11:20:30'),
(84, 1, '2025-05-15 11:20:32'),
(85, 1, '2025-05-15 11:21:17'),
(86, 1, '2025-05-15 11:21:28'),
(87, 1, '2025-05-15 11:21:51'),
(88, 1, '2025-05-15 11:22:53'),
(89, 1, '2025-05-15 11:23:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `giveaways`
--
ALTER TABLE `giveaways`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `giveaway_entries`
--
ALTER TABLE `giveaway_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_per_giveaway` (`giveaway_id`,`email`);

--
-- Indexes for table `giveaway_prizes`
--
ALTER TABLE `giveaway_prizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `giveaway_id` (`giveaway_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_game_id` (`game_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_product_id` (`product_id`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_visits`
--
ALTER TABLE `user_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `giveaways`
--
ALTER TABLE `giveaways`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `giveaway_entries`
--
ALTER TABLE `giveaway_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `giveaway_prizes`
--
ALTER TABLE `giveaway_prizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_visits`
--
ALTER TABLE `user_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `giveaways`
--
ALTER TABLE `giveaways`
  ADD CONSTRAINT `giveaways_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `giveaway_entries`
--
ALTER TABLE `giveaway_entries`
  ADD CONSTRAINT `giveaway_entries_ibfk_1` FOREIGN KEY (`giveaway_id`) REFERENCES `giveaways` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `giveaway_prizes`
--
ALTER TABLE `giveaway_prizes`
  ADD CONSTRAINT `giveaway_prizes_ibfk_1` FOREIGN KEY (`giveaway_id`) REFERENCES `giveaways` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_game_id` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_id` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_visits`
--
ALTER TABLE `user_visits`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
