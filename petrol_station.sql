-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 04:19 PM
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
-- Database: `petrol_station`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 6, 'register', 'New user registered', '::1', '2026-03-24 12:15:33'),
(2, 6, 'login', 'User logged in', '::1', '2026-03-24 12:16:02'),
(3, 6, 'add_reading', 'Added morning reading for pump ID: 1', '::1', '2026-03-24 12:36:47'),
(4, 6, 'add_reading', 'Added evening reading for pump ID: 1', '::1', '2026-03-24 12:37:28'),
(5, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-24 12:39:03'),
(6, 6, 'tank_refill', 'Refilled tank ID: 1 with 45 litres', '::1', '2026-03-24 12:41:39'),
(7, 6, 'add_reading', 'Added morning reading for pump ID: 2', '::1', '2026-03-24 12:48:11'),
(8, 7, 'register', 'New user registered', '::1', '2026-03-24 12:51:26'),
(9, 8, 'register', 'New user registered', '::1', '2026-03-24 12:53:36'),
(10, 8, 'login', 'User logged in', '::1', '2026-03-24 12:53:57'),
(11, 8, 'add_expense', 'Added expense: cash - 5000', '::1', '2026-03-24 12:55:41'),
(12, 8, 'add_office_cost', 'Added office cost: salary - 2000', '::1', '2026-03-24 12:57:08'),
(13, 6, 'logout', 'User logged out', '::1', '2026-03-24 13:18:08'),
(14, 8, 'login', 'User logged in', '::1', '2026-03-24 13:18:33'),
(15, 8, 'logout', 'User logged out', '::1', '2026-03-24 13:24:46'),
(16, 7, 'login', 'User logged in', '::1', '2026-03-24 13:25:19'),
(17, 7, 'logout', 'User logged out', '::1', '2026-03-24 13:44:11'),
(18, 6, 'login', 'User logged in', '::1', '2026-03-24 13:44:33'),
(19, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-24 13:46:09'),
(20, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-24 13:46:34'),
(21, 6, 'tank_refill', 'Refilled tank ID: 1 with 5 litres', '::1', '2026-03-24 13:48:17'),
(22, 6, 'logout', 'User logged out', '::1', '2026-03-24 13:49:52'),
(23, 8, 'login', 'User logged in', '::1', '2026-03-24 13:50:08'),
(24, 8, 'logout', 'User logged out', '::1', '2026-03-24 14:06:21'),
(25, 6, 'login', 'User logged in', '::1', '2026-03-24 14:06:34'),
(26, 6, 'add_reading', 'Added evening reading for pump ID: 2', '::1', '2026-03-24 14:17:47'),
(27, 6, 'create_pump', 'Created pump: 1', '::1', '2026-03-24 14:28:59'),
(28, 6, 'login', 'User logged in', '192.168.43.66', '2026-03-24 14:53:46'),
(29, 6, 'logout', 'User logged out', '192.168.43.66', '2026-03-24 15:09:32'),
(30, 8, 'login', 'User logged in', '192.168.43.66', '2026-03-24 15:09:56'),
(31, 1, 'logout', 'User logged out', '::1', '2026-03-25 08:19:50'),
(32, 6, 'login', 'User logged in', '::1', '2026-03-25 08:20:21'),
(33, 6, 'logout', 'User logged out', '::1', '2026-03-25 08:22:58'),
(34, 9, 'register', 'New user registered', '::1', '2026-03-25 08:24:11'),
(35, 9, 'login', 'User logged in', '::1', '2026-03-25 08:24:31'),
(36, 9, 'logout', 'User logged out', '::1', '2026-03-25 08:25:14'),
(37, 6, 'login', 'User logged in', '::1', '2026-03-25 16:16:41'),
(38, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-25 16:20:00'),
(39, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-25 16:20:10'),
(40, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-25 16:21:31'),
(41, 6, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-03-25 16:21:45'),
(42, 6, 'add_reading', 'Added morning reading for pump ID: 1', '::1', '2026-03-25 16:22:38'),
(43, 6, 'add_reading', 'Added evening reading for pump ID: 1', '::1', '2026-03-25 16:23:30'),
(44, 6, 'add_reading', 'Added morning reading for pump ID: 3', '::1', '2026-03-25 16:26:11'),
(45, 6, 'add_reading', 'Added evening reading for pump ID: 3', '::1', '2026-03-25 16:26:58'),
(46, 6, 'update_price', 'Updated fuel price to: 7800', '::1', '2026-03-25 16:29:55'),
(47, 6, 'tank_refill', 'Refilled tank ID: 1 with 34444444 litres', '::1', '2026-03-25 16:58:58'),
(48, 10, 'register', 'New user registered', '::1', '2026-03-25 17:16:46'),
(49, 10, 'login', 'User logged in', '::1', '2026-03-25 17:17:01'),
(50, 6, 'login', 'User logged in', '192.168.137.29', '2026-03-25 20:13:54'),
(51, 6, 'add_reading', 'Added morning reading for pump ID: 2', '::1', '2026-03-25 21:05:36'),
(52, 6, 'add_reading', 'Added evening reading for pump ID: 2', '::1', '2026-03-25 21:06:13'),
(53, 6, 'add_reading', 'Added morning reading for pump ID: 6', '::1', '2026-03-25 21:47:21'),
(54, 6, 'add_reading', 'Added evening reading for pump ID: 6', '::1', '2026-03-25 21:48:28'),
(55, 6, 'create_pump', 'Created pump: tttt', '::1', '2026-03-25 21:56:21'),
(56, 6, 'create_tank', 'Created tank: 11', '::1', '2026-03-25 22:10:55'),
(57, 6, 'add_reading', 'Added morning reading for pump ID: 4 - Sold 10 litres', '::1', '2026-03-25 22:15:37'),
(58, 6, 'logout', 'User logged out', '::1', '2026-03-25 22:35:47'),
(59, 9, 'login', 'User logged in', '::1', '2026-03-25 22:36:02'),
(60, 9, 'logout', 'User logged out', '::1', '2026-03-25 22:44:09'),
(61, 6, 'login', 'User logged in', '::1', '2026-03-25 22:44:41'),
(62, 6, 'logout', 'User logged out', '::1', '2026-03-25 23:35:19'),
(63, 9, 'login', 'User logged in', '::1', '2026-03-25 23:35:30'),
(64, 9, 'logout', 'User logged out', '::1', '2026-03-25 23:35:46'),
(65, 6, 'login', 'User logged in', '::1', '2026-03-25 23:36:05'),
(66, 6, 'logout', 'User logged out', '::1', '2026-03-27 12:39:32'),
(67, 9, 'login', 'User logged in', '::1', '2026-03-27 12:39:42'),
(68, 9, 'logout', 'User logged out', '::1', '2026-03-27 12:43:37'),
(69, 6, 'login', 'User logged in', '::1', '2026-03-27 12:43:48'),
(70, 6, 'login', 'User logged in', '::1', '2026-03-27 12:47:22'),
(71, 6, 'add_reading', 'Added morning opening reading for pump ID: 6', '::1', '2026-03-27 13:23:24'),
(72, 6, 'add_reading', 'Added morning closing reading for pump ID: 6', '::1', '2026-03-27 13:23:50'),
(73, 6, 'add_reading', 'Added evening opening reading for pump ID: 6', '::1', '2026-03-27 13:24:41'),
(74, 6, 'add_reading', 'Added evening closing reading for pump ID: 6', '::1', '2026-03-27 13:25:57'),
(75, 6, 'update_price', 'Updated fuel price to: 1', '::1', '2026-03-27 13:30:15'),
(76, 6, 'add_reading', 'Added morning opening reading for pump ID: 1', '::1', '2026-03-27 13:31:34'),
(77, 6, 'add_reading', 'Added morning closing reading for pump ID: 1', '::1', '2026-03-27 13:31:59'),
(78, 6, 'add_reading', 'Added evening opening reading for pump ID: 1', '::1', '2026-03-27 13:54:25'),
(79, 6, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 955 litres', '::1', '2026-03-27 13:55:05'),
(80, 6, 'add_reading', 'Added evening closing reading for pump ID: 1', '::1', '2026-03-27 13:55:05'),
(81, 6, 'add_reading', 'Added morning opening reading for pump ID: 4', '::1', '2026-03-27 13:56:25'),
(82, 6, 'tank_volume_decrease', 'Decreased tank ID: 3 volume by 831 litres', '::1', '2026-03-27 13:57:53'),
(83, 6, 'add_reading', 'Added morning closing reading for pump ID: 4', '::1', '2026-03-27 13:57:53'),
(84, 6, 'add_reading', 'Added evening opening reading for pump ID: 4', '::1', '2026-03-27 14:14:31'),
(85, 6, 'tank_volume_decrease', 'Decreased tank ID: 3 volume by 731 litres', '::1', '2026-03-27 14:15:10'),
(86, 6, 'add_reading', 'Added evening closing reading for pump ID: 4', '::1', '2026-03-27 14:15:10'),
(87, 6, 'add_reading', 'Added morning opening reading for pump ID: 3', '::1', '2026-03-27 14:26:12'),
(88, 6, 'tank_volume_decrease', 'Decreased tank ID: 2 volume by 912 litres', '::1', '2026-03-27 14:27:10'),
(89, 6, 'add_reading', 'Added morning closing reading for pump ID: 3', '::1', '2026-03-27 14:27:10'),
(90, 6, 'add_reading', 'Added morning opening reading for pump ID: 8', '::1', '2026-03-27 14:29:20'),
(91, 6, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 34412336 litres', '::1', '2026-03-27 14:30:41'),
(92, 6, 'add_reading', 'Added morning closing reading for pump ID: 8', '::1', '2026-03-27 14:30:42'),
(93, 6, 'add_reading', 'Added evening opening reading for pump ID: 8', '::1', '2026-03-27 14:33:22'),
(94, 6, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 8732 litres', '::1', '2026-03-27 14:33:42'),
(95, 6, 'add_reading', 'Added evening closing reading for pump ID: 8', '::1', '2026-03-27 14:33:42'),
(96, 6, 'tank_refill', 'Refilled tank ID: 5 with 334 litres', '::1', '2026-03-27 15:34:35'),
(97, 6, 'logout', 'User logged out', '::1', '2026-03-27 15:38:21'),
(98, 6, 'login', 'User logged in', '::1', '2026-03-27 15:39:14'),
(99, 6, 'logout', 'User logged out', '::1', '2026-03-27 15:39:32'),
(100, 11, 'register', 'New user registered', '::1', '2026-03-27 15:41:06'),
(101, 11, 'login', 'User logged in', '::1', '2026-03-27 15:41:20'),
(102, 6, 'login', 'User logged in', '::1', '2026-05-24 16:57:33'),
(103, 6, 'logout', 'User logged out', '::1', '2026-05-24 17:07:22'),
(104, 6, 'login', 'User logged in', '::1', '2026-05-24 17:07:45'),
(105, 6, 'logout', 'User logged out', '::1', '2026-05-24 17:09:14'),
(106, 8, 'login', 'User logged in', '::1', '2026-05-24 17:09:29'),
(107, 8, 'logout', 'User logged out', '::1', '2026-05-24 17:16:41'),
(108, 6, 'login', 'User logged in', '::1', '2026-05-24 17:16:55'),
(109, 6, 'logout', 'User logged out', '::1', '2026-05-24 17:17:05'),
(110, 6, 'login', 'User logged in', '::1', '2026-05-25 08:06:25'),
(111, 7, 'login', 'User logged in', '::1', '2026-05-25 08:14:16'),
(112, 7, 'create_user', 'Created user: mlinzi with role: security', '::1', '2026-05-25 08:18:01'),
(113, 7, 'create_user', 'Created user: mhudumu with role: pump_attendant', '::1', '2026-05-25 08:24:40'),
(114, 7, 'logout', 'User logged out', '::1', '2026-05-25 08:25:58'),
(115, 14, 'login', 'User logged in', '::1', '2026-05-25 08:26:11'),
(116, 14, 'logout', 'User logged out', '::1', '2026-05-25 08:27:05'),
(117, 6, 'login', 'User logged in', '::1', '2026-05-25 08:27:17'),
(118, 6, 'add_reading', 'Added morning opening reading for pump ID: 1', '::1', '2026-05-25 08:41:39'),
(119, 6, 'add_reading', 'Added evening closing reading for pump ID: 1', '::1', '2026-05-25 08:42:22'),
(120, 6, 'add_reading', 'Added morning opening reading for pump ID: 2', '::1', '2026-05-25 08:43:17'),
(121, 6, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 67 litres', '::1', '2026-05-25 08:43:57'),
(122, 6, 'add_reading', 'Added morning closing reading for pump ID: 2', '::1', '2026-05-25 08:43:57'),
(123, 6, 'add_reading', 'Added evening opening reading for pump ID: 2', '::1', '2026-05-25 08:45:41'),
(124, 6, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 4833 litres', '::1', '2026-05-25 08:46:32'),
(125, 6, 'add_reading', 'Added evening closing reading for pump ID: 2', '::1', '2026-05-25 08:46:32'),
(126, 6, 'create_tank', 'Created tank: TAN9999', '::1', '2026-05-25 09:04:44'),
(127, 6, 'logout', 'User logged out', '::1', '2026-05-25 09:18:52'),
(128, 6, 'login', 'User logged in', '::1', '2026-05-25 10:52:12'),
(129, 6, 'logout', 'User logged out', '::1', '2026-05-25 11:03:06'),
(130, 6, 'login', 'User logged in', '::1', '2026-05-25 14:14:55'),
(131, 6, 'logout', 'User logged out', '::1', '2026-05-25 14:22:21'),
(132, 6, 'login', 'User logged in', '::1', '2026-05-25 14:23:23'),
(133, 6, 'update_price', 'Updated fuel price to: 4000', '::1', '2026-05-25 14:27:13'),
(134, 6, 'add_reading', 'Added morning opening reading for pump ID: 3', '::1', '2026-05-25 14:30:27'),
(135, 6, 'tank_volume_decrease', 'Decreased tank ID: 2 volume by 250 litres', '::1', '2026-05-25 14:31:32'),
(136, 6, 'add_reading', 'Added morning closing reading for pump ID: 3', '::1', '2026-05-25 14:31:32'),
(137, 6, 'tank_volume_decrease', 'Decreased tank ID: 2 volume by 250.00 litres', '::1', '2026-05-25 14:35:11'),
(138, 6, 'record_sales', 'Recorded sales for pump ID: 3', '::1', '2026-05-25 14:35:12'),
(139, 6, 'tank_refill', 'Refilled tank ID: 2 with 4556 litres', '::1', '2026-05-25 14:42:55'),
(140, 6, 'assign_role', 'Assigned role: manager to user ID: 3', '::1', '2026-05-25 14:44:25'),
(141, 6, 'logout', 'User logged out', '::1', '2026-05-25 14:46:33'),
(142, 7, 'login', 'User logged in', '::1', '2026-05-25 14:48:04'),
(143, 7, 'logout', 'User logged out', '::1', '2026-05-25 14:55:36'),
(144, 6, 'login', 'User logged in', '::1', '2026-05-25 14:56:31'),
(145, 6, 'logout', 'User logged out', '::1', '2026-05-25 14:57:03'),
(146, 8, 'login', 'User logged in', '::1', '2026-05-25 14:57:17'),
(147, 8, 'add_expense', 'Added expense: wages - 56746554345', '::1', '2026-05-25 14:59:33'),
(148, 8, 'add_expense', 'Added expense: wages - 1000', '::1', '2026-05-25 15:00:28'),
(149, 8, 'add_expense', 'Added expense: wages - 1357', '::1', '2026-05-25 15:01:02'),
(150, 8, 'logout', 'User logged out', '::1', '2026-05-25 15:04:09'),
(151, 7, 'login', 'User logged in', '::1', '2026-05-25 15:04:26'),
(152, 7, 'create_user', 'Created user: kulwa with role: security', '::1', '2026-05-25 15:06:27'),
(153, 7, 'logout', 'User logged out', '::1', '2026-05-25 15:06:44'),
(154, 7, 'login', 'User logged in', '::1', '2026-05-25 15:07:39'),
(155, 7, 'create_user', 'Created user: fish with role: security', '::1', '2026-05-25 15:09:40'),
(156, 7, 'logout', 'User logged out', '::1', '2026-05-25 15:09:53'),
(157, 17, 'login', 'User logged in', '::1', '2026-05-25 15:10:09'),
(158, 17, 'logout', 'User logged out', '::1', '2026-05-25 15:47:37'),
(159, 6, 'login', 'User logged in', '::1', '2026-05-25 15:47:51'),
(160, 6, 'logout', 'User logged out', '::1', '2026-05-25 15:49:09'),
(161, 7, 'login', 'User logged in', '::1', '2026-05-25 15:49:22'),
(162, 7, 'delete_user', 'Deactivated user: kaka kiki (ID: 6)', '::1', '2026-05-25 16:35:14'),
(163, 7, 'delete_user', 'Deactivated user: kaka kiki (ID: 6)', '::1', '2026-05-25 16:35:23'),
(164, 7, 'delete_user', 'Deactivated user: kaka kiki (ID: 6)', '::1', '2026-05-25 16:35:28'),
(165, 7, 'assign_role', 'Assigned role: security to user ID: 1', '::1', '2026-05-25 16:36:09'),
(166, 7, 'assign_role', 'Assigned role: security to user ID: 1', '::1', '2026-05-25 16:36:24'),
(167, 7, 'delete_user', 'Deactivated user: yyyyyyyy (ID: 10)', '::1', '2026-05-25 16:36:40'),
(168, 7, 'assign_role', 'Assigned role: security to user ID: 1', '::1', '2026-05-25 16:37:06'),
(169, 7, 'assign_role', 'Assigned role: pump_attendant to user ID: 17', '::1', '2026-05-25 16:37:47'),
(170, 7, 'logout', 'User logged out', '::1', '2026-05-25 16:38:02'),
(171, 17, 'login', 'User logged in', '::1', '2026-05-25 16:38:28'),
(172, 17, 'logout', 'User logged out', '::1', '2026-05-25 16:43:10'),
(173, 7, 'login', 'User logged in', '::1', '2026-05-25 16:43:22'),
(174, 7, 'delete_user', 'Deactivated user: yyyyyyyy (ID: 10)', '::1', '2026-05-25 16:43:43'),
(175, 7, 'assign_role', 'Assigned role: accountant to user ID: 1', '::1', '2026-05-25 16:44:18'),
(176, 7, 'assign_role', 'Assigned role: accountant to user ID: 1', '::1', '2026-05-25 16:44:22'),
(177, 7, 'assign_role', 'Assigned role: accountant to user ID: 1', '::1', '2026-05-25 16:44:27'),
(178, 7, 'delete_user', 'Deactivated user: hiu fffj (ID: 11)', '::1', '2026-05-25 16:44:53'),
(179, 7, 'delete_user', 'Deactivated user: John Chief (ID: 1)', '::1', '2026-05-25 16:45:05'),
(180, 7, 'logout', 'User logged out', '::1', '2026-05-25 16:47:07'),
(181, 17, 'login', 'User logged in', '::1', '2026-05-25 16:48:15'),
(182, 17, 'logout', 'User logged out', '::1', '2026-05-25 16:48:20'),
(183, 7, 'login', 'User logged in', '::1', '2026-05-25 16:48:34'),
(184, 7, 'assign_role', 'Assigned role: pump_attendant to user ID: 12', '::1', '2026-05-25 16:49:02'),
(185, 7, 'assign_role', 'Assigned role: accountant to user ID: 4', '::1', '2026-05-25 16:49:33'),
(186, 7, 'assign_role', 'Assigned role: accountant to user ID: 4', '::1', '2026-05-25 16:55:24'),
(187, 7, 'assign_role', 'Assigned role: pump_attendant to user ID: 10', '::1', '2026-05-25 16:58:06'),
(188, 7, 'assign_role', 'Assigned role: accountant to user ID: 3', '::1', '2026-05-25 16:58:30'),
(189, 7, 'assign_role', 'Assigned role: pump_attendant to user ID: 15', '::1', '2026-05-25 16:59:08'),
(190, 7, 'assign_role', 'Assigned role: accountant to user ID: 6', '::1', '2026-05-25 16:59:49'),
(191, 7, 'assign_role', 'Assigned role: manager to user ID: 17', '::1', '2026-05-25 17:00:16'),
(192, 7, 'logout', 'User logged out', '::1', '2026-05-25 17:00:37'),
(193, 17, 'login', 'User logged in', '::1', '2026-05-25 17:00:49'),
(194, 17, 'logout', 'User logged out', '::1', '2026-05-25 21:10:59'),
(195, 7, 'login', 'User logged in', '::1', '2026-05-29 11:42:02'),
(196, 17, 'login', 'User logged in', '::1', '2026-06-01 08:58:27'),
(197, 17, 'login', 'User logged in', '::1', '2026-06-01 16:01:18'),
(198, 7, 'login', 'User logged in', '::1', '2026-06-09 05:27:04'),
(199, 18, 'register', 'New user registered', '::1', '2026-06-09 05:38:04'),
(200, 19, 'register', 'New user registered', '::1', '2026-06-09 05:39:22'),
(201, 7, 'logout', 'User logged out', '::1', '2026-06-09 05:48:30'),
(202, 19, 'login', 'User logged in', '::1', '2026-06-09 05:48:52'),
(203, 19, 'edit_profile', 'Updated profile for user ID: 8', '::1', '2026-06-09 06:05:15'),
(204, 19, 'logout', 'User logged out', '::1', '2026-06-09 06:05:43'),
(205, 8, 'login', 'User logged in', '::1', '2026-06-09 06:06:47'),
(206, 8, 'logout', 'User logged out', '::1', '2026-06-09 06:16:11'),
(207, 7, 'login', 'User logged in', '::1', '2026-06-09 06:16:25'),
(208, 7, 'delete_user', 'Deactivated user: john john (ID: 12)', '::1', '2026-06-09 06:36:20'),
(209, 17, 'login', 'User logged in', '::1', '2026-06-09 09:48:12'),
(210, 19, 'login', 'User logged in', '::1', '2026-06-10 04:49:57'),
(211, 19, 'update_price', 'Updated fuel price to: 500', '::1', '2026-06-10 09:10:36'),
(212, 19, 'logout', 'User logged out', '::1', '2026-06-10 09:16:37'),
(213, 7, 'login', 'User logged in', '::1', '2026-06-10 09:17:26'),
(214, 7, 'logout', 'User logged out', '::1', '2026-06-10 09:25:51'),
(215, 19, 'login', 'User logged in', '::1', '2026-06-10 09:26:29'),
(216, 19, 'add_reading', 'Added morning opening reading for pump ID: 1', '::1', '2026-06-10 09:26:53'),
(217, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 500 litres', '::1', '2026-06-10 09:28:37'),
(218, 19, 'add_reading', 'Added morning closing reading for pump ID: 1', '::1', '2026-06-10 09:28:37'),
(219, 19, 'logout', 'User logged out', '::1', '2026-06-10 09:30:16'),
(220, 7, 'login', 'User logged in', '::1', '2026-06-10 09:30:29'),
(221, 7, 'logout', 'User logged out', '::1', '2026-06-10 09:36:39'),
(222, 7, 'login', 'User logged in', '::1', '2026-06-10 09:36:56'),
(223, 7, 'assign_role', 'Assigned role: manager to user ID: 19', '::1', '2026-06-10 09:37:40'),
(224, 7, 'logout', 'User logged out', '::1', '2026-06-10 09:38:56'),
(225, 8, 'login', 'User logged in', '::1', '2026-06-10 09:40:36'),
(226, 8, 'logout', 'User logged out', '::1', '2026-06-10 09:40:54'),
(227, 19, 'login', 'User logged in', '::1', '2026-06-10 20:03:30'),
(228, 19, 'logout', 'User logged out', '::1', '2026-06-10 20:04:07'),
(229, 7, 'login', 'User logged in', '::1', '2026-06-10 20:04:21'),
(230, 19, 'login', 'User logged in', '::1', '2026-06-10 20:05:34'),
(231, 19, 'add_reading', 'Added morning opening reading for pump ID: 2', '::1', '2026-06-10 20:06:39'),
(232, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 432 litres', '::1', '2026-06-10 20:07:17'),
(233, 19, 'add_reading', 'Added morning closing reading for pump ID: 2', '::1', '2026-06-10 20:07:17'),
(234, 19, 'add_reading', 'Added evening opening reading for pump ID: 1', '::1', '2026-06-10 20:13:07'),
(235, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 722 litres', '::1', '2026-06-10 20:13:36'),
(236, 19, 'add_reading', 'Added evening closing reading for pump ID: 1', '::1', '2026-06-10 20:13:36'),
(237, 7, 'logout', 'User logged out', '::1', '2026-06-10 21:03:40'),
(238, 8, 'login', 'User logged in', '::1', '2026-06-10 21:03:54'),
(239, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 500.00 litres', '::1', '2026-06-10 21:07:22'),
(240, 19, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-06-10 21:07:22'),
(241, 8, 'add_expense', 'Added expense: Maintenance - 5000', '::1', '2026-06-10 21:11:06'),
(242, 8, 'add_office_cost', 'Added office cost: salary - 500000', '::1', '2026-06-10 21:15:17'),
(243, 8, 'logout', 'User logged out', '::1', '2026-06-10 21:21:46'),
(244, 7, 'login', 'User logged in', '::1', '2026-06-10 21:22:03'),
(245, 19, 'login', 'User logged in', '::1', '2026-06-11 06:12:38'),
(246, 19, 'tank_refill', 'Refilled tank ID: 1 with 1500 litres', '::1', '2026-06-11 06:15:26'),
(247, 19, 'tank_refill', 'Refilled tank ID: 1 with 2000 litres', '::1', '2026-06-11 06:20:31'),
(248, 7, 'create_user', 'Created user: yahya with role: manager', '::1', '2026-06-11 07:52:44'),
(249, 7, 'activate_user', 'Activated user: John Chief (ID: 1)', '::1', '2026-06-11 16:14:30'),
(250, 7, 'assign_role', 'Assigned role: top_manager to user ID: 11', '::1', '2026-06-11 16:15:18'),
(251, 7, 'assign_role', 'Assigned role: chief_manager to user ID: 11', '::1', '2026-06-11 16:15:43'),
(252, 7, 'delete_user', 'Deactivated user: John Chief (ID: 1)', '::1', '2026-06-11 16:16:15'),
(253, 7, 'activate_user', 'Activated user: John Chief (ID: 1)', '::1', '2026-06-11 16:17:12'),
(254, 7, 'activate_user', 'Activated user: John Chief (ID: 1)', '::1', '2026-06-11 16:33:00'),
(255, 19, 'login', 'User logged in', '::1', '2026-06-11 16:44:24'),
(256, 19, 'add_reading', 'Added morning opening reading for pump ID: 1', '::1', '2026-06-11 17:02:09'),
(257, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 4061 litres', '::1', '2026-06-11 17:02:59'),
(258, 19, 'add_reading', 'Added morning closing reading for pump ID: 1', '::1', '2026-06-11 17:02:59'),
(259, 7, 'logout', 'User logged out', '::1', '2026-06-11 17:38:22'),
(260, 19, 'login', 'User logged in', '::1', '2026-06-11 17:38:31'),
(261, 19, 'add_reading', 'Added evening opening reading for pump ID: 1', '::1', '2026-06-11 17:38:48'),
(262, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 6111 litres', '::1', '2026-06-11 17:39:14'),
(263, 19, 'add_reading', 'Added evening closing reading for pump ID: 1', '::1', '2026-06-11 17:39:14'),
(264, 19, 'tank_refill', 'Refilled tank ID: 1 with 5000 litres', '::1', '2026-06-11 18:09:21'),
(265, 19, 'create_tank', 'Created tank: TANK002', '::1', '2026-06-11 18:13:59'),
(266, 19, 'logout', 'User logged out', '::1', '2026-06-11 18:32:07'),
(267, 24, 'register', 'New user registered', '::1', '2026-06-11 18:34:01'),
(268, 7, 'login', 'User logged in', '::1', '2026-06-11 18:34:21'),
(269, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 4061.00 litres', '::1', '2026-06-11 18:37:46'),
(270, 19, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-06-11 18:37:46'),
(271, 19, 'logout', 'User logged out', '::1', '2026-06-11 19:04:28'),
(272, 7, 'login', 'User logged in', '::1', '2026-06-11 19:04:39'),
(273, 7, 'logout', 'User logged out', '::1', '2026-06-11 20:43:34'),
(274, 19, 'login', 'User logged in', '::1', '2026-06-11 20:43:46'),
(275, 19, 'record_sales', 'Recorded sales for pump ID: 1', '::1', '2026-06-11 21:25:39'),
(276, 19, 'tank_refill', 'Refilled tank ID: 1 with 5000 litres', '::1', '2026-06-12 05:19:22'),
(277, 19, 'tank_refill', 'Refilled tank ID: 1 with 6000 litres', '::1', '2026-06-12 05:20:25'),
(278, 7, 'logout', 'User logged out', '::1', '2026-06-12 05:20:46'),
(279, 8, 'login', 'User logged in', '::1', '2026-06-12 05:20:58'),
(280, 8, 'add_expense', 'Added expense: Debtors pay - 5000', '::1', '2026-06-12 05:24:50'),
(281, 8, 'logout', 'User logged out', '::1', '2026-06-12 05:33:10'),
(282, 19, 'login', 'User logged in', '::1', '2026-06-12 05:36:28'),
(283, 19, 'add_reading', 'Added morning opening reading for pump ID: 1', '::1', '2026-06-12 05:36:57'),
(284, 19, 'tank_volume_decrease', 'Decreased tank ID: 1 volume by 895 litres', '::1', '2026-06-12 05:37:28'),
(285, 19, 'add_reading', 'Added morning closing reading for pump ID: 1', '::1', '2026-06-12 05:37:28'),
(286, 19, 'add_reading', 'Added morning opening reading for pump ID: 3', '::1', '2026-06-12 05:38:13'),
(287, 19, 'tank_volume_decrease', 'Decreased tank ID: 8 volume by 888 litres', '::1', '2026-06-12 05:38:45'),
(288, 19, 'add_reading', 'Added morning closing reading for pump ID: 3', '::1', '2026-06-12 05:38:45'),
(289, 19, 'tank_refill', 'Refilled tank ID: 1 with 6000 litres', '::1', '2026-06-12 06:49:28'),
(290, 19, 'logout', 'User logged out', '::1', '2026-06-12 06:49:53'),
(291, 7, 'login', 'User logged in', '::1', '2026-06-12 06:50:02'),
(292, 19, 'add_reading', 'Added evening opening reading for pump ID: 3', '::1', '2026-06-12 06:58:44'),
(293, 19, 'tank_volume_decrease', 'Decreased tank ID: 8 volume by 975 litres', '::1', '2026-06-12 06:59:52'),
(294, 19, 'add_reading', 'Added evening closing reading for pump ID: 3', '::1', '2026-06-12 06:59:52'),
(295, 19, 'login', 'User logged in', '::1', '2026-06-19 14:03:26'),
(296, 7, 'login', 'User logged in', '::1', '2026-06-19 14:12:09');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_type`, `description`, `amount`, `expense_date`, `created_by`, `created_at`) VALUES
(1, 'cash', 'meal', 5000.00, '2026-03-24', 8, '2026-03-24 12:55:41'),
(2, 'wages', 'all', 9999999999.99, '2026-05-25', 8, '2026-05-25 14:59:33'),
(3, 'wages', 'fgh', 1000.00, '2026-05-25', 8, '2026-05-25 15:00:28'),
(4, 'wages', 'fjhgf', 1357.00, '2026-05-25', 8, '2026-05-25 15:01:02'),
(5, 'Maintenance', 'Furniture repair', 5000.00, '2026-06-10', 8, '2026-06-10 21:11:06'),
(6, 'Debtors pay', 'six debtors were payed', 5000.00, '2026-06-12', 8, '2026-06-12 05:24:50');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_prices`
--

CREATE TABLE `fuel_prices` (
  `id` int(11) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `price_per_litre` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_prices`
--

INSERT INTO `fuel_prices` (`id`, `fuel_type_id`, `price_per_litre`, `effective_date`, `created_by`, `created_at`) VALUES
(2, 2, 2990.00, '2026-03-24', 2, '2026-03-24 12:03:32'),
(7, 1, 4000.00, '2026-05-25', 6, '2026-05-25 14:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_types`
--

CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_types`
--

INSERT INTO `fuel_types` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Petrol', 'Regular Petrol (RON 95)', 1, '2026-03-24 12:03:32'),
(2, 'Diesel', 'Diesel Fuel', 1, '2026-03-24 12:03:32');

-- --------------------------------------------------------

--
-- Table structure for table `office_costs`
--

CREATE TABLE `office_costs` (
  `id` int(11) NOT NULL,
  `cost_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL COMMENT 'Employee ID if payment to staff',
  `payment_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `office_costs`
--

INSERT INTO `office_costs` (`id`, `cost_type`, `description`, `amount`, `recipient_id`, `payment_date`, `created_by`, `created_at`) VALUES
(1, 'salary', 'workers', 2000.00, 5, '2026-03-24', 8, '2026-03-24 12:57:08'),
(2, 'salary', 'paid to all workers', 500000.00, NULL, '2026-06-10', 8, '2026-06-10 21:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `pumps`
--

CREATE TABLE `pumps` (
  `id` int(11) NOT NULL,
  `pump_number` varchar(20) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `attendant_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pumps`
--

INSERT INTO `pumps` (`id`, `pump_number`, `fuel_type_id`, `attendant_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PUMP001', 1, NULL, 1, '2026-03-24 12:03:32', '2026-03-24 12:03:32'),
(3, 'PUMP002', 2, NULL, 1, '2026-03-24 12:03:32', '2026-06-12 06:55:48');

-- --------------------------------------------------------

--
-- Table structure for table `pump_readings`
--

CREATE TABLE `pump_readings` (
  `id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,
  `shift` enum('morning','evening') NOT NULL,
  `reading_type` enum('opening','closing') NOT NULL DEFAULT 'closing',
  `reading_date` date NOT NULL,
  `initial_reading` decimal(10,2) DEFAULT NULL,
  `final_reading` decimal(10,2) DEFAULT NULL,
  `meter_reading` decimal(10,2) NOT NULL COMMENT 'Current meter reading in litres',
  `previous_reading` decimal(10,2) DEFAULT NULL COMMENT 'Previous reading for comparison',
  `litres_sold` decimal(10,2) DEFAULT 0.00 COMMENT 'Calculated: current - previous',
  `income` decimal(12,2) DEFAULT 0.00 COMMENT 'Calculated: litres_sold * price',
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pump_readings`
--

INSERT INTO `pump_readings` (`id`, `pump_id`, `shift`, `reading_type`, `reading_date`, `initial_reading`, `final_reading`, `meter_reading`, `previous_reading`, `litres_sold`, `income`, `recorded_by`, `created_at`) VALUES
(1, 1, 'morning', 'opening', '2026-03-24', NULL, 367.00, 367.00, NULL, 0.00, 0.00, 6, '2026-03-24 12:36:47'),
(2, 1, 'evening', 'opening', '2026-03-24', NULL, 998.00, 998.00, NULL, 0.00, 0.00, 6, '2026-03-24 12:37:28'),
(4, 2, 'morning', 'opening', '2026-03-24', NULL, 6565.00, 6565.00, NULL, 0.00, 0.00, 6, '2026-03-24 12:48:11'),
(9, 2, 'evening', 'opening', '2026-03-24', NULL, 7889.00, 7889.00, 6565.00, 1324.00, 4303000.00, 6, '2026-03-24 14:17:47'),
(10, 1, 'morning', 'opening', '2026-03-25', NULL, 12.56, 12.56, 998.00, -985.44, 0.00, 6, '2026-03-25 16:22:38'),
(11, 1, 'evening', 'opening', '2026-03-25', NULL, 78.68, 78.68, 12.56, 66.12, 214890.00, 6, '2026-03-25 16:23:30'),
(12, 3, 'morning', 'opening', '2026-03-25', NULL, 700.00, 700.00, NULL, 0.00, 0.00, 6, '2026-03-25 16:26:11'),
(13, 3, 'evening', 'opening', '2026-03-25', NULL, 987.60, 987.60, 700.00, 287.60, 859924.00, 6, '2026-03-25 16:26:58'),
(15, 2, 'morning', 'opening', '2026-03-25', NULL, 34.00, 34.00, 7889.00, -7855.00, 0.00, 6, '2026-03-25 21:05:36'),
(16, 2, 'evening', 'opening', '2026-03-25', NULL, 78.00, 78.00, 34.00, 44.00, 143000.00, 6, '2026-03-25 21:06:13'),
(20, 6, 'morning', 'opening', '2026-03-25', 34.00, 44.00, 0.00, NULL, 10.00, 32500.00, 6, '2026-03-25 21:47:21'),
(21, 6, 'evening', 'opening', '2026-03-25', 44.00, 78.00, 0.00, 44.00, 34.00, 110500.00, 6, '2026-03-25 21:48:28'),
(22, 4, 'morning', 'opening', '2026-03-25', 89.00, 99.00, 0.00, NULL, 10.00, 35500.00, 6, '2026-03-25 22:15:37'),
(24, 6, 'morning', 'opening', '2026-03-27', NULL, NULL, 23.00, NULL, 0.00, 0.00, 6, '2026-03-27 13:23:24'),
(25, 6, 'morning', 'closing', '2026-03-27', NULL, NULL, 43.00, 23.00, 20.00, 65000.00, 6, '2026-03-27 13:23:50'),
(26, 6, 'evening', 'opening', '2026-03-27', NULL, NULL, 43.00, 43.00, 0.00, 0.00, 6, '2026-03-27 13:24:41'),
(27, 6, 'evening', 'closing', '2026-03-27', NULL, NULL, 68.00, 43.00, 25.00, 81250.00, 6, '2026-03-27 13:25:57'),
(29, 1, 'morning', 'opening', '2026-03-27', NULL, NULL, 10.00, NULL, 0.00, 0.00, 6, '2026-03-27 13:31:34'),
(30, 1, 'morning', 'closing', '2026-03-27', NULL, NULL, 45.00, 10.00, 35.00, 35.00, 6, '2026-03-27 13:31:59'),
(32, 1, 'evening', 'opening', '2026-03-27', NULL, NULL, 45.00, 45.00, 0.00, 0.00, 6, '2026-03-27 13:54:25'),
(33, 1, 'evening', 'closing', '2026-03-27', NULL, NULL, 1000.00, 45.00, 955.00, 955.00, 6, '2026-03-27 13:55:05'),
(34, 4, 'morning', 'opening', '2026-03-27', NULL, NULL, 23.00, NULL, 0.00, 0.00, 6, '2026-03-27 13:56:25'),
(36, 4, 'morning', 'closing', '2026-03-27', NULL, NULL, 854.00, 23.00, 831.00, 2950050.00, 6, '2026-03-27 13:57:53'),
(39, 4, 'evening', 'opening', '2026-03-27', NULL, NULL, 67.00, 854.00, 0.00, 0.00, 6, '2026-03-27 14:14:31'),
(40, 4, 'evening', 'closing', '2026-03-27', NULL, NULL, 798.00, 67.00, 731.00, 2595050.00, 6, '2026-03-27 14:15:10'),
(43, 3, 'morning', 'opening', '2026-03-27', NULL, NULL, 77.00, NULL, 0.00, 0.00, 6, '2026-03-27 14:26:12'),
(44, 3, 'morning', 'closing', '2026-03-27', NULL, NULL, 989.00, 77.00, 912.00, 7113600.00, 6, '2026-03-27 14:27:10'),
(45, 8, 'morning', 'opening', '2026-03-27', NULL, NULL, 898.00, NULL, 0.00, 0.00, 6, '2026-03-27 14:29:20'),
(46, 8, 'morning', 'closing', '2026-03-27', NULL, NULL, 34413234.00, 898.00, 34412336.00, 34412336.00, 6, '2026-03-27 14:30:41'),
(47, 8, 'evening', 'opening', '2026-03-27', NULL, NULL, 56.00, 34413234.00, 0.00, 0.00, 6, '2026-03-27 14:33:22'),
(48, 8, 'evening', 'closing', '2026-03-27', NULL, NULL, 8788.00, 56.00, 8732.00, 8732.00, 6, '2026-03-27 14:33:42'),
(50, 1, 'morning', 'opening', '2026-05-25', NULL, NULL, 577.00, 1000.00, 0.00, 0.00, 6, '2026-05-25 08:41:39'),
(51, 1, 'evening', 'closing', '2026-05-25', NULL, NULL, 999.00, NULL, 0.00, 0.00, 6, '2026-05-25 08:42:22'),
(52, 2, 'morning', 'opening', '2026-05-25', NULL, NULL, 100.00, NULL, 0.00, 0.00, 6, '2026-05-25 08:43:17'),
(53, 2, 'morning', 'closing', '2026-05-25', NULL, NULL, 167.00, 100.00, 67.00, 67.00, 6, '2026-05-25 08:43:57'),
(54, 2, 'evening', 'opening', '2026-05-25', NULL, NULL, 167.00, 167.00, 0.00, 0.00, 6, '2026-05-25 08:45:41'),
(55, 2, 'evening', 'closing', '2026-05-25', NULL, NULL, 5000.00, 167.00, 4833.00, 4833.00, 6, '2026-05-25 08:46:32'),
(56, 3, 'morning', 'opening', '2026-05-25', NULL, NULL, 100.00, 989.00, 0.00, 0.00, 6, '2026-05-25 14:30:27'),
(57, 3, 'morning', 'closing', '2026-05-25', NULL, NULL, 350.00, 100.00, 250.00, 1950000.00, 6, '2026-05-25 14:31:32'),
(58, 1, 'morning', 'opening', '2026-06-10', NULL, NULL, 100.00, 999.00, 0.00, 0.00, 19, '2026-06-10 09:26:53'),
(59, 1, 'morning', 'closing', '2026-06-10', NULL, NULL, 600.00, 100.00, 500.00, 2000000.00, 19, '2026-06-10 09:28:37'),
(61, 2, 'morning', 'opening', '2026-06-10', NULL, NULL, 567.00, 5000.00, 0.00, 0.00, 19, '2026-06-10 20:06:39'),
(62, 2, 'morning', 'closing', '2026-06-10', NULL, NULL, 999.00, 567.00, 432.00, 1728000.00, 19, '2026-06-10 20:07:17'),
(63, 1, 'evening', 'opening', '2026-06-10', NULL, NULL, 478.00, 600.00, 0.00, 0.00, 19, '2026-06-10 20:13:07'),
(64, 1, 'evening', 'closing', '2026-06-10', NULL, NULL, 1200.00, 478.00, 722.00, 2888000.00, 19, '2026-06-10 20:13:36'),
(65, 1, 'morning', 'opening', '2026-06-11', NULL, NULL, 500.00, 1200.00, 0.00, 0.00, 19, '2026-06-11 17:02:09'),
(67, 1, 'morning', 'closing', '2026-06-11', NULL, NULL, 4561.00, 500.00, 4061.00, 16244000.00, 19, '2026-06-11 17:02:59'),
(69, 1, 'evening', 'opening', '2026-06-11', NULL, NULL, 678.00, 4561.00, 0.00, 0.00, 19, '2026-06-11 17:38:48'),
(70, 1, 'evening', 'closing', '2026-06-11', NULL, NULL, 6789.00, 678.00, 6111.00, 24444000.00, 19, '2026-06-11 17:39:14'),
(71, 1, 'morning', 'opening', '2026-06-12', NULL, NULL, 0.00, 6789.00, 0.00, 0.00, 19, '2026-06-12 05:36:57'),
(72, 1, 'morning', 'closing', '2026-06-12', NULL, NULL, 895.00, 0.00, 895.00, 3580000.00, 19, '2026-06-12 05:37:28'),
(73, 3, 'morning', 'opening', '2026-06-12', NULL, NULL, 135.00, 350.00, 0.00, 0.00, 19, '2026-06-12 05:38:13'),
(74, 3, 'morning', 'closing', '2026-06-12', NULL, NULL, 1023.00, 135.00, 888.00, 2655120.00, 19, '2026-06-12 05:38:45'),
(76, 3, 'evening', 'opening', '2026-06-12', NULL, NULL, 1023.00, 1023.00, 0.00, 0.00, 19, '2026-06-12 06:58:44'),
(77, 3, 'evening', 'closing', '2026-06-12', NULL, NULL, 1998.00, 1023.00, 975.00, 2915250.00, 19, '2026-06-12 06:59:52');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,
  `attendant_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `shift` enum('morning','evening') NOT NULL,
  `cash_sales` decimal(12,2) DEFAULT 0.00,
  `bank_sales` decimal(12,2) DEFAULT 0.00,
  `mobile_sales` decimal(12,2) DEFAULT 0.00,
  `total_sales` decimal(12,2) DEFAULT 0.00,
  `litres_sold` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `pump_id`, `attendant_id`, `sale_date`, `shift`, `cash_sales`, `bank_sales`, `mobile_sales`, `total_sales`, `litres_sold`, `created_at`) VALUES
(1, 1, 4, '2026-03-24', 'morning', 4333.00, 0.00, 0.00, 4333.00, 0.00, '2026-03-24 12:39:03'),
(2, 1, 4, '2026-03-24', 'morning', 4545.00, 0.00, 0.00, 4545.00, 0.00, '2026-03-24 13:46:09'),
(3, 1, 4, '2026-03-24', 'evening', 8779.00, 0.00, 0.00, 8779.00, 0.00, '2026-03-24 13:46:34'),
(4, 1, 4, '2026-03-25', 'morning', 20000.00, 5000.00, 2000.00, 27000.00, 0.00, '2026-03-25 16:20:00'),
(5, 1, 4, '2026-03-25', 'morning', 20000.00, 5000.00, 2000.00, 27000.00, 0.00, '2026-03-25 16:20:10'),
(6, 1, 4, '2026-03-25', 'evening', 100000.00, 30000.00, 1000.00, 131000.00, 0.00, '2026-03-25 16:21:31'),
(7, 1, 4, '2026-03-25', 'evening', 100000.00, 30000.00, 1000.00, 131000.00, 0.00, '2026-03-25 16:21:45'),
(8, 3, 4, '2026-05-25', 'morning', 345654.00, 5676.00, 0.00, 351330.00, 250.00, '2026-05-25 14:35:11'),
(9, 1, 9, '2026-06-10', 'morning', 4544.00, 0.00, 0.00, 4544.00, 500.00, '2026-06-10 21:07:22'),
(10, 1, 24, '2026-06-11', 'morning', 4567.00, 0.00, 0.00, 4567.00, 4061.00, '2026-06-11 18:37:45'),
(18, 1, 24, '2026-06-11', 'evening', 600000.00, 0.00, 0.00, 600000.00, 6111.00, '2026-06-11 21:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `security_shifts`
--

CREATE TABLE `security_shifts` (
  `id` int(11) NOT NULL,
  `security_id` int(11) NOT NULL,
  `shift_type` varchar(50) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tanks`
--

CREATE TABLE `tanks` (
  `id` int(11) NOT NULL,
  `tank_number` varchar(20) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `max_capacity` decimal(10,2) NOT NULL COMMENT 'Maximum capacity in litres',
  `current_volume` decimal(10,2) DEFAULT 0.00 COMMENT 'Current fuel volume in litres',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tanks`
--

INSERT INTO `tanks` (`id`, `tank_number`, `fuel_type_id`, `max_capacity`, `current_volume`, `created_at`, `updated_at`) VALUES
(1, 'TANK001', 1, 40000.00, 14789.00, '2026-03-24 12:03:32', '2026-06-19 14:15:44'),
(8, 'TANK002', 2, 35000.00, 16137.00, '2026-06-11 18:13:59', '2026-06-19 14:16:08');

-- --------------------------------------------------------

--
-- Table structure for table `tank_refills`
--

CREATE TABLE `tank_refills` (
  `id` int(11) NOT NULL,
  `tank_id` int(11) NOT NULL,
  `refill_volume` decimal(10,2) NOT NULL COMMENT 'Volume added in litres',
  `cost` decimal(12,2) NOT NULL COMMENT 'Cost of refill',
  `receipt_image` varchar(255) DEFAULT NULL,
  `refill_date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tank_refills`
--

INSERT INTO `tank_refills` (`id`, `tank_id`, `refill_volume`, `cost`, `receipt_image`, `refill_date`, `created_by`, `created_at`) VALUES
(1, 1, 45.00, 4556.00, '../uploads/receipts/receipt_69c2868300e37.jpg', '2026-03-24 05:41:39', 6, '2026-03-24 12:41:39'),
(2, 1, 5.00, 6567.00, '../uploads/receipts/receipt_69c2962152d1a.jpg', '2026-03-24 06:48:17', 6, '2026-03-24 13:48:17'),
(3, 1, 34444444.00, 9999999999.99, NULL, '2026-03-25 09:58:58', 6, '2026-03-25 16:58:58'),
(4, 5, 334.00, 676789.00, NULL, '2026-03-27 08:34:35', 6, '2026-03-27 15:34:35'),
(5, 2, 4556.00, 45678766.00, NULL, '2026-05-25 07:42:55', 6, '2026-05-25 14:42:55'),
(6, 1, 1500.00, 750000.00, NULL, '2026-06-10 23:15:26', 19, '2026-06-11 06:15:26'),
(7, 1, 2000.00, 800000.00, NULL, '2026-06-10 23:20:31', 19, '2026-06-11 06:20:31'),
(8, 1, 5000.00, 43456567.00, NULL, '2026-06-11 11:09:21', 19, '2026-06-11 18:09:21'),
(9, 1, 5000.00, 2000000.00, NULL, '2026-06-11 22:19:22', 19, '2026-06-12 05:19:22'),
(10, 1, 6000.00, 2400000.00, NULL, '2026-06-11 22:20:25', 19, '2026-06-12 05:20:25'),
(11, 1, 6000.00, 2400000.00, NULL, '2026-06-11 23:49:28', 19, '2026-06-12 06:49:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('chief_manager','manager','accountant','pump_attendant','security') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `phone`, `address`, `profile_image`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'chief_manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'chief@petrolstation.com', 'John Chief', 'chief_manager', '+255700000001', NULL, NULL, 1, '2026-03-24 12:03:32', '2026-06-11 16:17:12'),
(2, 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@petrolstation.com', 'Jane Manager', 'manager', '+255700000002', NULL, NULL, 1, '2026-03-24 12:03:32', '2026-03-24 12:03:32'),
(3, 'accountant', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'accountant@petrolstation.com', 'Alice Accountant', 'accountant', '+255700000003', NULL, NULL, 1, '2026-03-24 12:03:32', '2026-05-25 16:58:30'),
(4, 'attendant1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'attendant1@petrolstation.com', 'Mike Attendant', 'accountant', '+255700000004', NULL, NULL, 1, '2026-03-24 12:03:32', '2026-05-25 16:49:33'),
(6, 'kaka', '$2y$10$S39On2IrQyj0xsXngRf/LOSCWF2Wxfpq5Qhlc2GUC2uIhCFEuR8wK', 'kaka@gmail.com', 'kaka kiki', 'accountant', '0617679594', 'dodoma', '../uploads/profiles/profile_6a146f586cbfb.jpg', 1, '2026-03-24 12:15:33', '2026-06-11 07:15:21'),
(7, 'chief', '$2y$10$K4Fwt0IxJCL0Or4nv8DUlOPz1PZ3d0Ni1Zns0qRGyAz3vQxCxTZk6', 'yyyyyyy@gmail.com', 'chat_system', 'chief_manager', '3344556677', '', NULL, 1, '2026-03-24 12:51:26', '2026-03-24 12:51:26'),
(8, 'treasurer', '$2y$10$LPch1ZwIVvEt9ER1rOm4u.tND3xcsZTzGEQBes/1z/9Z8YCiC3Xam', 'jastineasteriod204@gmail.com', 'Marian  Joel', 'accountant', '3344556677', 'Dodoma', '../uploads/profiles/profile_6a133268e1e5e.jpg', 1, '2026-03-24 12:53:36', '2026-06-09 06:05:15'),
(10, 'fay', '$2y$10$PQhH1Iu1DulqqtueKo0YAOJtu0liHgad89As8zk27nF18AAGOqssu', 'yff@gmail.com', 'yyyyyyyy', 'chief_manager', '2334445567', '', NULL, 1, '2026-03-25 17:16:46', '2026-06-11 07:15:00'),
(11, 'money', '$2y$10$6xCooHAPG5QJjTUo2.9nx.xNNiOswX6k2q8y0/JJonMn248Ub.Js.', 'maeda@gmail.com', 'hiu fffj', 'chief_manager', '0777777777', '', NULL, 1, '2026-03-27 15:41:06', '2026-06-11 16:15:43'),
(17, 'fish', '$2y$10$3DVo0s1vvloFlJog8BpIP.fLGqlTvFyTb8EFbCkvc2lDlPlJIUYii', 'dddfer@gmail.com', 'hgfjhgfdhgfd', 'manager', '0695486181', NULL, '../uploads/profiles/profile_6a146f090979c.jpg', 1, '2026-05-25 15:09:40', '2026-05-25 17:00:16'),
(18, 'mhasibu', '$2y$10$kl72KZFQ13s5mIBMxsR9i.dBLco45bY7Tl5GXfK1b9730VFhF1L9K', 'mh@gmail.com', 'godfrey peter', 'accountant', '0754565434', 'dodoma', NULL, 1, '2026-06-09 05:38:04', '2026-06-09 05:38:04'),
(19, 'meneja', '$2y$10$1eU7SgJvZ0nFJpaYWeBFuu8iXZAFhawic8HKV/EGIx0yaS/TBuevq', 'manag@gmail.com', 'Antony Tony', 'manager', '0632233223', 'dodoma', '../uploads/profiles/profile_6a27ab37505a0.jpeg', 1, '2026-06-09 05:39:22', '2026-06-09 05:57:11'),
(21, 'yahya', '$2y$10$h9t8EwiMx1jNTFJApldWgOhCQLgvsGzN.q63vSQG8sPJoOJN.H4Bu', 'mk@gmail.com', 'malik juma', 'manager', '0634433443', NULL, NULL, 1, '2026-06-11 07:52:44', '2026-06-11 07:52:44'),
(24, 'Attendant', '$2y$10$VmGstYX4JyHrLL0fw6E/MOVVl4jFoP0CoHvZTuthiBI6/Acas1z22', 'afr@gmail.com', 'michael joy', 'pump_attendant', '0787899898', '', NULL, 1, '2026-06-11 18:34:01', '2026-06-11 18:34:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_expense_type` (`expense_type`);

--
-- Indexes for table `fuel_prices`
--
ALTER TABLE `fuel_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_fuel_type` (`fuel_type_id`),
  ADD KEY `idx_effective_date` (`effective_date`);

--
-- Indexes for table `fuel_types`
--
ALTER TABLE `fuel_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `office_costs`
--
ALTER TABLE `office_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_cost_type` (`cost_type`);

--
-- Indexes for table `pumps`
--
ALTER TABLE `pumps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pump_number` (`pump_number`),
  ADD KEY `fuel_type_id` (`fuel_type_id`),
  ADD KEY `idx_pump_number` (`pump_number`),
  ADD KEY `idx_attendant` (`attendant_id`);

--
-- Indexes for table `pump_readings`
--
ALTER TABLE `pump_readings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pump_shift_date_type` (`pump_id`,`shift`,`reading_date`,`reading_type`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_pump_id` (`pump_id`),
  ADD KEY `idx_reading_date` (`reading_date`),
  ADD KEY `idx_reading_type` (`reading_type`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pump_id` (`pump_id`),
  ADD KEY `idx_sale_date` (`sale_date`),
  ADD KEY `idx_attendant` (`attendant_id`);

--
-- Indexes for table `security_shifts`
--
ALTER TABLE `security_shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_security_shift` (`security_id`,`shift_date`),
  ADD KEY `idx_security_id` (`security_id`),
  ADD KEY `idx_shift_date` (`shift_date`);

--
-- Indexes for table `tanks`
--
ALTER TABLE `tanks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tank_number` (`tank_number`),
  ADD KEY `fuel_type_id` (`fuel_type_id`),
  ADD KEY `idx_tank_number` (`tank_number`);

--
-- Indexes for table `tank_refills`
--
ALTER TABLE `tank_refills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_tank_id` (`tank_id`),
  ADD KEY `idx_refill_date` (`refill_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=297;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fuel_prices`
--
ALTER TABLE `fuel_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fuel_types`
--
ALTER TABLE `fuel_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `office_costs`
--
ALTER TABLE `office_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pumps`
--
ALTER TABLE `pumps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pump_readings`
--
ALTER TABLE `pump_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `security_shifts`
--
ALTER TABLE `security_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tanks`
--
ALTER TABLE `tanks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tank_refills`
--
ALTER TABLE `tank_refills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fuel_prices`
--
ALTER TABLE `fuel_prices`
  ADD CONSTRAINT `fuel_prices_ibfk_1` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`),
  ADD CONSTRAINT `fuel_prices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `office_costs`
--
ALTER TABLE `office_costs`
  ADD CONSTRAINT `office_costs_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `office_costs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `pumps`
--
ALTER TABLE `pumps`
  ADD CONSTRAINT `pumps_ibfk_1` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`),
  ADD CONSTRAINT `pumps_ibfk_2` FOREIGN KEY (`attendant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pump_readings`
--
ALTER TABLE `pump_readings`
  ADD CONSTRAINT `pump_readings_ibfk_1` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`),
  ADD CONSTRAINT `pump_readings_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`attendant_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `security_shifts`
--
ALTER TABLE `security_shifts`
  ADD CONSTRAINT `security_shifts_ibfk_1` FOREIGN KEY (`security_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tanks`
--
ALTER TABLE `tanks`
  ADD CONSTRAINT `tanks_ibfk_1` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`);

--
-- Constraints for table `tank_refills`
--
ALTER TABLE `tank_refills`
  ADD CONSTRAINT `tank_refills_ibfk_1` FOREIGN KEY (`tank_id`) REFERENCES `tanks` (`id`),
  ADD CONSTRAINT `tank_refills_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
