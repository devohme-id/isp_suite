-- Phase 1 SQL Migration: FTTH Network Schema

START TRANSACTION;

-- 1. Create OLTs Table
CREATE TABLE IF NOT EXISTS `olts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `olt_name` varchar(100) NOT NULL UNIQUE,
  `olt_model` varchar(100) DEFAULT NULL,
  `olt_type` enum('GPON','EPON') NOT NULL DEFAULT 'GPON',
  `ip_address` varchar(45) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `total_ports` int(11) NOT NULL DEFAULT 8,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create OLT Ports Table
CREATE TABLE IF NOT EXISTS `olt_ports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `olt_id` int(11) NOT NULL,
  `port_number` int(11) NOT NULL,
  `port_label` varchar(50) DEFAULT NULL,
  `port_type` enum('GPON', 'EPON', 'XGS-PON') NOT NULL DEFAULT 'GPON',
  `status` enum('active', 'inactive', 'fault') NOT NULL DEFAULT 'inactive',
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `olt_port_unique` (`olt_id`, `port_number`),
  CONSTRAINT `fk_olt_ports_olt` FOREIGN KEY (`olt_id`) REFERENCES `olts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create Backbones Table
CREATE TABLE IF NOT EXISTS `backbones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backbone_code` varchar(50) NOT NULL UNIQUE,
  `olt_port_id` int(11) DEFAULT NULL,
  `cable_type` varchar(100) DEFAULT NULL,
  `total_tubes` int(11) NOT NULL DEFAULT 1,
  `cores_per_tube` int(11) NOT NULL DEFAULT 12,
  `route_description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_backbones_olt_port` FOREIGN KEY (`olt_port_id`) REFERENCES `olt_ports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create Backbone Cores Table
CREATE TABLE IF NOT EXISTS `backbone_cores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backbone_id` int(11) NOT NULL,
  `tube_number` int(11) NOT NULL,
  `core_number` int(11) NOT NULL,
  `status` enum('active', 'idle', 'reserved', 'fault') NOT NULL DEFAULT 'idle',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `backbone_tube_core_unique` (`backbone_id`, `tube_number`, `core_number`),
  CONSTRAINT `fk_backbone_cores_backbone` FOREIGN KEY (`backbone_id`) REFERENCES `backbones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create RK Points Table
CREATE TABLE IF NOT EXISTS `rk_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rk_name` varchar(100) NOT NULL UNIQUE,
  `location_description` varchar(255) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create RK Connections Table
CREATE TABLE IF NOT EXISTS `rk_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rk_id` int(11) NOT NULL,
  `backbone_core_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `backbone_core_unique` (`backbone_core_id`),
  CONSTRAINT `fk_rk_connections_rk` FOREIGN KEY (`rk_id`) REFERENCES `rk_points` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rk_connections_core` FOREIGN KEY (`backbone_core_id`) REFERENCES `backbone_cores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Create Distributions Table
CREATE TABLE IF NOT EXISTS `distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dist_code` varchar(50) NOT NULL UNIQUE,
  `rk_id` int(11) DEFAULT NULL,
  `cable_type` varchar(100) DEFAULT NULL,
  `total_tubes` int(11) NOT NULL DEFAULT 1,
  `cores_per_tube` int(11) NOT NULL DEFAULT 12,
  `coverage_area` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_distributions_rk` FOREIGN KEY (`rk_id`) REFERENCES `rk_points` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Create Distribution Cores Table
CREATE TABLE IF NOT EXISTS `distribution_cores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `distribution_id` int(11) NOT NULL,
  `tube_number` int(11) NOT NULL,
  `core_number` int(11) NOT NULL,
  `status` enum('active', 'idle', 'reserved', 'fault') NOT NULL DEFAULT 'idle',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dist_tube_core_unique` (`distribution_id`, `tube_number`, `core_number`),
  CONSTRAINT `fk_distribution_cores_dist` FOREIGN KEY (`distribution_id`) REFERENCES `distributions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Check if odp_points exists before renaming
SET @table_exists = (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'odp_points');
SET @sql_rename = IF(@table_exists > 0, 'RENAME TABLE odp_points TO drop_points', 'SELECT "Table odp_points already renamed or does not exist" AS msg');
PREPARE stmt_rename FROM @sql_rename;
EXECUTE stmt_rename;
DEALLOCATE PREPARE stmt_rename;

