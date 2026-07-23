-- AM4 Alliance Web Application Database Schema (v2)
-- Compatible with MySQL 5.6+ (InfinityFree)
-- Run this in your InfinityFree phpMyAdmin

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `airline_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `country` VARCHAR(80) NOT NULL,
  `alliance` VARCHAR(100) DEFAULT NULL,
  `alliance_custom` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('user','admin','owner') DEFAULT 'user',
  `is_banned` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `logo_zoom` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: alliances
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alliances` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `tag` VARCHAR(50) DEFAULT NULL,
  `tag_color` VARCHAR(20) DEFAULT '#6366f1',
  `value` VARCHAR(50) DEFAULT '$0',
  `member_count` INT(11) DEFAULT 0,
  `max_members` INT(11) DEFAULT 60,
  `rank` INT(11) DEFAULT 0,
  `requirements` TEXT DEFAULT NULL,
  `gradient_from` VARCHAR(20) DEFAULT '#4f46e5',
  `gradient_to` VARCHAR(20) DEFAULT '#6366f1',
  `border_color` VARCHAR(50) DEFAULT 'rgba(99,102,241,0.15)',
  `status` ENUM('active','inactive','recruiting') DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: alliance_custom_members
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alliance_custom_members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `alliance_id` INT(11) NOT NULL,
  `airline_name` VARCHAR(100) NOT NULL,
  `share_value` VARCHAR(50) DEFAULT NULL,
  `aircraft_count` INT(11) DEFAULT 0,
  `added_by` INT(11) DEFAULT NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`alliance_id`) REFERENCES `alliances`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: messages (group/chat rooms)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `room` VARCHAR(50) NOT NULL DEFAULT 'public',
  `message` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_room_created` (`room`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: direct_messages (private DMs / personal mail)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `direct_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `from_id` INT(11) NOT NULL,
  `to_id` INT(11) NOT NULL,
  `body` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`from_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_to_read` (`to_id`, `is_read`),
  INDEX `idx_thread` (`from_id`, `to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `alliance_id` INT(11) NOT NULL,
  `airline_name` VARCHAR(100) NOT NULL,
  `previous_alliances` VARCHAR(255) DEFAULT NULL,
  `share_value` VARCHAR(50) NOT NULL,
  `aircraft_count` INT(11) NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` INT(11) DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`alliance_id`) REFERENCES `alliances`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: bans
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `reason` TEXT NOT NULL,
  `banned_by` INT(11) NOT NULL,
  `banned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: appeals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appeals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` INT(11) DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: inactivity_notices
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inactivity_notices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `airline_name` VARCHAR(100) NOT NULL,
  `alliance` VARCHAR(100) NOT NULL,
  `days` INT(11) NOT NULL,
  `reason` VARCHAR(100) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_alliance` (`alliance`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Default Alliance Data
-- --------------------------------------------------------
INSERT INTO `alliances` (`name`,`tag`,`tag_color`,`value`,`member_count`,`max_members`,`rank`,`requirements`,`gradient_from`,`gradient_to`,`border_color`,`status`) VALUES
('SKY TEAM 2.0','FLAGSHIP','#6366f1','$8,420,000,000',42,60,1,'Min $100M value|50+ routes|Active daily','#4f46e5','#6366f1','rgba(99,102,241,0.15)','recruiting'),
('Aura Union','GROWING','#a855f7','$4,150,000,000',28,60,2,'Min $50M value|20+ routes|Friendly & helpful','#7c3aed','#a855f7','rgba(168,85,247,0.15)','active'),
('Prime United','ELITE','#f59e0b','$12,780,000,000',55,60,3,'Min $200M value|100+ routes|Invite only','#d97706','#f59e0b','rgba(245,158,11,0.15)','active');

-- --------------------------------------------------------
-- Default Owner Account (username: owner | password: ChangeMe123!)
-- --------------------------------------------------------
INSERT INTO `users` (`airline_name`,`username`,`password_hash`,`country`,`alliance`,`role`) VALUES
('Admin Airlines','owner','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Global','SKY TEAM 2.0','owner');
-- NOTE: Default password is "password" — CHANGE IMMEDIATELY after setup!

-- --------------------------------------------------------
-- MIGRATION: Run these if UPGRADING an existing installation
-- --------------------------------------------------------
-- ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_photo` VARCHAR(255) DEFAULT NULL;
-- ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `logo_zoom` TEXT DEFAULT NULL;
-- CREATE TABLE IF NOT EXISTS `direct_messages` ( ... ); -- (see full definition above)

-- --------------------------------------------------------
-- Table: site_settings (social links, etc.)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `key_name` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `site_settings` (`key_name`, `value`) VALUES
('discord_url', 'https://discord.gg/yourserver'),
('instagram_url', 'https://instagram.com/yourpage');
