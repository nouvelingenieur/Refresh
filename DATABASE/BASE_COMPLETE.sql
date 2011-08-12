/*
	Plateforme web PPR - outil de crowdwourcing
	Copyright(C) 2011 Nicolas SEICHEPINE

	This file is part of PPR.
	
	PPR is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	Contact : contact_ppr@seichepine.org
*/

-- phpMyAdmin SQL Dump
-- http://www.phpmyadmin.net
--
-- Généré le : Dim 07 Août 2011 à 11:51
-- Version du serveur: 5.1.49
-- Version de PHP: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `enpcref`
--
CREATE DATABASE `enpcref` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `enpcref`;

-- --------------------------------------------------------

--
-- Structure de la table `comment`
--

CREATE TABLE IF NOT EXISTS `comment` (
  `comment_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(16) unsigned NOT NULL,
  `rand_prop` int(16) unsigned NOT NULL,
  `hash_prop` varchar(40) NOT NULL,
  `text` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_valid` tinyint(1) NOT NULL DEFAULT '0',
  `already_mod` tinyint(1) NOT NULL DEFAULT '0',
  `possibly_name` varchar(64) NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE IF NOT EXISTS `document` (
  `document_id` int(16) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `filedate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category` int(12) unsigned NOT NULL,
  PRIMARY KEY (`document_id`),
  KEY `category` (`category`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `document_category`
--

CREATE TABLE IF NOT EXISTS `document_category` (
  `category_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(128) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `thread`
--

CREATE TABLE IF NOT EXISTS `thread` (
  `thread_id` int(16) unsigned NOT NULL AUTO_INCREMENT,
  `rand_prop` int(16) unsigned NOT NULL,
  `hash_prop` varchar(40) NOT NULL,
  `title` varchar(128) NOT NULL,
  `text` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `category` int(12) unsigned NOT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT '0',
  `already_mod` tinyint(1) NOT NULL DEFAULT '0',
  `possibly_name` varchar(64) NOT NULL,
  PRIMARY KEY (`thread_id`),
  KEY `category_id` (`category`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `thread_category`
--

CREATE TABLE IF NOT EXISTS `thread_category` (
  `category_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(128) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `hash_mail` varchar(40) NOT NULL,
  `hash_pass` varchar(40) NOT NULL,
  `hash_conf` varchar(40) NOT NULL,
  `inscription_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `privileges` int(4) unsigned NOT NULL DEFAULT '3',
  `is_valid` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `vote`
--

CREATE TABLE IF NOT EXISTS `vote` (
  `vote_id` int(24) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(16) unsigned NOT NULL,
  `rand_prop` int(16) NOT NULL,
  `hash_prop` varchar(40) NOT NULL,
  `vote` tinyint(1) NOT NULL,
  PRIMARY KEY (`vote_id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `thread`
--
ALTER TABLE `thread`
  ADD CONSTRAINT `thread_ibfk_1` FOREIGN KEY (`category`) REFERENCES `thread_category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `vote`
--
ALTER TABLE `vote`
  ADD CONSTRAINT `vote_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE;
