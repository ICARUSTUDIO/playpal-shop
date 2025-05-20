-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 20, 2025 at 08:29 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u460963782_playpal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `giveaway_prizes`
--

CREATE TABLE `giveaway_prizes` (
  `id` int(11) NOT NULL,
  `giveaway_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `sale_price` decimal(10,2) DEFAULT NULL,
  `game_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_rentable` tinyint(1) DEFAULT 0,
  `rent_price` decimal(10,2) DEFAULT NULL,
  `product_type` enum('buy','rent') NOT NULL DEFAULT 'buy'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `sale_price`, `game_id`, `created_at`, `is_rentable`, `rent_price`, `product_type`) VALUES
(16, 'Test', 'Test', 233.00, 55.00, 2, '2025-05-19 14:32:59', 0, NULL, 'buy'),
(17, 'Test', 'Test', 333.00, 100.00, 1, '2025-05-19 14:34:04', 1, 10.00, 'buy');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `created_at`) VALUES
(4, 16, '682b411b4b5cc.png', '2025-05-19 14:32:59'),
(5, 17, '682b415c1ed95.png', '2025-05-19 14:34:04');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suggestions`
--

INSERT INTO `suggestions` (`id`, `name`, `email`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Oluwatobi Obafemi', 'tobyfemi55@gmail.com', 'Hello guys I know this website is farely new but could you add some games', 'pending', '2025-05-19 11:14:30', '2025-05-19 11:14:30');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `visit_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_visits`
--

INSERT INTO `user_visits` (`id`, `visit_time`, `session_id`, `ip_address`, `user_agent`) VALUES
(1, '2025-05-15 14:06:28', '', NULL, NULL),
(2, '2025-05-15 14:06:31', '', NULL, NULL),
(3, '2025-05-15 14:07:05', '', NULL, NULL),
(4, '2025-05-15 14:07:08', '', NULL, NULL),
(5, '2025-05-15 14:07:11', '', NULL, NULL),
(6, '2025-05-15 14:14:11', '', NULL, NULL),
(7, '2025-05-15 14:15:25', '', NULL, NULL),
(8, '2025-05-19 07:54:29', '', NULL, NULL),
(9, '2025-05-19 07:55:27', '', NULL, NULL),
(10, '2025-05-19 08:06:14', '', NULL, NULL),
(11, '2025-05-19 08:16:12', '', NULL, NULL),
(12, '2025-05-19 08:16:13', '', NULL, NULL),
(13, '2025-05-19 08:19:51', '', NULL, NULL),
(14, '2025-05-19 08:19:55', '', NULL, NULL),
(15, '2025-05-19 08:20:01', '', NULL, NULL),
(16, '2025-05-19 08:20:08', '', NULL, NULL),
(17, '2025-05-19 08:29:20', '', NULL, NULL),
(18, '2025-05-19 08:32:48', '', NULL, NULL),
(19, '2025-05-19 08:35:59', '', NULL, NULL),
(20, '2025-05-19 11:15:13', '', NULL, NULL),
(21, '2025-05-19 11:18:18', '', NULL, NULL),
(22, '2025-05-19 11:18:40', '', NULL, NULL),
(23, '2025-05-19 11:23:07', '', NULL, NULL),
(24, '2025-05-19 11:23:12', '', NULL, NULL),
(25, '2025-05-19 11:23:14', '', NULL, NULL),
(26, '2025-05-19 11:23:24', '', NULL, NULL),
(27, '2025-05-19 11:23:29', '', NULL, NULL),
(28, '2025-05-19 11:23:32', '', NULL, NULL),
(29, '2025-05-19 11:23:34', '', NULL, NULL),
(30, '2025-05-19 11:25:10', '', NULL, NULL),
(31, '2025-05-19 11:25:12', '', NULL, NULL),
(32, '2025-05-19 11:25:15', '', NULL, NULL),
(33, '2025-05-19 11:29:31', '', NULL, NULL),
(34, '2025-05-19 11:29:33', '', NULL, NULL),
(35, '2025-05-19 11:33:44', '', NULL, NULL),
(36, '2025-05-19 11:33:47', '', NULL, NULL),
(37, '2025-05-19 11:33:48', '', NULL, NULL),
(38, '2025-05-19 11:34:20', '', NULL, NULL),
(39, '2025-05-19 11:34:26', '', NULL, NULL),
(40, '2025-05-19 11:54:13', 'bs6lis0v98q2rdc0kt8qo4qsmm', '102.90.101.49', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36'),
(41, '2025-05-19 11:54:52', 'jecgumnhhjuqmc8gsb54nuv357', '102.90.101.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36'),
(42, '2025-05-19 12:03:13', 'jecgumnhhjuqmc8gsb54nuv357', '102.90.101.49', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36'),
(43, '2025-05-19 12:14:09', 'q77c7tt9vaik1dm7tsvk0n5ikm', '102.90.101.215', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1'),
(44, '2025-05-19 13:49:03', 'keciaf0oj4egm7bsu319r3dmv3', '197.210.79.20', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1'),
(45, '2025-05-19 18:44:08', '8l4htg9loe28lre4e4af5mopi1', '102.90.103.4', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Mobile Safari/537.36');

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
  ADD UNIQUE KEY `unique_visit` (`session_id`,`visit_time`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
