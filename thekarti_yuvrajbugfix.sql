-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 192.168.0.100
-- Generation Time: Jul 14, 2025 at 11:34 AM
-- Server version: 8.0.41-32
-- PHP Version: 7.3.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thekarti_yuvrajbugfix`
--

-- --------------------------------------------------------

--
-- Table structure for table `bug_remarks`
--

CREATE TABLE `bug_remarks` (
  `id` int NOT NULL,
  `bug_id` int NOT NULL,
  `user_id` int NOT NULL,
  `remark` text COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bug_remarks`
--

INSERT INTO `bug_remarks` (`id`, `bug_id`, `user_id`, `remark`, `image`, `timestamp`) VALUES
(25, 26, 10, 'Rejection reason: still getting N/A', NULL, '2025-07-14 05:33:05'),
(26, 30, 17, 'I have assigned the bug', NULL, '2025-07-14 07:01:50'),
(27, 30, 18, 'okay i will get back to you', NULL, '2025-07-14 07:03:06'),
(28, 30, 18, 'i have fixed the bug please check', NULL, '2025-07-14 07:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `bug_status_logs`
--

CREATE TABLE `bug_status_logs` (
  `id` int NOT NULL,
  `bug_id` int NOT NULL,
  `status` enum('pending','in_progress','fixed','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL,
  `updated_by` int NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bug_status_logs`
--

INSERT INTO `bug_status_logs` (`id`, `bug_id`, `status`, `updated_by`, `timestamp`) VALUES
(1, 26, 'pending', 10, '2025-07-12 12:39:23'),
(2, 27, 'pending', 10, '2025-07-12 13:00:26'),
(4, 27, 'in_progress', 12, '2025-07-14 05:12:14'),
(5, 27, 'fixed', 12, '2025-07-14 05:12:30'),
(6, 26, 'in_progress', 12, '2025-07-14 05:12:37'),
(7, 26, 'fixed', 12, '2025-07-14 05:12:39'),
(8, 27, 'approved', 10, '2025-07-14 05:30:01'),
(9, 26, 'rejected', 10, '2025-07-14 05:33:05'),
(10, 29, 'pending', 11, '2025-07-14 06:39:06'),
(11, 30, 'pending', 17, '2025-07-14 07:00:22'),
(12, 30, 'in_progress', 18, '2025-07-14 07:04:10'),
(13, 30, 'fixed', 18, '2025-07-14 07:06:44'),
(14, 30, 'approved', 17, '2025-07-14 07:08:53'),
(15, 29, 'in_progress', 12, '2025-07-14 08:07:15'),
(16, 29, 'fixed', 12, '2025-07-14 08:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `bug_tickets`
--

CREATE TABLE `bug_tickets` (
  `id` int NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `priority` enum('P1','P2','P3','P4') COLLATE utf8mb4_general_ci NOT NULL,
  `visible_impact` text COLLATE utf8mb4_general_ci,
  `screenshot` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_dev_id` int DEFAULT NULL,
  `created_by` int NOT NULL,
  `status` enum('pending','in_progress','fixed','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `module` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submodule` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bug_tickets`
--

INSERT INTO `bug_tickets` (`id`, `title`, `description`, `priority`, `visible_impact`, `screenshot`, `assigned_dev_id`, `created_by`, `status`, `created_at`, `updated_at`, `module`, `submodule`, `project_id`) VALUES
(26, 'showing N/A in approval details :', 'In approver side , In Approval >> advance >> \"recieved amount by N/A\" :\"N/A\"', 'P4', 'N/A', '1752323963_bug!_na.png', 12, 10, 'rejected', '2025-07-12 12:39:23', '2025-07-14 05:33:05', 'Approval', 'Advance', 7),
(27, 'paid by self and date freeze issue', 'TA/DA >> local >> Expense plan >> miscellaneuos Expense >> paid by self is not default and date is freezed , unfreeze it', 'P2', '', '1752325226_paidby_error.png', 12, 10, 'approved', '2025-07-12 13:00:26', '2025-07-14 05:30:01', 'Local', 'Expense Plan', 7),
(29, 'paid by - self issue in a miscellaneous expense', 'when user add the expense in a miscellaneous expense-paid by self field is blank.', 'P3', '', '1752475146_Screenshot_2025-07-14-11-58-03-12_7d5094cd92425ec3cff52c3eeaec67de.jpg', 12, 11, 'fixed', '2025-07-14 06:39:06', '2025-07-14 08:07:17', 'TA/DA', 'Misc Expense', 7),
(30, 'Not able to create user', 'not able to create user.', 'P1', '', '1752476422_protocol layering.png', 18, 17, 'approved', '2025-07-14 07:00:22', '2025-07-14 07:08:53', 'User Management', 'User Profile', 8);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `created_at`, `updated_at`) VALUES
(6, 'FixHR', '2025-07-11 12:10:59', '2025-07-11 12:10:59'),
(7, 'lipl', '2025-07-12 12:58:01', '2025-07-12 12:58:01'),
(8, 'dev.fixhr.app', '2025-07-14 05:26:37', '2025-07-14 05:26:37'),
(9, 'web.fixhr.app', '2025-07-14 05:26:56', '2025-07-14 05:26:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('tester','developer','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'tester',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(10, 'yuvraj singh', 'yuvraj@gmail.com', '$2y$10$FvKae.wyLVBwV2FadkH5yuQWJtAe4s/KOJrAsdG7EWWvj7bB5HpaG', 'tester', '2025-07-12 11:47:51', '2025-07-12 11:48:57'),
(11, 'Durgadas', 'Durgadas@gmail.com', '$2y$10$7NnqYVwaBZ48gez.su4H0uJ/hqIpTzNyxDJ6s8LiAe2CIiiuUuM7e', 'tester', '2025-07-12 11:48:35', '2025-07-12 11:48:35'),
(12, 'Manish', 'manish@gmail.com', '$2y$10$zLnlR3XtfJSbBPphph/20.wR5GC6Fpgs425kkQsr0XfJwJ4Ri1tkq', 'developer', '2025-07-12 11:49:30', '2025-07-12 11:49:30'),
(13, 'karan', 'karan@gmail.com', '$2y$10$B52DkgFLt55sJtJF8tubg.EBYDqtXfOHD2YiSofghSucY8rEBJ6v6', 'developer', '2025-07-12 11:49:44', '2025-07-12 11:49:44'),
(14, 'gajendra', 'gajendra@gmail.com', '$2y$10$KcWYHix/8ID4hLtw/KatLeqvA8rr6aa.xXeJs5o.saI5dhSo3CT.S', 'developer', '2025-07-12 11:49:59', '2025-07-12 11:49:59'),
(15, 'Admin', 'admin@admin.com', '$2y$10$swEZ3Sj2gtdKyIAdEYaEZOD7QE3qI76fabJdDM6cEfyuKuLrfWZlS', 'admin', '2025-07-12 11:50:40', '2025-07-12 11:50:54'),
(16, 'Vikas Yadav', '245yadavjii@gmail.com', '$2y$10$1wnEhBVog9BbRt0VASn48uJMl2ka6LNB.4SaqwSWYRCnn1.jwU9tC', 'tester', '2025-07-12 14:09:37', '2025-07-12 14:09:37'),
(17, 'Chetana', 'tester0607@yahoo.com', '$2y$10$uEThmZMWPTkucq7AcqVQeu1mdHRfLIAhmXOa30eWGxLh0aB1826I6', 'tester', '2025-07-14 06:50:50', '2025-07-14 06:50:50'),
(18, 'demodev', 'demo@gmail.com', '$2y$10$UBSMjXGovLCR40fM6XLTSONN126gO38C4ano6pTAzNLFXqWvI4qKm', 'developer', '2025-07-14 06:57:59', '2025-07-14 06:57:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bug_remarks`
--
ALTER TABLE `bug_remarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bug_id` (`bug_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bug_status_logs`
--
ALTER TABLE `bug_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bug_id` (`bug_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `bug_tickets`
--
ALTER TABLE `bug_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_dev_id` (`assigned_dev_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `bug_tickets_ibfk_3` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bug_remarks`
--
ALTER TABLE `bug_remarks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `bug_status_logs`
--
ALTER TABLE `bug_status_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `bug_tickets`
--
ALTER TABLE `bug_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bug_remarks`
--
ALTER TABLE `bug_remarks`
  ADD CONSTRAINT `bug_remarks_ibfk_1` FOREIGN KEY (`bug_id`) REFERENCES `bug_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bug_remarks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bug_status_logs`
--
ALTER TABLE `bug_status_logs`
  ADD CONSTRAINT `bug_status_logs_ibfk_1` FOREIGN KEY (`bug_id`) REFERENCES `bug_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bug_status_logs_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bug_tickets`
--
ALTER TABLE `bug_tickets`
  ADD CONSTRAINT `bug_tickets_ibfk_1` FOREIGN KEY (`assigned_dev_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bug_tickets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bug_tickets_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
