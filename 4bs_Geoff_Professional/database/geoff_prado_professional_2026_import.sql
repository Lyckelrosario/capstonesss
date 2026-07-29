CREATE DATABASE IF NOT EXISTS `geoff_prado_professional_2026` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `geoff_prado_professional_2026`;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_conversations`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `mechanics`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50) NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','client') NOT NULL DEFAULT 'client',
  `avatar_path` VARCHAR(255) NULL,
  `remember_token` VARCHAR(100) NULL,
  `email_verified_at` TIMESTAMP NULL,
  `onboarding_completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mechanics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `specialty` VARCHAR(255) NOT NULL,
  `experience` VARCHAR(255) NULL,
  `photo` VARCHAR(255) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 60,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(50) NOT NULL DEFAULT 'pcs',
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) NULL,
  `status` ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `mechanic_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_brand` VARCHAR(255) NOT NULL,
  `vehicle_model` VARCHAR(255) NOT NULL,
  `plate_number` VARCHAR(255) NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `issue_description` TEXT NULL,
  `ai_diagnosis` TEXT NULL,
  `status` ENUM('pending','approved','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_slot_status_idx` (`mechanic_id`,`appointment_date`,`appointment_time`,`status`),
  KEY `appointments_user_id_idx` (`user_id`),
  KEY `appointments_service_id_idx` (`service_id`),
  CONSTRAINT `appointments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_mechanic_fk` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_service_fk` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `chat_conversations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_admin_id` BIGINT UNSIGNED NULL,
  `status` ENUM('ai','waiting_for_admin','live','closed') NOT NULL DEFAULT 'ai',
  `subject` VARCHAR(255) NOT NULL DEFAULT 'Customer support',
  `live_requested_at` TIMESTAMP NULL,
  `claimed_at` TIMESTAMP NULL,
  `closed_at` TIMESTAMP NULL,
  `last_message_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chat_conversations_status_last_idx` (`status`,`last_message_at`),
  KEY `chat_conversations_user_id_idx` (`user_id`),
  CONSTRAINT `chat_conversations_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_conversations_admin_fk` FOREIGN KEY (`assigned_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `sender_id` BIGINT UNSIGNED NULL,
  `sender_type` ENUM('user','ai','admin','system') NOT NULL,
  `body` TEXT NOT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_conversation_id_idx` (`conversation_id`,`id`),
  KEY `chat_messages_sender_id_idx` (`sender_id`),
  CONSTRAINT `chat_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `sold_at` DATE NOT NULL,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `sales_product_id_idx` (`product_id`),
  CONSTRAINT `sales_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `mechanic_id` BIGINT UNSIGNED NOT NULL,
  `appointment_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `shop_rating` INT NOT NULL,
  `mechanic_rating` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_user_id_idx` (`user_id`),
  KEY `feedback_mechanic_id_idx` (`mechanic_id`),
  CONSTRAINT `feedback_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_mechanic_fk` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_appointment_fk` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`name`,`email`,`phone`,`password`,`role`,`email_verified_at`,`created_at`,`updated_at`) VALUES
('4BS Admin','admin@4bs.test','09000000000','$2y$12$O2W8vwK9qH2xcB7nfT4qx.8CTIHlYOkYO3qPDrr79x42DNYKUGwra','admin',NOW(),NOW(),NOW()),
('Sample Client','client@4bs.test','09123456789','$2y$12$m7/XYKOP..cn.pz0FAlqNO3gMUC6iF6YWq7L1c689vJP55AnNOTa2','client',NOW(),NOW(),NOW());

INSERT INTO `mechanics` (`name`,`specialty`,`experience`,`status`,`created_at`,`updated_at`) VALUES
('Mark Dela Cruz','Brake and underchassis','5 years','active',NOW(),NOW()),
('Joel Santos','Engine tune-up','7 years','active',NOW(),NOW()),
('Rico Reyes','Electrical diagnosis','4 years','active',NOW(),NOW());

INSERT INTO `services` (`name`,`description`,`price`,`duration_minutes`,`status`,`created_at`,`updated_at`) VALUES
('Brake Inspection','Brake pads, fluid, pedal response and safety check.',450.00,60,'active',NOW(),NOW()),
('Oil Change','Oil drain, replacement and basic engine check.',650.00,45,'active',NOW(),NOW()),
('Engine Check-up','Basic diagnosis for minor engine issues.',800.00,90,'active',NOW(),NOW());

INSERT INTO `products` (`brand`,`name`,`category`,`quantity`,`unit`,`price`,`status`,`created_at`,`updated_at`) VALUES
('Motul','Motul Oil 10W-40','Engine Oil',40,'bottle',380.00,'available',NOW(),NOW()),
('Honda','Brake Fluid DOT 4','Brake Fluid',25,'bottle',220.00,'available',NOW(),NOW()),
('NGK','Spark Plug','Ignition',60,'pcs',180.00,'available',NOW(),NOW());
