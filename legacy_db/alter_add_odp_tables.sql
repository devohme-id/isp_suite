-- Create ODP Points Table
CREATE TABLE IF NOT EXISTS `odp_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `odp_name` varchar(50) NOT NULL UNIQUE,
  `zone_area` varchar(100) DEFAULT NULL,
  `total_ports` int(11) DEFAULT 8,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alter Customers Table to add ODP mapping
ALTER TABLE `customers`
ADD COLUMN `odp_id` int(11) DEFAULT NULL after `package_id`,
ADD COLUMN `odp_port` int(11) DEFAULT NULL after `odp_id`;

ALTER TABLE `customers`
ADD CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`odp_id`) REFERENCES `odp_points` (`id`) ON DELETE SET NULL;
