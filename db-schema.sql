-- --------------------------------------------------------
-- Hostiteľ:                     127.0.0.1
-- Verze serveru:                5.7.33 - MySQL Community Server (GPL)
-- OS serveru:                   Win64
-- HeidiSQL Verzia:              11.2.0.6213
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Exportování struktury databáze pro
CREATE DATABASE IF NOT EXISTS `wol` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `wol`;

-- Exportování struktury pro tabulka wol.computer
CREATE TABLE IF NOT EXISTS `computer` (
  `id` varchar(255) NOT NULL,
  `deviceId` varchar(255) NOT NULL,
  `userId` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `macAddress` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK__device` (`deviceId`),
  KEY `FK_computer_user` (`userId`),
  CONSTRAINT `FK__device` FOREIGN KEY (`deviceId`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_computer_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export dat nebyl vybrán.

-- Exportování struktury pro tabulka wol.connection
CREATE TABLE IF NOT EXISTS `connection` (
  `id` varchar(255) NOT NULL,
  `userId` varchar(255) NOT NULL,
  `computerId` varchar(255) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('TCP','UDP') NOT NULL DEFAULT 'TCP',
  `serverId` varchar(255) NOT NULL DEFAULT '',
  `remotePort` smallint(5) unsigned NOT NULL DEFAULT '0',
  `localPort` smallint(5) unsigned NOT NULL DEFAULT '0',
  `localIp` varchar(50) NOT NULL,
  `ipWhitelist` text,
  PRIMARY KEY (`id`),
  KEY `FK_connection_user` (`userId`),
  KEY `FK_connection_computer` (`computerId`),
  KEY `FK_connection_server` (`serverId`),
  CONSTRAINT `FK_connection_computer` FOREIGN KEY (`computerId`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_connection_server` FOREIGN KEY (`serverId`) REFERENCES `server` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_connection_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export dat nebyl vybrán.

-- Exportování struktury pro tabulka wol.device
CREATE TABLE IF NOT EXISTS `device` (
  `id` varchar(255) NOT NULL,
  `userId` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lastActiveAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK__user` (`userId`),
  CONSTRAINT `FK__user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export dat nebyl vybrán.

-- Exportování struktury pro tabulka wol.server
CREATE TABLE IF NOT EXISTS `server` (
  `id` varchar(50) NOT NULL,
  `userId` varchar(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `serverIp` varchar(50) NOT NULL,
  `serverFrpPort` smallint(5) unsigned NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`),
  CONSTRAINT `FK_server_user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export dat nebyl vybrán.

-- Exportování struktury pro tabulka wol.startlist
CREATE TABLE IF NOT EXISTS `startlist` (
  `id` varchar(255) NOT NULL,
  `deviceId` varchar(255) NOT NULL,
  `computerId` varchar(255) NOT NULL,
  `userId` varchar(255) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `startAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK1__device` (`deviceId`),
  KEY `FK2__computer` (`computerId`),
  KEY `FK3__user` (`userId`),
  CONSTRAINT `FK1__device` FOREIGN KEY (`deviceId`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK2__computer` FOREIGN KEY (`computerId`) REFERENCES `computer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK3__user` FOREIGN KEY (`userId`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export dat nebyl vybrán.

-- Exportování struktury pro tabulka wol.user
CREATE TABLE IF NOT EXISTS `user` (
  `id` varchar(255) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('USER','ADMIN') NOT NULL DEFAULT 'USER',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `passwordChangedAt` datetime DEFAULT NULL,
  `passwordResetToken` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `passwordResetToken` (`passwordResetToken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export dat nebyl vybrán.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
