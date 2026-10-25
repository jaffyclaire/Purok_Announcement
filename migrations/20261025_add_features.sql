-- Migration: 20261025_add_features.sql
-- Add image support, avatar, unique reaction, notifications adjustment, and events table

-- 1. Add file_path to attachments
ALTER TABLE `attachments`
  ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(255) NULL AFTER `file_type`;

-- 2. Add avatar_path to users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `avatar_path` VARCHAR(255) NULL AFTER `role`;

-- 3. Add unique constraint on reactions (announcement_id, user_id)
ALTER TABLE `reactions`
  ADD UNIQUE KEY `uq_announcement_user` (`announcement_id`, `user_id`);

-- 4. Adjust notifications table to allow null announcement_id and add event_id
ALTER TABLE `notifications`
  MODIFY COLUMN `announcement_id` INT(11) NULL,
  ADD COLUMN IF NOT EXISTS `event_id` INT(11) NULL AFTER `announcement_id`;

-- 5. Create events table
CREATE TABLE IF NOT EXISTS `events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `FK_events_created_by` (`created_by`),
  CONSTRAINT `FK_events_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

