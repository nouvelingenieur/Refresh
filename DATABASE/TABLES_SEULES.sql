/*
	Plateforme web PPR - outil de crowdsourcing
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
	
	Contact : nicolas.seichepine.org/?action=contact
*/

-- phpMyAdmin SQL Dump
-- version 3.4.8
-- http://www.phpmyadmin.net
--
-- Host: mysql
-- Generation Time: Mar 04, 2012 at 02:09 AM
-- Server version: 5.1.39
-- PHP Version: 5.3.6-11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `nouvelingenieur_refresh`
--

-- --------------------------------------------------------

--
-- Table structure for table `comment`
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `document`
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `document_category`
--

CREATE TABLE IF NOT EXISTS `document_category` (
  `category_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(128) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sauvegarde_comment`
--

CREATE TABLE IF NOT EXISTS `sauvegarde_comment` (
  `comment_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(16) unsigned NOT NULL,
  `text` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pro_vote` int(16) unsigned NOT NULL DEFAULT '0',
  `agt_vote` int(16) unsigned NOT NULL DEFAULT '0',
  `possibly_name` varchar(64) NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=354 ;

-- --------------------------------------------------------

--
-- Table structure for table `sauvegarde_thread`
--

CREATE TABLE IF NOT EXISTS `sauvegarde_thread` (
  `thread_id` int(16) unsigned NOT NULL AUTO_INCREMENT,
  `campagne_ref` int(8) unsigned NOT NULL,
  `title` varchar(128) NOT NULL,
  `text` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `state` enum('prop','integ_rapp','presente','en_cours_real','realise') NOT NULL DEFAULT 'prop',
  `pro_vote` int(16) unsigned NOT NULL DEFAULT '0',
  `agt_vote` int(16) unsigned NOT NULL DEFAULT '0',
  `category` int(12) unsigned NOT NULL,
  `possibly_name` varchar(64) NOT NULL,
  PRIMARY KEY (`thread_id`),
  KEY `category_id` (`category`),
  KEY `campagne_ref` (`campagne_ref`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Table structure for table `thread`
--

CREATE TABLE IF NOT EXISTS `thread` (
  `thread_id` int(16) unsigned NOT NULL AUTO_INCREMENT,
  `rand_prop` int(16) unsigned NOT NULL,
  `hash_prop` varchar(40) NOT NULL,
  `title` varchar(128) NOT NULL,
  `text` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `datecom` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `category` int(12) unsigned NOT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT '0',
  `already_mod` tinyint(1) NOT NULL DEFAULT '0',
  `possibly_name` varchar(64) NOT NULL,
  `chaine_moderation` varchar(40) NOT NULL,
  PRIMARY KEY (`thread_id`),
  KEY `category_id` (`category`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Table structure for table `thread_category`
--

CREATE TABLE IF NOT EXISTS `thread_category` (
  `category_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(128) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Table structure for table `vote`
--

CREATE TABLE IF NOT EXISTS `vote` (
  `vote_id` int(24) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(16) unsigned NOT NULL,
  `rand_prop` int(16) NOT NULL,
  `hash_prop` varchar(40) NOT NULL,
  `vote` tinyint(1) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vote_id`),
  KEY `thread_id` (`thread_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Table structure for table `vote_comment`
--

CREATE TABLE IF NOT EXISTS `vote_comment` (
  `vote_comment_id` int(24) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int(16) unsigned NOT NULL,
  `rand_prop` int(16) NOT NULL,
  `hash_prop` varchar(40) NOT NULL,
  `vote` tinyint(1) NOT NULL,
  PRIMARY KEY (`vote_comment_id`),
  KEY `thread_id` (`comment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=177 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sauvegarde_comment`
--
ALTER TABLE `sauvegarde_comment`
  ADD CONSTRAINT `sauvegarde_comment_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `sauvegarde_thread` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sauvegarde_thread`
--
ALTER TABLE `sauvegarde_thread`
  ADD CONSTRAINT `sauvegarde_thread_ibfk_1` FOREIGN KEY (`category`) REFERENCES `thread_category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thread`
--
ALTER TABLE `thread`
  ADD CONSTRAINT `thread_ibfk_1` FOREIGN KEY (`category`) REFERENCES `thread_category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vote`
--
ALTER TABLE `vote`
  ADD CONSTRAINT `vote_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `thread` (`thread_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vote_comment`
--
ALTER TABLE `vote_comment`
  ADD CONSTRAINT `vote_comment_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
