-- Add MikroTik settings to settings table
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
('mikrotik_enabled', '0'),
('mikrotik_host', ''),
('mikrotik_port', '8729'),
('mikrotik_user', ''),
('mikrotik_password', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
