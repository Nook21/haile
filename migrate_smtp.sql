-- Run this if you already imported database.sql
-- Adds SMTP email configuration settings

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('smtp_host',       ''),
('smtp_port',       '587'),
('smtp_username',   ''),
('smtp_password',   ''),
('smtp_encryption', 'tls'),
('smtp_from_name',  'Amanuel Yohannes'),
('smtp_from_email', ''),
('smtp_enabled',    '0');