-- 10. Update drop_points table structure
-- Add dp_code if it does not exist
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'drop_points' AND column_name = 'dp_code');
SET @sql_add_code = IF(@col_exists = 0, 'ALTER TABLE drop_points ADD COLUMN dp_code VARCHAR(20) UNIQUE AFTER id', 'SELECT "Column dp_code already exists" AS msg');
PREPARE stmt_add_code FROM @sql_add_code;
EXECUTE stmt_add_code;
DEALLOCATE PREPARE stmt_add_code;

-- Add dist_core_id if it does not exist
SET @col_dist_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'drop_points' AND column_name = 'dist_core_id');
SET @sql_add_dist = IF(@col_dist_exists = 0, 'ALTER TABLE drop_points ADD COLUMN dist_core_id INT(11) DEFAULT NULL AFTER dp_code', 'SELECT "Column dist_core_id already exists" AS msg');
PREPARE stmt_add_dist FROM @sql_add_dist;
EXECUTE stmt_add_dist;
DEALLOCATE PREPARE stmt_add_dist;

-- Rename odp_name to dp_name if it exists
SET @col_odp_name_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'drop_points' AND column_name = 'odp_name');
SET @sql_rename_name = IF(@col_odp_name_exists > 0, 'ALTER TABLE drop_points CHANGE COLUMN odp_name dp_name VARCHAR(50) NOT NULL', 'SELECT "Column odp_name already renamed" AS msg');
PREPARE stmt_rename_name FROM @sql_rename_name;
EXECUTE stmt_rename_name;
DEALLOCATE PREPARE stmt_rename_name;

-- Add foreign key constraint to drop_points for dist_core_id
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'drop_points' AND constraint_name = 'fk_dp_dist_core');
SET @sql_constraint = IF(@constraint_exists = 0, 'ALTER TABLE drop_points ADD CONSTRAINT fk_dp_dist_core FOREIGN KEY (dist_core_id) REFERENCES distribution_cores(id) ON DELETE SET NULL', 'SELECT "Constraint fk_dp_dist_core already exists" AS msg');
PREPARE stmt_constraint FROM @sql_constraint;
EXECUTE stmt_constraint;
DEALLOCATE PREPARE stmt_constraint;

-- 11. Update customers table column names and foreign keys
-- Drop existing foreign key customers_ibfk_2 if it exists
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'customers' AND constraint_name = 'customers_ibfk_2');
SET @sql_drop_fk = IF(@fk_exists > 0, 'ALTER TABLE customers DROP FOREIGN KEY customers_ibfk_2', 'SELECT "FK customers_ibfk_2 does not exist" AS msg');
PREPARE stmt_drop_fk FROM @sql_drop_fk;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

-- Rename odp_id to dp_id if odp_id exists
SET @col_odp_id_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'odp_id');
SET @sql_rename_odp_id = IF(@col_odp_id_exists > 0, 'ALTER TABLE customers CHANGE COLUMN odp_id dp_id INT(11) DEFAULT NULL', 'SELECT "Column odp_id already renamed" AS msg');
PREPARE stmt_rename_odp_id FROM @sql_rename_odp_id;
EXECUTE stmt_rename_odp_id;
DEALLOCATE PREPARE stmt_rename_odp_id;

-- Rename odp_port to dp_port if odp_port exists
SET @col_odp_port_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = 'odp_port');
SET @sql_rename_odp_port = IF(@col_odp_port_exists > 0, 'ALTER TABLE customers CHANGE COLUMN odp_port dp_port INT(11) DEFAULT NULL', 'SELECT "Column odp_port already renamed" AS msg');
PREPARE stmt_rename_odp_port FROM @sql_rename_odp_port;
EXECUTE stmt_rename_odp_port;
DEALLOCATE PREPARE stmt_rename_odp_port;

-- Add new foreign key constraint fk_customer_dp if it does not exist
SET @fk_cust_dp_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'customers' AND constraint_name = 'fk_customer_dp');
SET @sql_fk_cust_dp = IF(@fk_cust_dp_exists = 0, 'ALTER TABLE customers ADD CONSTRAINT fk_customer_dp FOREIGN KEY (dp_id) REFERENCES drop_points(id) ON DELETE SET NULL', 'SELECT "Constraint fk_customer_dp already exists" AS msg');
PREPARE stmt_fk_cust_dp FROM @sql_fk_cust_dp;
EXECUTE stmt_fk_cust_dp;
DEALLOCATE PREPARE stmt_fk_cust_dp;

-- 12. Fill in default dp_code for existing drop points if null
UPDATE drop_points SET dp_code = CONCAT('DP-GEN-', id) WHERE dp_code IS NULL OR dp_code = '';

COMMIT;
