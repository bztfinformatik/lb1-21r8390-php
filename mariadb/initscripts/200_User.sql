-- Creates the user table

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(35) NOT NULL,
  `email` varchar(255) NOT NULL,
  `wantsUpdates` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `salt` varchar(20) NOT NULL,
  `role` tinyint(4) unsigned NOT NULL,
  `profilePicture` longtext DEFAULT NULL,
  `isVerified` tinyint(1) NOT NULL,
  `verificationCode` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
