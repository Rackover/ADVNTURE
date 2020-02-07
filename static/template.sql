-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 07, 2020 at 03:46 PM
-- Server version: 5.7.29-0ubuntu0.18.04.1
-- PHP Version: 7.2.24-0ubuntu0.18.04.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `adventure`
--
CREATE DATABASE IF NOT EXISTS `adventure` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `adventure`;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE IF NOT EXISTS `client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(32) NOT NULL,
  `is_banned` tinyint(1) NOT NULL,
  `ban_reason` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`)
) ENGINE=InnoDB AUTO_INCREMENT=609 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hp_event`
--

DROP TABLE IF EXISTS `hp_event`;
CREATE TABLE IF NOT EXISTS `hp_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `hp_change` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
CREATE TABLE IF NOT EXISTS `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` varchar(64) NOT NULL,
  `content` varchar(257) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL,
  `hidden_because` text NOT NULL,
  `is_dead_end` tinyint(1) NOT NULL,
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `author_id_2` (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=299 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `page_succession`
--

DROP TABLE IF EXISTS `page_succession`;
CREATE TABLE IF NOT EXISTS `page_succession` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `command` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page_succession_ibfk_1` (`origin_id`),
  KEY `page_succession_ibfk_2` (`target_id`)
) ENGINE=InnoDB AUTO_INCREMENT=352 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `id` varchar(64) NOT NULL,
  `page_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `hp` tinyint(4) NOT NULL DEFAULT '10',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `player_ibfk_1` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `player_prop`
--

DROP TABLE IF EXISTS `player_prop`;
CREATE TABLE IF NOT EXISTS `player_prop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` varchar(64) NOT NULL,
  `prop_id` int(11) NOT NULL,
  `original_page_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `prop_id` (`prop_id`),
  KEY `player_prop_ibfk_4` (`player_id`),
  KEY `player_prop_ibfk_3` (`original_page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=903 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `prop`
--

DROP TABLE IF EXISTS `prop`;
CREATE TABLE IF NOT EXISTS `prop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(96) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `prop_placement`
--

DROP TABLE IF EXISTS `prop_placement`;
CREATE TABLE IF NOT EXISTS `prop_placement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prop_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `prop_placement_ibfk_1` (`prop_id`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `word_blacklist`
--

DROP TABLE IF EXISTS `word_blacklist`;
CREATE TABLE IF NOT EXISTS `word_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(64) NOT NULL,
  `gravity` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`)
) ENGINE=InnoDB AUTO_INCREMENT=892 DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hp_event`
--
ALTER TABLE `hp_event`
  ADD CONSTRAINT `hp_event_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`);

--
-- Constraints for table `page`
--
ALTER TABLE `page`
  ADD CONSTRAINT `page_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `player` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_succession`
--
ALTER TABLE `page_succession`
  ADD CONSTRAINT `page_succession_ibfk_1` FOREIGN KEY (`origin_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_succession_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `player_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`);

--
-- Constraints for table `player_prop`
--
ALTER TABLE `player_prop`
  ADD CONSTRAINT `player_prop_ibfk_2` FOREIGN KEY (`prop_id`) REFERENCES `prop` (`id`),
  ADD CONSTRAINT `player_prop_ibfk_3` FOREIGN KEY (`original_page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_prop_ibfk_4` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prop_placement`
--
ALTER TABLE `prop_placement`
  ADD CONSTRAINT `prop_placement_ibfk_1` FOREIGN KEY (`prop_id`) REFERENCES `prop` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prop_placement_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
