-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 01 juin 2026 à 18:33
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `1000saveurs`
--

-- --------------------------------------------------------

--
-- Structure de la table `debts`
--

CREATE TABLE `debts` (
  `id` int(11) NOT NULL,
  `debtor_type` enum('client','employee') NOT NULL,
  `debtor_name` varchar(255) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_at` date DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `debts`
--

INSERT INTO `debts` (`id`, `debtor_type`, `debtor_name`, `employee_id`, `amount`, `sale_item_id`, `due_date`, `status`, `paid_at`, `paid_amount`, `created_at`, `updated_at`) VALUES
(5, 'client', 'Jean Dupont', NULL, 150.00, 3, '2026-05-23', 'pending', NULL, NULL, '2026-04-23 14:11:26', '2026-04-23 14:11:26');

-- --------------------------------------------------------

--
-- Structure de la table `departements`
--

CREATE TABLE `departements` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `departements`
--

INSERT INTO `departements` (`id`, `name`, `address`, `description`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Feel welcome', 'BUJUMBURA , ROHERO', 'Dans un monde en constante Ã©volution, il est essentiel de sâadapter et de continuer', 8, '2026-04-18 08:17:15', '2026-05-06 11:20:07'),
(5, 'Boucherie', '123 rue des Saveurs', 'Dans un monde en constante Ã©volution, il est essentiel de sâadapter et de continuer', 38, '2026-04-19 18:53:38', '2026-05-30 01:03:59'),
(6, 'Test Dept', '', 'Dans un monde en constante Ã©volution, il est essentiel', 5, '2026-04-19 18:55:16', '2026-05-06 20:34:39'),
(7, 'Test Prod Dept', '', 'Dans un monde en constante Ã©volution, il est essentiel de sâadapter et de continuer', NULL, '2026-04-19 18:56:01', '2026-04-27 20:29:10'),
(10, '1000saveurs', 'COTEBU', 'Dans un monde en constante Ã©volution, il est essentiel de sâadapter et de continuer', 5, '2026-04-27 17:08:52', '2026-04-27 20:27:25'),
(11, 'Viande hachee', 'yuiop', 'jup;;lju', 5, '2026-04-30 09:01:58', '2026-04-30 09:01:58'),
(12, 'fghjkl', 'xcvbnm,.', 'fghjk', 5, '2026-05-04 17:23:41', '2026-05-04 17:23:41'),
(18, 'Jean Laurent', 'Kamenge', 'Ventes des materiaux de construction', 5, '2026-05-06 20:33:29', '2026-05-06 20:33:29'),
(19, 'MUCO', '456 rue des Gourmets', 'Dans un monde en constante Ã©volution, il est essentiel de sâadapter et de continuer', NULL, '2026-04-22 20:22:23', '2026-05-05 19:23:51'),
(22, 'soso', 'Ngagara', 'soso veut devenir developpeur Frontend', 5, '2026-05-18 12:04:47', '2026-05-18 12:04:47'),
(23, 'Lourde', 'UE', 'Portugal', 10, '2026-05-27 17:57:35', '2026-05-27 17:57:35'),
(24, 'Général', NULL, 'Département principal', NULL, '2026-05-30 02:21:36', '2026-05-30 02:21:36'),
(25, 'Direction', NULL, 'Département principal', NULL, '2026-05-30 20:42:59', '2026-05-30 20:42:59'),
(26, 'Direction', NULL, 'Département principal', NULL, '2026-05-30 20:53:41', '2026-05-30 20:53:41');

-- --------------------------------------------------------

--
-- Structure de la table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `departement_id` int(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `hired_at` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `departement_id`, `position`, `salary`, `hired_at`, `created_at`, `updated_at`) VALUES
(5, 5, 1, '', 15000.00, '2026-04-12', '2026-04-22 20:38:56', '2026-05-18 12:06:31'),
(8, 10, 1, 'Bar', 15000.00, '2026-04-12', '2026-04-22 20:39:54', '2026-05-21 12:04:43'),
(9, 5, 5, 'Boucher', 15000.00, '2026-04-12', '2026-04-22 20:40:07', '2026-05-30 01:58:38'),
(11, 12, 1, 'Gérant', 300000.00, '2026-04-22', '2026-04-22 21:19:03', '2026-04-22 21:19:03'),
(16, 12, 1, 'Caissier', 120000.00, '2026-04-23', '2026-04-23 14:20:37', '2026-04-23 14:20:37'),
(17, 12, 1, 'Boucher', 150000.00, '2026-04-23', '2026-04-23 14:20:37', '2026-04-23 14:20:37'),
(18, 12, 5, 'Gérant', 0.00, '2026-04-23', '2026-04-23 14:33:15', '2026-05-30 01:58:09'),
(20, 4, 10, 'gerant', 5000.00, '2026-05-04', '2026-05-12 16:02:57', '2026-05-27 18:28:53'),
(21, 4, 10, 'Manager', 5000.00, '2026-05-04', '2026-05-12 16:03:09', '2026-05-12 16:03:09'),
(22, 35, 10, 'caissier', 50000.00, '2026-05-27', '2026-05-27 18:28:26', '2026-05-27 18:29:21'),
(23, 38, 1, 'Manager', 0.00, '2026-05-30', '2026-05-30 02:17:52', '2026-05-30 02:17:52'),
(24, 38, 1, 'manager', 200000.00, '2026-05-30', '2026-05-30 02:43:30', '2026-05-30 02:43:30'),
(25, 38, 1, 'Manager', 0.00, '2026-05-30', '2026-05-30 02:54:17', '2026-05-30 02:54:17'),
(26, 38, 1, 'Manager', 0.00, '2026-05-30', '2026-05-30 02:56:56', '2026-05-30 02:56:56'),
(27, 38, 1, 'Manager', 0.00, '2026-05-30', '2026-05-30 03:10:27', '2026-05-30 03:10:27'),
(28, 38, 1, 'Manager', 0.00, '2026-05-30', '2026-05-30 20:42:30', '2026-05-30 20:42:30'),
(29, 38, 1, 'Manager', 0.00, '2026-05-30', '2026-05-30 20:53:41', '2026-05-30 20:53:41'),
(30, 39, 1, 'gerant', 0.00, '2026-06-01', '2026-06-01 13:12:16', '2026-06-01 13:12:16');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `departement_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `departement_id`, `name`, `description`, `unit_price`, `current_stock`, `low_stock_threshold`, `created_at`, `updated_at`) VALUES
(3, 1, 'Steak hach', '250g', 500000.00, 1, 5, '2026-04-22 20:51:28', '2026-05-28 16:10:01'),
(4, 1, 'Vin ', '250bouteilles', 8.99, 97, 20, '2026-04-22 20:52:29', '2026-05-31 00:09:51'),
(5, 1, 'Amstel ', '250bouteilles', 8.99, 95, 20, '2026-04-22 20:52:44', '2026-05-31 00:22:21'),
(7, 1, 'Steak', 'En vente', 10000.00, 120, 5, '2026-04-22 23:52:36', '2026-05-07 00:08:06'),
(8, 1, 'Steak', 'Skeak for all', 104.00, 0, 5, '2026-04-23 11:09:46', '2026-05-10 17:19:48'),
(9, 1, 'Produit test', 'En vente', 115.00, 100, 5, '2026-04-23 14:04:01', '2026-05-07 00:05:38'),
(10, 1, 'Produit test', 'gtgtg', 1000.00, 96, 5, '2026-04-23 14:05:39', '2026-05-31 00:07:12'),
(11, 1, 'Produit test', NULL, 10.00, 100, 5, '2026-04-23 14:05:50', '2026-04-23 14:05:50'),
(12, 6, 'Boisson', 'Boisson non alcoliser', 5000.00, 500, 5, '2026-05-05 18:54:45', '2026-05-05 18:54:45'),
(13, 10, 'lkjhgbbb', 'kuhjjj', 106.00, 0, 5, '2026-05-06 21:27:27', '2026-05-10 17:18:20'),
(14, 10, 'Bovin', 'Bovin for B', 500.00, 20, 5, '2026-05-06 21:28:57', '2026-05-06 21:28:57'),
(15, 10, 'Depot de boissons', 'Boisson non alcholise', 8000.00, 2, 5, '2026-05-06 23:44:06', '2026-05-10 17:18:40'),
(16, 10, 'vtvt', 'vtvt', 2000.00, 10, 5, '2026-05-06 23:51:14', '2026-05-07 00:25:24'),
(17, 10, 'gggg', 'ggg', 121.00, 17, 5, '2026-05-07 00:04:08', '2026-05-07 00:04:08'),
(18, 10, 'BBNC', 'bbnc for all', 1999.00, 5000, 5, '2026-05-07 00:26:03', '2026-05-07 00:26:03'),
(19, 5, 'Ordinateur', 'Ordinateur de bureau', 500000.00, 1, 5, '2026-05-10 17:17:30', '2026-05-10 17:17:30'),
(20, 10, 'Nouveau', 'hjihihhih', 2000.00, 20, 5, '2026-05-19 17:06:43', '2026-05-21 12:00:04'),
(21, 5, 'Telephone', 'Samsung', 100000.00, 20, 5, '2026-05-30 01:56:23', '2026-05-30 01:56:23'),
(22, 5, 'Telephone', 'Samsung', 100000.00, 20, 5, '2026-05-30 01:56:33', '2026-05-30 01:56:33'),
(23, 1, 'Test Product', NULL, 1000.00, 10, 2, '2026-05-30 20:44:16', '2026-05-30 20:44:16'),
(24, 10, 'Pain grille', 'pain grille', 5000.00, 20, 5, '2026-06-01 12:13:16', '2026-06-01 12:13:16');

