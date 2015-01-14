-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 24, 2014 at 11:25 AM
-- Server version: 5.5.40
-- PHP Version: 5.4.35-0+deb7u2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `photohunt`
--

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `name` varchar(80) NOT NULL,
  `value` varchar(255) NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`name`, `value`) VALUES
('version', '1');

-- --------------------------------------------------------

--
-- Table structure for table `contests`
--

DROP TABLE IF EXISTS `contests`;
CREATE TABLE `contests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `rewards` int(11) NOT NULL DEFAULT '5',
  `close_date` date NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '1',
  `user_id` bigint(20) NOT NULL,
  `works` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `FK_userid` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `goods`
--

DROP TABLE IF EXISTS `goods`;
CREATE TABLE `goods` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(80) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `service_name` (`service_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `goods`
--

INSERT INTO `goods` (`id`, `service_name`, `name`, `description`, `price`) VALUES
(1, 'extra_photo', 'Дополнительная фотография', 'Позволяет опубликовать дополнительную фотографию в открытый конкурс.', 10);

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `contest_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `vote_count` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `contest_id` (`contest_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `good_id` bigint(20) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(40) NOT NULL,
  `display_name` varchar(80) NOT NULL,
  `password` varchar(40) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT '0',
  `vote_count` tinyint(4) NOT NULL DEFAULT '3',
  `hash` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `display_name`, `password`, `balance`, `vote_count`, `hash`) VALUES
(1, 'System', 'Sytem', 'block', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_contests`
--
DROP VIEW IF EXISTS `view_contests`;
CREATE TABLE `view_contests` (
`id` bigint(20)
,`subject` varchar(255)
,`rewards` int(11)
,`close_date` date
,`status` smallint(6)
,`user_id` bigint(20)
,`display_name` varchar(80)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_images`
--
DROP VIEW IF EXISTS `view_images`;
CREATE TABLE `view_images` (
`id` bigint(20)
,`contest_id` bigint(20)
,`user_id` bigint(20)
,`subject` varchar(255)
,`vote_count` tinyint(4)
,`display_name` varchar(80)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_items`
--
DROP VIEW IF EXISTS `view_items`;
CREATE TABLE `view_items` (
`id` bigint(20)
,`user_id` bigint(20)
,`service_name` varchar(80)
,`name` varchar(80)
,`description` varchar(255)
,`count` int(11)
);
-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `image_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `image_id` (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure for view `view_contests`
--
DROP TABLE IF EXISTS `view_contests`;

CREATE VIEW `view_contests` AS select `c`.`id` AS `id`,`c`.`subject` AS `subject`,`c`.`rewards` AS `rewards`,`c`.`close_date` AS `close_date`,`c`.`status` AS `status`,`c`.`user_id` AS `user_id`,`c`.`works` as `works`, `u`.`display_name` AS `display_name` from (`contests` `c` join `users` `u` on((`c`.`user_id` = `u`.`id`)));

-- --------------------------------------------------------

--
-- Structure for view `view_images`
--
DROP TABLE IF EXISTS `view_images`;

CREATE VIEW `view_images` AS select `i`.`id` AS `id`,`i`.`contest_id` AS `contest_id`,`i`.`user_id` AS `user_id`,`i`.`subject` AS `subject`,`i`.`vote_count` AS `vote_count`,`u`.`display_name` AS `display_name` from (`images` `i` join `users` `u` on((`i`.`user_id` = `u`.`id`)));

-- --------------------------------------------------------

--
-- Structure for view `view_items`
--
DROP TABLE IF EXISTS `view_items`;

CREATE VIEW `view_items` AS select `i`.`id` AS `id`,`u`.`id` AS `user_id`,`g`.`service_name` AS `service_name`,`g`.`name` AS `name`,`g`.`description` AS `description`,`i`.`count` AS `count` from ((`goods` `g` join `items` `i` on((`g`.`id` = `i`.`good_id`))) join `users` `u` on((`i`.`user_id` = `u`.`id`)));

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contests`
--
ALTER TABLE `contests`
  ADD CONSTRAINT `contests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`contest_id`) REFERENCES `contests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `images_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `items_ibfk_2` FOREIGN KEY (`good_id`) REFERENCES `goods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
