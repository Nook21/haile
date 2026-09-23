SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `haile` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `haile`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin') NOT NULL DEFAULT 'admin',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` varchar(300) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `testimonial` text DEFAULT NULL,
  `person_title` varchar(150) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `short_description` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `year` year DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `favorite` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('published','draft') NOT NULL DEFAULT 'draft',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `price` varchar(100) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `fk_project_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `type` enum('image','video') NOT NULL DEFAULT 'image',
  `file_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `fk_media_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `about` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `profile_image_dark` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `insights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `excerpt` varchar(400) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'draft',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Admin user (password: changeme123 — visit /setup.php to change)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Haile Estate', 'admin@haile.local', '$2y$12$LcMqUMoN9oqFqFqFqFqFquXvXvXvXvXvXvXvXvXvXvXvXvXvXvXu', 'admin');

INSERT INTO `categories` (`name`, `slug`, `description`, `display_order`) VALUES
('Luxury Residential', 'residential', 'Curated luxury homes, villas and private residences across Addis Ababa.', 1),
('Commercial', 'commercial', 'Strategically positioned commercial spaces with strong business potential.', 2),
('Investment', 'luxury-villas', 'High-yield investment properties with strong long-term capital growth.', 3),
('Land & Development', 'land-plots', 'Prime land and development opportunities across Ethiopia.', 4);

INSERT INTO `projects` (`category_id`, `title`, `slug`, `short_description`, `description`, `year`, `cover_image`, `featured`, `favorite`, `status`, `display_order`, `location`, `price`, `bedrooms`, `bathrooms`, `area`) VALUES
(3, 'Villa Serenity', 'villa-serenity', 'A masterpiece of modern luxury nestled in the hills of Addis Ababa.', 'Villa Serenity redefines luxury living with its seamless blend of contemporary architecture and natural surroundings. Every detail has been crafted to offer an unparalleled living experience, from the infinity pool overlooking the city to the bespoke interior finishes sourced from around the world.', 2024, NULL, 1, 1, 'published', 1, 'Bole, Addis Ababa', '$2,400,000', 5, 4, '620 sqm'),
(1, 'The Residence at Kazanchis', 'residence-kazanchis', 'Sophisticated urban living in the heart of the diplomatic quarter.', 'The Residence at Kazanchis offers a rare opportunity to own a piece of Addis Ababa''s most prestigious address. Designed for the discerning buyer, each unit features floor-to-ceiling windows, premium finishes, and access to world-class amenities.', 2024, NULL, 1, 1, 'published', 2, 'Kazanchis, Addis Ababa', '$890,000', 3, 2, '280 sqm'),
(4, 'Skyline Penthouse', 'skyline-penthouse', 'An iconic penthouse with panoramic views of the Addis Ababa skyline.', 'Perched atop one of Addis Ababa''s most iconic towers, the Skyline Penthouse is a statement of architectural ambition. The double-height living spaces, private rooftop terrace, and bespoke kitchen make this a truly one-of-a-kind residence.', 2023, NULL, 1, 0, 'published', 3, 'Bole Atlas, Addis Ababa', '$1,750,000', 4, 3, '450 sqm'),
(2, 'Heritage Business Centre', 'heritage-business-centre', 'Grade-A commercial space in Addis Ababa''s emerging business district.', 'Heritage Business Centre sets a new standard for commercial real estate in Ethiopia. With LEED-certified construction, smart building technology, and flexible floor plates, it is designed to attract the world''s leading corporations.', 2023, NULL, 0, 0, 'published', 4, 'CMC, Addis Ababa', '$3,200,000', NULL, NULL, '1,800 sqm'),
(3, 'Entoto Ridge Estate', 'entoto-ridge-estate', 'A private estate commanding breathtaking views of Entoto Mountain.', 'Entoto Ridge Estate is a rare private compound offering absolute privacy and uninterrupted views of Entoto Mountain. The estate features a main residence, guest house, and landscaped gardens designed by award-winning landscape architects.', 2024, NULL, 0, 1, 'published', 5, 'Entoto, Addis Ababa', '$4,100,000', 6, 5, '1,200 sqm'),
(1, 'Bole Medhanialem Townhouse', 'bole-medhanialem-townhouse', 'Contemporary townhouse living steps from Bole Medhanialem Church.', 'A collection of meticulously designed townhouses offering the perfect balance of privacy and community. Each home features a private garden, rooftop terrace, and double garage, finished to the highest specification.', 2023, NULL, 0, 0, 'published', 6, 'Bole Medhanialem, Addis Ababa', '$620,000', 4, 3, '320 sqm');

INSERT INTO `services` (`title`, `description`, `icon`, `display_order`, `status`) VALUES
('Property Sales', 'Expert guidance through every step of buying or selling premium residential and commercial properties across Ethiopia.', 'bi-building', 1, 'active'),
('Investment Advisory', 'Strategic real estate investment advice tailored to your portfolio goals, from single assets to large-scale developments.', 'bi-graph-up-arrow', 2, 'active'),
('Property Management', 'Comprehensive management services ensuring your investment performs at its peak, from tenant relations to maintenance.', 'bi-shield-check', 3, 'active'),
('Development Consulting', 'End-to-end consulting for real estate developers, from site acquisition and feasibility through to project delivery.', 'bi-layers', 4, 'active'),
('Valuation Services', 'Independent, RICS-aligned property valuations for acquisition, disposal, financing, and insurance purposes.', 'bi-clipboard-data', 5, 'active'),
('Relocation Services', 'Seamless relocation support for executives and families moving to Addis Ababa, including property search and settling-in services.', 'bi-geo-alt', 6, 'active');

