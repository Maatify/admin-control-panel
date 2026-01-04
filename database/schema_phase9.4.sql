CREATE TABLE IF NOT EXISTS `failed_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `channel` VARCHAR(50) NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `reason` TEXT NOT NULL,
    `attempts` INT NOT NULL DEFAULT 1,
    `last_attempt_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL,
    INDEX `idx_failed_channel` (`channel`),
    INDEX `idx_failed_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
