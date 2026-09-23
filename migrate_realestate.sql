-- Add missing settings keys
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('contact_phone_2',    ''),
('contact_whatsapp',   ''),
('contact_telegram',   ''),
('social_whatsapp',    ''),
('investment_section_title', 'Invest with perspective.'),
('investment_section_body',  'Real estate decisions require more than finding a property. They require understanding location, opportunity, value and long-term potential.\n\nHaile Real Estate Advisor helps clients approach property decisions with a strategic perspective.'),
('investment_bg_image', '');

-- Testimonials table
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `person_title` varchar(150) DEFAULT NULL,
  `body` text NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `testimonials` (`name`, `person_title`, `body`, `rating`, `display_order`, `status`) VALUES
('Selam T.', 'Residential Client', 'Haile understood exactly what we were looking for and guided us through every stage with patience and clarity. We felt informed rather than sold to.', 5, 1, 'active'),
('Daniel A.', 'Investment Client', 'His perspective on location value changed how I evaluate opportunities. The commercial space we acquired has performed beyond our expectations.', 5, 2, 'active'),
('Meron G.', 'Property Owner', 'Professional, transparent and genuinely responsive. Working with an advisor rather than an agent made all the difference.', 5, 3, 'active');
