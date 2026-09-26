-- Run this in phpMyAdmin → haile database → SQL tab

-- 1. Add brand_video setting (safe to run even if already exists)
INSERT INTO `settings` (`setting_key`, `setting_value`)
VALUES ('brand_video', '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- 2. Create sponsors table if it doesn't exist
CREATE TABLE IF NOT EXISTS `sponsors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT 'Sponsor',
  `logo` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create uploads/brand directory placeholder (handled by PHP, no SQL needed)
-- Done.
