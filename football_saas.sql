-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 12, 2024 at 03:10 AM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `football_saas`
--

-- --------------------------------------------------------

--
-- Table structure for table `coach_history`
--

DROP TABLE IF EXISTS `coach_history`;
CREATE TABLE IF NOT EXISTS `coach_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `team_id` int NOT NULL,
  `coach_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
CREATE TABLE IF NOT EXISTS `matches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `home_team_id` int NOT NULL,
  `home_team_name` varchar(255) DEFAULT NULL,
  `away_team_id` int NOT NULL,
  `away_team_name` varchar(255) DEFAULT NULL,
  `match_date` datetime NOT NULL,
  `home_score` int DEFAULT NULL,
  `away_score` int DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `home_team_id` (`home_team_id`),
  KEY `away_team_id` (`away_team_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `home_team_id`, `home_team_name`, `away_team_id`, `away_team_name`, `match_date`, `home_score`, `away_score`, `status`, `created_at`) VALUES
(2, 1, NULL, 0, 'BARCA', '2024-12-04 02:13:00', 1, 6, 'completed', '2024-12-12 02:15:00'),
(3, 1, NULL, 0, 'REAL MADRIDE', '2024-12-05 02:15:00', 4, 0, 'completed', '2024-12-12 02:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
CREATE TABLE IF NOT EXISTS `players` (
  `id` int NOT NULL AUTO_INCREMENT,
  `team_id` int NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `jersey_number` int DEFAULT NULL,
  `birth_place` varchar(255) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `team_id`, `first_name`, `last_name`, `birth_date`, `position`, `jersey_number`, `birth_place`, `photo_path`, `contact`, `created_at`) VALUES
(2, 1, 'junior', 'Shofela ', '2006-11-24', 'Attaquant', 33, 'Londre', 'uploads/players/675a3efb0f702.png', '0170561121', '2024-12-12 01:40:11'),
(3, 1, 'Jules', 'Kouassi', '2007-12-29', 'Défenseur', 5, 'Cocody', 'uploads/players/675a3f82bb8e2.jpg', '0170561121', '2024-12-12 01:42:26'),
(4, 1, 'Bernard', 'Kouassi', '2008-06-19', 'Milieu', 1, 'Londre', 'uploads/players/675a3fa837d1d.png', '0170561121', '2024-12-12 01:43:04'),
(5, 1, 'Daniel ', 'MC', '2010-07-15', 'Attaquant', 25, 'Londre', 'uploads/players/675a3fe6e3988.png', '0170561121', '2024-12-12 01:44:06'),
(6, 1, 'Daniel ', 'Shofela ', '2007-06-07', 'Gardien', 2, '', 'uploads/players/675a4a62cea57.jpg', '0170561121', '2024-12-12 02:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
CREATE TABLE IF NOT EXISTS `teams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password` varchar(32) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `locality` varchar(255) NOT NULL,
  `coach_name` varchar(255) NOT NULL,
  `division` tinyint NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `email`, `password`, `team_name`, `locality`, `coach_name`, `division`, `logo_path`, `created_at`) VALUES
(1, 'daniel.shofela01@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'PSG', 'Paris', 'dani', 1, 'uploads/logos/675a4e625ec28.jpg', '2024-12-11 11:43:30');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
