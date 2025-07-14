-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2025 at 09:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project_changes`
--

-- --------------------------------------------------------

--
-- Table structure for table `changes`
--

CREATE TABLE `changes` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `submodule_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `created_at`) VALUES
(1, 'ABC Corporation', '2025-07-14 18:34:47'),
(2, 'XYZ Industries', '2025-07-14 18:34:47'),
(3, 'Tech Solutions Inc', '2025-07-14 18:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `meeting_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `name`, `project_id`, `created_at`) VALUES
(1, 'User Authentication', 1, '2025-07-14 18:34:47'),
(2, 'Product Management', 1, '2025-07-14 18:34:47'),
(3, 'Payment Gateway', 1, '2025-07-14 18:34:47'),
(4, 'User Interface', 2, '2025-07-14 18:34:47'),
(5, 'Database', 3, '2025-07-14 18:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `created_at`) VALUES
(1, 'FixHr', '2025-07-14 18:34:47'),
(2, 'Android : lipl', '2025-07-14 18:34:47'),
(3, 'LIPL', '2025-07-14 18:34:47');

-- --------------------------------------------------------

--
-- Table structure for table `submodules`
--

CREATE TABLE `submodules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submodules`
--

INSERT INTO `submodules` (`id`, `name`, `module_id`, `created_at`) VALUES
(1, 'Login System', 1, '2025-07-14 18:34:47'),
(2, 'Registration', 1, '2025-07-14 18:34:47'),
(3, 'Product Catalog', 2, '2025-07-14 18:34:47'),
(4, 'Shopping Cart', 2, '2025-07-14 18:34:47'),
(5, 'Stripe Integration', 3, '2025-07-14 18:34:47'),
(6, 'PayPal Integration', 3, '2025-07-14 18:34:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `changes`
--
ALTER TABLE `changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meeting_id` (`meeting_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `submodule_id` (`submodule_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `submodules`
--
ALTER TABLE `submodules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `changes`
--
ALTER TABLE `changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submodules`
--
ALTER TABLE `submodules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `changes`
--
ALTER TABLE `changes`
  ADD CONSTRAINT `changes_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `changes_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `changes_ibfk_3` FOREIGN KEY (`submodule_id`) REFERENCES `submodules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meetings_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submodules`
--
ALTER TABLE `submodules`
  ADD CONSTRAINT `submodules_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