-- --------------------------------------------------------

--
-- Structure de la table `salary_reports`
--

CREATE TABLE `salary_reports` (
  `id` int(11) NOT NULL,
  `departement_id` int(11) NOT NULL,
  `manager_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_salary` decimal(10,2) NOT NULL,
  `status` enum('pending','approved') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `salary_reports`
--

INSERT INTO `salary_reports` (`id`, `departement_id`, `manager_id`, `month`, `year`, `total_salary`, `status`, `submitted_at`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 12, 4, 2026, 615000.00, 'pending', '2026-04-23 14:46:17', NULL, '2026-04-23 14:46:17', '2026-04-23 14:46:17');

-- --------------------------------------------------------

--
-- Structure de la table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `departement_id` int(11) NOT NULL,
  `sold_at` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sales`
--

INSERT INTO `sales` (`id`, `departement_id`, `sold_at`, `total_amount`, `created_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-04-20', 150000.00, 12, 'Nouveau', '2026-04-22 21:58:19', '2026-05-21 12:02:22'),
(2, 1, '2026-04-21', 250000.00, 12, 'Vente 2', '2026-04-22 21:58:19', '2026-04-22 21:58:19'),
(3, 1, '2026-04-22', 120000.00, 12, 'Vente 3', '2026-04-22 21:58:19', '2026-04-22 21:58:19'),
(4, 1, '2026-04-22', 0.00, 12, 'Vente test', '2026-04-22 23:45:26', '2026-04-22 23:45:26'),
(5, 1, '2026-04-23', 0.00, 12, 'Test vente', '2026-04-22 23:53:34', '2026-04-22 23:53:34'),
(6, 1, '2026-04-23', 0.00, 12, 'Test vente', '2026-04-22 23:53:46', '2026-04-22 23:53:46'),
(7, 1, '2026-04-23', 0.00, 12, 'Test vente', '2026-04-22 23:58:19', '2026-04-22 23:58:19'),
(8, 1, '2026-04-23', 0.00, 12, 'Test vente', '2026-04-23 00:14:35', '2026-04-23 00:14:35'),
(14, 1, '2026-05-31', 8.99, 38, NULL, '2026-05-31 00:06:41', '2026-05-31 00:06:41'),
(15, 1, '2026-05-31', 4000.00, 38, NULL, '2026-05-31 00:07:12', '2026-05-31 00:07:12'),
(16, 1, '2026-05-31', 17.98, 38, NULL, '2026-05-31 00:09:51', '2026-05-31 00:09:51'),
(17, 1, '2026-05-31', 17.98, 38, NULL, '2026-05-31 00:10:47', '2026-05-31 00:10:47'),
(18, 1, '2026-05-26', 8.99, 38, NULL, '2026-05-31 00:13:27', '2026-05-31 00:13:27'),
(19, 1, '2026-05-31', 17.98, 38, NULL, '2026-05-31 00:22:21', '2026-05-31 00:22:21');

-- --------------------------------------------------------

--
-- Structure de la table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `is_paid` tinyint(1) DEFAULT 1,
  `client_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `is_paid`, `client_name`, `created_at`, `updated_at`) VALUES
(3, 5, 5, 2, 10.00, 0, 'Client Dette', '2026-04-23 14:10:13', '2026-04-23 14:10:13'),
(4, 14, 4, 1, 8.99, 1, NULL, '2026-05-31 00:06:41', '2026-05-31 00:06:41'),
(5, 15, 10, 4, 1000.00, 1, NULL, '2026-05-31 00:07:12', '2026-05-31 00:07:12'),
(6, 16, 4, 2, 8.99, 1, NULL, '2026-05-31 00:09:51', '2026-05-31 00:09:51'),
(7, 17, 5, 2, 8.99, 1, NULL, '2026-05-31 00:10:50', '2026-05-31 00:10:50'),
(8, 18, 5, 1, 8.99, 1, NULL, '2026-05-31 00:13:27', '2026-05-31 00:13:27'),
(9, 19, 5, 2, 8.99, 1, NULL, '2026-05-31 00:22:21', '2026-05-31 00:22:21');

-- --------------------------------------------------------

--
-- Structure de la table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `quantity`, `type`, `reason`, `reference_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'out', 'Vente #14', 14, 38, '2026-05-31 00:06:42', '2026-05-31 00:06:42'),
(2, 10, 4, 'out', 'Vente #15', 15, 38, '2026-05-31 00:07:12', '2026-05-31 00:07:12'),
(3, 4, 2, 'out', 'Vente #16', 16, 38, '2026-05-31 00:09:51', '2026-05-31 00:09:51'),
(4, 5, 2, 'out', 'Vente #17', 17, 38, '2026-05-31 00:10:50', '2026-05-31 00:10:50'),
(5, 5, 1, 'out', 'Vente #18', 18, 38, '2026-05-31 00:13:27', '2026-05-31 00:13:27'),
(6, 5, 2, 'out', 'Vente #19', 19, 38, '2026-05-31 00:22:21', '2026-05-31 00:22:21');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `role` enum('admin','patron','manager') NOT NULL DEFAULT 'manager',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `last_name`, `email`, `password`, `phone`, `is_active`, `role`, `created_at`, `updated_at`) VALUES
(4, 'NDAYIKEZA', 'NDAYIKEZA', 'Amos', 'amos@gmail.com', 'Developpeur', '66642122', 1, '', '2026-04-17 10:33:02', '2026-05-27 18:28:53'),
(5, 'Jean Dupont', 'Jean', 'SOSO', 'jon@example.com', 'secret', '0612345678', 1, 'manager', '2026-04-19 18:39:24', '2026-05-18 12:06:30'),
(7, 'Laurent', 'Test', 'Jean Baptiste', 'emp@test.com', 'pass', NULL, 1, 'patron', '2026-04-19 18:55:15', '2026-05-17 11:48:43'),
(8, 'Test User', 'Test', 'User', 'test@example.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', '0123456789', 1, 'patron', '2026-04-21 16:36:48', '2026-04-21 16:36:48'),
(10, '', 'Super', 'Admin', 'admin@1000saveurs.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', NULL, 1, 'admin', '2026-04-22 18:59:21', '2026-04-22 18:59:21'),
(12, '', 'Marie', 'Manager', 'manager@1000saveurs.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', NULL, 1, 'manager', '2026-04-22 21:17:13', '2026-04-22 21:17:13'),
(13, '', 'Jean', 'Patron', 'patron@1000saveurs.com', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', NULL, 1, 'patron', '2026-04-23 13:57:28', '2026-04-23 13:57:28'),
(14, 'KALENGA', 'MBATCHIKA', 'Marc', 'marc@gmail.com', 'toutvasbin', '66642122', 1, 'admin', '2026-05-17 11:18:09', '2026-05-17 11:18:09'),
(31, 'Nouveau Nom', '', 'Jean Baptiste', 'nouveau.email@example.com', 'password576', NULL, 1, 'manager', '2026-05-21 11:09:36', '2026-05-21 11:55:20'),
(32, 'IRAKOZE', 'IRAKOZE', 'Alice', 'manager@gmail.com', '$2y$10$YourSaltAndHashHere', '79862218', 1, 'manager', '2026-05-21 11:37:25', '2026-05-29 22:14:28'),
(33, 'SALO', 'SALO', 'SALO', 'salo@gmail.com', '$2y$10$JQAf7885tnh9qjSIgkoHMulUGOh8S0RtOBcJqGcnflMxoHxgVg51S', '66645122', 1, 'patron', '2026-05-22 14:20:04', '2026-05-22 15:04:49'),
(35, '', 'tjgjngj', 'bjhj', 'vvvvvvv@gmail.com', '$2y$10$0IjVQZ.5c3LXOMnq6w7bM.jgteHeAw1BvJ9DcOBFqNy5GIcWsnBRu', NULL, 1, '', '2026-05-27 18:28:26', '2026-05-27 18:28:26'),
(38, '', 'Jean', 'Dupont', 'manager@1000saveur.com', '$2y$10$0BYnAHsB4n3LRseLT08QjObPYQpCFUnpuzZbvfGpCig.fo1sIEJJ2', NULL, 1, 'manager', '2026-05-29 22:50:00', '2026-05-29 22:50:00'),
(39, '', 'nhbjnnj', 'n jjkm', 'mkkmm@gmail.com', '$2y$10$KEGVAhJWs6f6a4okBTYke.iPtMARUeg/PdjSyXZXSotIjzRcXaW9K', NULL, 1, '', '2026-06-01 13:12:16', '2026-06-01 13:12:16');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `debts`
--
ALTER TABLE `debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `sale_item_id` (`sale_item_id`);

--
-- Index pour la table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Index pour la table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `departement_id` (`departement_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departement_id` (`departement_id`);

--
-- Index pour la table `salary_reports`
--
ALTER TABLE `salary_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departement_id` (`departement_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Index pour la table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departement_id` (`departement_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `debts`
--
ALTER TABLE `debts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `departements`
--
ALTER TABLE `departements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `salary_reports`
--
ALTER TABLE `salary_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `debts`
--
ALTER TABLE `debts`
  ADD CONSTRAINT `debts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debts_ibfk_2` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `departements`
--
ALTER TABLE `departements`
  ADD CONSTRAINT `departements_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `salary_reports`
--
ALTER TABLE `salary_reports`
  ADD CONSTRAINT `salary_reports_ibfk_1` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salary_reports_ibfk_2` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
