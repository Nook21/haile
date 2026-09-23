-- ============================================================
-- Migration: Sponsors + About dark image + new settings
-- Run once via phpMyAdmin or: mysql -u root portfolio < migrate.sql
-- ============================================================

USE `portfolio`;

-- Sponsors table
CREATE TABLE IF NOT EXISTS `sponsors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dark mode profile image column
ALTER TABLE `about`
  ADD COLUMN IF NOT EXISTS `profile_image_dark` varchar(255) DEFAULT NULL AFTER `profile_image`;

-- New settings keys
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('contact_section_heading',    'Start a conversation.'),
('contact_section_subheading', 'Get In Touch'),
('contact_section_body',       'Have a project in mind? I''d love to hear about it. Send a message and I''ll get back to you.'),
('show_sponsors',              '1'),
('designer_title',             'Graphic Designer');
