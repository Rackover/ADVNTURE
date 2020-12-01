-- phpMyAdmin SQL Dump
-- version 4.6.6deb5ubuntu0.5
-- https://www.phpmyadmin.net/
--
-- Client :  localhost:3306
-- Généré le :  Mar 01 Décembre 2020 à 23:02
-- Version du serveur :  5.7.32-0ubuntu0.18.04.1
-- Version de PHP :  7.2.24-0ubuntu0.18.04.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `adventure`
--
CREATE DATABASE IF NOT EXISTS `adventure` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `adventure`;

-- --------------------------------------------------------

--
-- Structure de la table `biome`
--

DROP TABLE IF EXISTS `biome`;
CREATE TABLE `biome` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `color` varchar(6) NOT NULL,
  `characters` varchar(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `biome_words`
--

DROP TABLE IF EXISTS `biome_words`;
CREATE TABLE `biome_words` (
  `id` int(11) NOT NULL,
  `biome_id` int(11) NOT NULL,
  `word` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `address` varchar(32) NOT NULL,
  `is_banned` tinyint(1) NOT NULL,
  `ban_reason` text,
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `dimension`
--

DROP TABLE IF EXISTS `dimension`;
CREATE TABLE `dimension` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `readonly` tinyint(1) NOT NULL DEFAULT '1',
  `initial` tinyint(1) NOT NULL DEFAULT '0',
  `starting_page` int(11) NOT NULL,
  `type` enum('BRANCH','GRID') NOT NULL DEFAULT 'BRANCH',
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `hp_event`
--

DROP TABLE IF EXISTS `hp_event`;
CREATE TABLE `hp_event` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `hp_change` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `page`
--

DROP TABLE IF EXISTS `page`;
CREATE TABLE `page` (
  `id` int(11) NOT NULL,
  `author_id` varchar(64) NOT NULL,
  `dimension_id` int(11) NOT NULL DEFAULT '1',
  `position` varchar(16) DEFAULT NULL,
  `biome_id` int(11) NOT NULL DEFAULT '1',
  `content` varchar(257) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL,
  `hidden_because` text NOT NULL,
  `is_dead_end` tinyint(1) NOT NULL,
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `page_succession`
--

DROP TABLE IF EXISTS `page_succession`;
CREATE TABLE `page_succession` (
  `id` int(11) NOT NULL,
  `origin_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `command` varchar(255) NOT NULL,
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `id` varchar(64) NOT NULL,
  `page_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `hp` tinyint(4) NOT NULL DEFAULT '10',
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `player_prop`
--

DROP TABLE IF EXISTS `player_prop`;
CREATE TABLE `player_prop` (
  `id` int(11) NOT NULL,
  `player_id` varchar(64) NOT NULL,
  `prop_id` int(11) NOT NULL,
  `original_page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `player_vision`
--

DROP TABLE IF EXISTS `player_vision`;
CREATE TABLE `player_vision` (
  `id` int(11) NOT NULL,
  `player_id` varchar(64) NOT NULL,
  `page_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `prop`
--

DROP TABLE IF EXISTS `prop`;
CREATE TABLE `prop` (
  `id` int(11) NOT NULL,
  `name` varchar(96) DEFAULT NULL,
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `prop_placement`
--

DROP TABLE IF EXISTS `prop_placement`;
CREATE TABLE `prop_placement` (
  `id` int(11) NOT NULL,
  `prop_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `word_blacklist`
--

DROP TABLE IF EXISTS `word_blacklist`;
CREATE TABLE `word_blacklist` (
  `id` int(11) NOT NULL,
  `word` varchar(64) NOT NULL,
  `gravity` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `biome`
--
ALTER TABLE `biome`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `color` (`color`);

--
-- Index pour la table `biome_words`
--
ALTER TABLE `biome_words`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `address` (`address`);

--
-- Index pour la table `dimension`
--
ALTER TABLE `dimension`
  ADD PRIMARY KEY (`id`),
  ADD KEY `starting_page` (`starting_page`);

--
-- Index pour la table `hp_event`
--
ALTER TABLE `hp_event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`);

--
-- Index pour la table `page`
--
ALTER TABLE `page`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `author_id_2` (`author_id`),
  ADD KEY `biome_id` (`biome_id`),
  ADD KEY `page_ibfk_2` (`dimension_id`);

--
-- Index pour la table `page_succession`
--
ALTER TABLE `page_succession`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_succession_ibfk_1` (`origin_id`),
  ADD KEY `page_succession_ibfk_2` (`target_id`);

--
-- Index pour la table `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `player_ibfk_1` (`page_id`);

--
-- Index pour la table `player_prop`
--
ALTER TABLE `player_prop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prop_id` (`prop_id`),
  ADD KEY `player_prop_ibfk_4` (`player_id`),
  ADD KEY `player_prop_ibfk_3` (`original_page_id`);

--
-- Index pour la table `player_vision`
--
ALTER TABLE `player_vision`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_player_vision` (`player_id`,`page_id`),
  ADD UNIQUE KEY `id_2` (`id`),
  ADD KEY `id` (`id`),
  ADD KEY `page_id` (`page_id`);

--
-- Index pour la table `prop`
--
ALTER TABLE `prop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `prop_placement`
--
ALTER TABLE `prop_placement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prop_placement_ibfk_1` (`prop_id`),
  ADD KEY `page_id` (`page_id`);

--
-- Index pour la table `word_blacklist`
--
ALTER TABLE `word_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `word` (`word`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `biome`
--
ALTER TABLE `biome`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT pour la table `biome_words`
--
ALTER TABLE `biome_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;
--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1115;
--
-- AUTO_INCREMENT pour la table `dimension`
--
ALTER TABLE `dimension`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `hp_event`
--
ALTER TABLE `hp_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;
--
-- AUTO_INCREMENT pour la table `page`
--
ALTER TABLE `page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=550;
--
-- AUTO_INCREMENT pour la table `page_succession`
--
ALTER TABLE `page_succession`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=669;
--
-- AUTO_INCREMENT pour la table `player_prop`
--
ALTER TABLE `player_prop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1591;
--
-- AUTO_INCREMENT pour la table `player_vision`
--
ALTER TABLE `player_vision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=347;
--
-- AUTO_INCREMENT pour la table `prop`
--
ALTER TABLE `prop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;
--
-- AUTO_INCREMENT pour la table `prop_placement`
--
ALTER TABLE `prop_placement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;
--
-- AUTO_INCREMENT pour la table `word_blacklist`
--
ALTER TABLE `word_blacklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=892;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `dimension`
--
ALTER TABLE `dimension`
  ADD CONSTRAINT `dimension_ibfk_1` FOREIGN KEY (`starting_page`) REFERENCES `page` (`id`);

--
-- Contraintes pour la table `hp_event`
--
ALTER TABLE `hp_event`
  ADD CONSTRAINT `hp_event_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`);

--
-- Contraintes pour la table `page`
--
ALTER TABLE `page`
  ADD CONSTRAINT `page_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `player` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_ibfk_2` FOREIGN KEY (`dimension_id`) REFERENCES `dimension` (`id`),
  ADD CONSTRAINT `page_ibfk_3` FOREIGN KEY (`biome_id`) REFERENCES `biome` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `page_succession`
--
ALTER TABLE `page_succession`
  ADD CONSTRAINT `page_succession_ibfk_1` FOREIGN KEY (`origin_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_succession_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `player_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`);

--
-- Contraintes pour la table `player_prop`
--
ALTER TABLE `player_prop`
  ADD CONSTRAINT `player_prop_ibfk_2` FOREIGN KEY (`prop_id`) REFERENCES `prop` (`id`),
  ADD CONSTRAINT `player_prop_ibfk_3` FOREIGN KEY (`original_page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_prop_ibfk_4` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `player_vision`
--
ALTER TABLE `player_vision`
  ADD CONSTRAINT `player_vision_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `player_vision_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `prop_placement`
--
ALTER TABLE `prop_placement`
  ADD CONSTRAINT `prop_placement_ibfk_1` FOREIGN KEY (`prop_id`) REFERENCES `prop` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prop_placement_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