INSERT INTO `about` (`name`, `title`, `biography`, `profile_image`) VALUES
('Haile Estate', 'Luxury Real Estate',
'Haile Estate is Addis Ababa''s premier luxury real estate firm, founded on the belief that exceptional properties deserve exceptional representation. We curate a portfolio of the finest residential and commercial properties across Ethiopia, connecting discerning buyers with homes that reflect their ambitions.\n\nOur team of experienced advisors brings deep local knowledge and an international perspective to every transaction. We understand that buying or selling a property is one of the most significant decisions you will make, and we are committed to making that journey seamless, transparent, and rewarding.\n\nFrom intimate family residences to landmark commercial developments, Haile Estate is the trusted partner for those who demand the very best.',
NULL);

INSERT INTO `insights` (`title`, `slug`, `excerpt`, `body`, `category`, `status`, `display_order`) VALUES
('The Rise of Luxury Real Estate in Addis Ababa', 'rise-of-luxury-real-estate-addis-ababa', 'Addis Ababa is experiencing an unprecedented surge in luxury property development. We explore the forces driving this transformation.', 'Addis Ababa is experiencing an unprecedented surge in luxury property development. International investors, a growing affluent middle class, and a wave of diaspora returnees are reshaping the city''s skyline and redefining what luxury living means in East Africa.\n\nThe demand for premium residential properties has grown by over 40% in the past three years, driven by a combination of economic growth, improved infrastructure, and a cultural shift towards homeownership among Ethiopia''s professional class.\n\nAt Haile Estate, we have witnessed this transformation firsthand. The properties we represent today would have been unimaginable a decade ago — penthouses with panoramic city views, private villas with infinity pools, and commercial spaces that rival anything in Nairobi or Dubai.', 'Market Insights', 'published', 1),
('How to Choose the Right Neighbourhood in Addis Ababa', 'choose-right-neighbourhood-addis-ababa', 'From the diplomatic enclave of Kazanchis to the leafy hills of Entoto, each neighbourhood offers a distinct lifestyle. Our guide helps you find your perfect match.', 'Choosing the right neighbourhood is as important as choosing the right property. Addis Ababa''s diverse districts each offer a unique character, lifestyle, and investment profile.\n\nBole remains the city''s most cosmopolitan address, home to international embassies, five-star hotels, and the finest dining. Kazanchis offers a quieter, more diplomatic atmosphere, while the Entoto hills provide an escape from the urban energy with cooler temperatures and spectacular views.\n\nFor families, the CMC and Ayat areas offer spacious plots, good schools, and a strong sense of community. For investors, the emerging corridors along the new light rail lines present compelling opportunities for capital appreciation.', 'Lifestyle', 'published', 2),
('Understanding Property Investment Returns in Ethiopia', 'property-investment-returns-ethiopia', 'Ethiopia''s real estate market offers some of the most compelling investment returns in Africa. Here is what every investor needs to know.', 'Ethiopia''s real estate market has consistently delivered strong returns for investors who understand the market dynamics. Rental yields in prime Addis Ababa locations range from 6% to 9% annually, while capital appreciation in emerging neighbourhoods has exceeded 15% per year in recent cycles.\n\nThe key to successful property investment in Ethiopia lies in understanding the regulatory environment, the importance of title deed verification, and the role of local partnerships in navigating the market.\n\nAt Haile Estate, our investment advisory team provides clients with comprehensive market analysis, due diligence support, and ongoing portfolio management to ensure their investments perform at their full potential.', 'Investment', 'published', 3);

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_title',            'Haile Estate'),
('site_description',      'Addis Ababa''s premier luxury real estate firm. Discover exceptional properties across Ethiopia.'),
('designer_title',        'Luxury Real Estate'),
('contact_email',         ''),
('contact_phone',         ''),
('contact_location',      'Addis Ababa, Ethiopia'),
('contact_location_url',  ''),
('social_instagram',      ''),
('social_linkedin',       ''),
('social_facebook',       ''),
('social_twitter',        ''),
('social_telegram',       ''),
('copyright_text',        '© 2025 Haile Estate. All rights reserved.'),
('hero_title',            'Where Luxury Meets Home.'),
('hero_subtitle',         'Addis Ababa''s most exceptional properties, curated for those who demand the very best.'),
('hero_cta_text',         'View Properties'),
('hero_eyebrow',          'Senior Property Consultant / Managing Director'),
('hero_bg_image',         ''),
('show_clients',          '1'),
('show_about',            '1'),
('show_services',         '1'),
('show_sponsors',         '0'),
('favicon',               ''),
('profile_image',         ''),
('contact_section_body',  'Whether you are buying, selling, or investing, our team of expert advisors is ready to guide you through every step of your property journey.'),
('smtp_enabled',          '0'),
('smtp_host',             ''),
('smtp_port',             '587'),
('smtp_username',         ''),
('smtp_password',         ''),
('smtp_encryption',       'tls'),
('smtp_from_name',        'Haile Estate'),
('smtp_from_email',       '');
