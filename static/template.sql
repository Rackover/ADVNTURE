-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 28, 2020 at 09:04 PM
-- Server version: 10.1.38-MariaDB-0+deb9u1
-- PHP Version: 7.0.33-0+deb9u6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `adventure`
--

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `address` varchar(32) NOT NULL,
  `is_banned` tinyint(1) NOT NULL,
  `ban_reason` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dimension`
--

CREATE TABLE `dimension` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `readonly` tinyint(1) NOT NULL DEFAULT '1',
  `initial` tinyint(1) NOT NULL DEFAULT '0',
  `starting_page` int(11) NOT NULL,
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hp_event`
--

CREATE TABLE `hp_event` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `hp_change` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

CREATE TABLE `page` (
  `id` int(11) NOT NULL,
  `author_id` varchar(64) NOT NULL,
  `content` varchar(257) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL,
  `hidden_because` text NOT NULL,
  `is_dead_end` tinyint(1) NOT NULL,
  `dimension_id` int(11) NOT NULL DEFAULT '1',
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `page_succession`
--

CREATE TABLE `page_succession` (
  `id` int(11) NOT NULL,
  `origin_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `command` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

CREATE TABLE `player` (
  `id` varchar(64) NOT NULL,
  `page_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `hp` tinyint(255) UNSIGNED NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `player_prop`
--

CREATE TABLE `player_prop` (
  `id` int(11) NOT NULL,
  `player_id` varchar(64) NOT NULL,
  `prop_id` int(11) NOT NULL,
  `original_page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `prop`
--

CREATE TABLE `prop` (
  `id` int(11) NOT NULL,
  `name` varchar(96) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `prop_placement`
--

CREATE TABLE `prop_placement` (
  `id` int(11) NOT NULL,
  `prop_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `word_blacklist`
--

CREATE TABLE `word_blacklist` (
  `id` int(11) NOT NULL,
  `word` varchar(64) NOT NULL,
  `gravity` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `address` (`address`);

--
-- Indexes for table `dimension`
--
ALTER TABLE `dimension`
  ADD PRIMARY KEY (`id`),
  ADD KEY `starting_page` (`starting_page`);

--
-- Indexes for table `hp_event`
--
ALTER TABLE `hp_event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`);

--
-- Indexes for table `page`
--
ALTER TABLE `page`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `author_id_2` (`author_id`),
  ADD KEY `page_ibfk_1` (`dimension_id`);

--
-- Indexes for table `page_succession`
--
ALTER TABLE `page_succession`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_succession_ibfk_1` (`origin_id`),
  ADD KEY `page_succession_ibfk_2` (`target_id`);

--
-- Indexes for table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `player_ibfk_1` (`page_id`);

--
-- Indexes for table `player_prop`
--
ALTER TABLE `player_prop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prop_id` (`prop_id`),
  ADD KEY `player_prop_ibfk_4` (`player_id`),
  ADD KEY `player_prop_ibfk_3` (`original_page_id`);

--
-- Indexes for table `prop`
--
ALTER TABLE `prop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `prop_placement`
--
ALTER TABLE `prop_placement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prop_placement_ibfk_1` (`prop_id`),
  ADD KEY `page_id` (`page_id`);

--
-- Indexes for table `word_blacklist`
--
ALTER TABLE `word_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `word` (`word`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=533;
--
-- AUTO_INCREMENT for table `dimension`
--
ALTER TABLE `dimension`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `hp_event`
--
ALTER TABLE `hp_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `page`
--
ALTER TABLE `page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=277;
--
-- AUTO_INCREMENT for table `page_succession`
--
ALTER TABLE `page_succession`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327;
--
-- AUTO_INCREMENT for table `player_prop`
--
ALTER TABLE `player_prop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=731;
--
-- AUTO_INCREMENT for table `prop`
--
ALTER TABLE `prop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;
--
-- AUTO_INCREMENT for table `prop_placement`
--
ALTER TABLE `prop_placement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;
--
-- AUTO_INCREMENT for table `word_blacklist`
--
ALTER TABLE `word_blacklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=453;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `dimension`
--
ALTER TABLE `dimension`
  ADD CONSTRAINT `dimension_ibfk_1` FOREIGN KEY (`starting_page`) REFERENCES `page` (`id`);

--
-- Constraints for table `hp_event`
--
ALTER TABLE `hp_event`
  ADD CONSTRAINT `hp_event_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`);

--
-- Constraints for table `page`
--
ALTER TABLE `page`
  ADD CONSTRAINT `page_ibfk_1` FOREIGN KEY (`dimension_id`) REFERENCES `dimension` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `player` (`id`) ON DELETE CASCADE;

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
