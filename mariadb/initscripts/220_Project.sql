SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL,
  `title` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `fromDate` date NOT NULL,
  `toDate` date NOT NULL,
  `docsRepo` varchar(255) NOT NULL,
  `codeRepo` varchar(255) NOT NULL,
  `wantReadme` tinyint(1) unsigned NOT NULL,
  `wantIgnore` tinyint(1) unsigned NOT NULL,
  `wantCSS` tinyint(1) unsigned NOT NULL,
  `wantJS` tinyint(1) unsigned NOT NULL,
  `wantPages` tinyint(1) unsigned NOT NULL,
  `color` tinyint(4) NOT NULL,
  `font` tinyint(4) NOT NULL,
  `wantDarkMode` tinyint(1) unsigned NOT NULL,
  `wantCopyright` tinyint(1) unsigned NOT NULL,
  `wantSearch` tinyint(1) unsigned NOT NULL,
  `wantTags` tinyint(1) unsigned NOT NULL,
  `logo` mediumtext NOT NULL,
  `wantJournal` tinyint(1) unsigned NOT NULL,
  `wantExamples` tinyint(1) unsigned NOT NULL,
  `structure` mediumtext NOT NULL,
  `confirmedBy` int(10) unsigned DEFAULT NULL,
  `comment` varchar(500) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `downloadUrl` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `confirmedBy` (`confirmedBy`),
  CONSTRAINT `project_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_ibfk_2` FOREIGN KEY (`confirmedBy`) REFERENCES `user` (`id`) ON DELETE SET NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;