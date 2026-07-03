/*
 Navicat Premium Dump SQL

 Source Server         : LOCALHOST - MAMP
 Source Server Type    : MySQL
 Source Server Version : 80040 (8.0.40)
 Source Host           : localhost:8889
 Source Schema         : isp_billing

 Target Server Type    : MySQL
 Target Server Version : 80040 (8.0.40)
 File Encoding         : 65001

 Date: 17/01/2026 11:45:06
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for customers
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `package_id` int DEFAULT NULL,
  `odp_id` int DEFAULT NULL,
  `odp_port` int DEFAULT NULL,
  `mac_address` varchar(50) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `installation_date` datetime DEFAULT NULL,
  `due_date_day` int DEFAULT '10',
  `status` enum('active','suspended','terminated','pending') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `package_id` (`package_id`),
  KEY `customers_ibfk_2` (`odp_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `internet_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`odp_id`) REFERENCES `odp_points` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of customers
-- ----------------------------
BEGIN;
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (1, 'CUST1001', 'Imelda', NULL, '-', 'D1/02', NULL, NULL, 1, NULL, NULL, '98:C7:A4:5D:FC:71', '192.168.10.11', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:48');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (2, 'CUST1002', 'Hilwa', NULL, '-', 'D1/21', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:DE:96:A2', '192.168.10.32', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:51');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (3, 'CUST1003', 'Zahra', NULL, '-', 'D1/03', NULL, NULL, 1, NULL, NULL, '14:4D:67:36:A1:45', '192.168.10.29', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:52');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (4, 'CUST1004', 'Anis', NULL, '-', 'D5/06', NULL, NULL, 2, NULL, NULL, '98:C7:A4:4C:A7:50', '192.168.10.25', NULL, 10, 'suspended', '2026-01-17 10:51:27', '2026-01-17 11:01:43');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (5, 'CUST1005', 'Fikri', NULL, '-', 'D4/11', NULL, NULL, 1, NULL, NULL, '14:4D:67:0D:35:C5', '192.168.10.13', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:58');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (6, 'CUST1006', 'Dara', NULL, '-', 'D5/3', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:C2:6E:32', '192.168.10.23', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:00');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (7, 'CUST1007', 'Heri', NULL, '-', 'B6/10', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:05:1B:69', '192.168.10.53', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:53');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (8, 'CUST1008', 'Hari', NULL, '-', 'D1/7', NULL, NULL, 1, NULL, NULL, '14:4D:67:36:89:15', '192.168.10.31', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:01:02');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (9, 'CUST1009', 'Navisa', NULL, '-', 'D5/', NULL, NULL, 1, NULL, NULL, '78:44:76:F6:94:D1', '192.168.10.24', NULL, 10, 'suspended', '2026-01-17 10:51:27', '2026-01-17 11:00:06');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (11, 'CUST1011', 'Huda', NULL, '-', 'D1/25', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:36:58', '192.168.10.17', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (12, 'CUST1012', 'Akila', NULL, '-', 'D3/', NULL, NULL, 1, NULL, NULL, '00:D4:8F:41:33:CC', '192.168.10.36', NULL, 10, 'suspended', '2026-01-17 10:51:27', '2026-01-17 11:00:18');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (13, 'CUST1013', 'Anggit', NULL, '-', 'D2/02', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:B9:B8:AB', '192.168.10.49', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:20');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (14, 'CUST1014', 'Pradipta', NULL, '-', 'D2/4', NULL, NULL, 2, NULL, NULL, '14:4D:67:B7:A0:95', '192.168.10.50', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (15, 'CUST1015', 'Gunawan', NULL, '-', 'C5/31', NULL, NULL, 2, NULL, NULL, '40:62:EA:63:91:68', '192.168.10.43', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (16, 'CUST1016', 'Mesti', NULL, '-', 'D1/04', NULL, NULL, 1, NULL, NULL, '98:C7:A4:5D:DC:6D', '192.168.10.30', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:23');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (17, 'CUST1017', 'Dani', NULL, '-', 'C5/36', NULL, NULL, 2, NULL, NULL, '50:8C:F5:24:25:99', '192.168.10.45', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (18, 'CUST1018', 'Tarmidi', NULL, '-', 'C5/27', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:36:90', '192.168.10.84', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (19, 'CUST1019', 'Irfan', NULL, '-', 'D2/11', NULL, NULL, 1, NULL, NULL, '40:62:EA:8D:88:F1', '192.168.10.83', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:27');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (20, 'CUST1020', 'Syafia', NULL, '-', 'D4/8', NULL, NULL, 1, NULL, NULL, '00:D4:8F:41:37:FE', '192.168.10.39', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:30');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (21, 'CUST1021', 'Septo', NULL, '-', 'D4/07', NULL, NULL, 1, NULL, NULL, '00:D4:8F:41:37:E3', '192.168.10.19', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:32');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (22, 'CUST1022', 'Komar', NULL, '-', 'C2/27', NULL, NULL, 2, NULL, NULL, '98:C7:A4:13:06:E3', '192.168.10.44', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (23, 'CUST1023', 'Melody', NULL, '-', 'B7/32', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1F:8F:C3', '192.168.10.96', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (24, 'CUST1024', 'Lingga', NULL, '-', 'C6/02', NULL, NULL, 2, NULL, NULL, '80:D4:A5:4F:B5:00', '192.168.10.52', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (25, 'CUST1025', 'Ade', NULL, '-', 'B6/16', NULL, NULL, 2, NULL, NULL, '98:C7:A4:5E:8A:89', '192.168.10.48', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (26, 'CUST1026', 'Kenanatan', NULL, '-', 'C7/33', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:3C:C0:DB', '192.168.10.79', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (27, 'CUST1027', 'Marvel', NULL, '-', 'B7/6', NULL, NULL, 1, NULL, NULL, '18:69:DA:BA:10:29', '192.168.10.76', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:37');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (28, 'CUST1028', 'Fathiyah', NULL, '-', 'B7/2', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:3A:72:53', '192.168.10.75', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:38');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (29, 'CUST1029', 'Reyhan', NULL, '-', 'B7/11', NULL, NULL, 1, NULL, NULL, '18:69:DA:BB:2D:F1', '192.168.10.74', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:00:40');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (30, 'CUST1030', 'Adit', NULL, '-', 'C6/3', NULL, NULL, 2, NULL, NULL, '40:62:EA:E3:DB:30', '192.168.10.42', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (31, 'CUST1031', 'Riyanti', NULL, '-', 'C7/36', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:34:60', '192.168.10.99', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (32, 'CUST1032', 'Safania2', NULL, '-', 'C7/39', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2B:FD:4B', '192.168.10.165', NULL, 10, 'suspended', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (33, 'CUST1033', 'HendraSalmana', NULL, '-', 'C7/28', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:0D:9A:CA', '192.168.10.100', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (34, 'CUST1034', 'Subiyanto', NULL, '-', 'C5/02', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:34:9C', '192.168.10.101', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (35, 'CUST1035', 'Amanda', NULL, '-', 'C6/5', NULL, NULL, 1, NULL, NULL, '40:62:EA:BA:21:B0', '192.168.10.47', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:02:12');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (36, 'CUST1036', 'Charli', NULL, '-', 'C6/9', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:3E:CB:9B', '192.168.10.68', NULL, 10, 'suspended', '2026-01-17 10:51:27', '2026-01-17 11:02:14');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (37, 'CUST1037', 'John', NULL, '-', 'C7/27', NULL, NULL, 2, NULL, NULL, '18:69:DA:BA:7A:89', '192.168.10.69', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (38, 'CUST1038', 'Muhadi', NULL, '-', 'C7/31', NULL, NULL, 1, NULL, NULL, '98:C7:A4:12:DC:C7', '192.168.10.55', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:02:24');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (39, 'CUST1039', 'Kusnoyo', NULL, '-', 'C7/21', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:34:8C', '192.168.10.104', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (40, 'CUST1040', 'Iramaya', NULL, '-', 'B7/35', NULL, NULL, 2, NULL, NULL, '40:62:EA:AC:0E:B8', '192.168.10.71', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (41, 'CUST1041', 'Naufal', NULL, '-', 'C7/24', NULL, NULL, 2, NULL, NULL, '40:62:EA:2E:AC:D9', '192.168.10.105', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (42, 'CUST1042', 'Dede', NULL, '-', 'B7/15', NULL, NULL, 1, NULL, NULL, '40:62:EA:B0:C6:A0', '192.168.10.77', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:02:36');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (43, 'CUST1043', 'RezaMaulana', NULL, '-', 'C4/5', NULL, NULL, 2, NULL, NULL, '70:89:CC:71:BF:08', '192.168.10.102', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (44, 'CUST1044', 'Imam', NULL, '-', 'D3/6', NULL, NULL, 1, NULL, NULL, 'A4:7C:C9:58:6C:C8', '192.168.10.22', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:02:39');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (45, 'CUST1045', 'RandR', NULL, '-', 'B7/14', NULL, NULL, 2, NULL, NULL, '70:89:CC:8E:76:DC', '192.168.10.111', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (46, 'CUST1046', 'REY', NULL, '-', 'B7/12a', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1F:86:A3', '192.168.10.112', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (47, 'CUST1047', 'Devita', NULL, '-', 'D2/30', NULL, NULL, 2, NULL, NULL, '98:C7:A4:4C:AC:9C', '192.168.10.113', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (48, 'CUST1048', 'Syafaluna', NULL, '-', 'C7/17', NULL, NULL, 2, NULL, NULL, '70:89:CC:4F:E3:9A', '192.168.10.107', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (49, 'CUST1049', 'Eza', NULL, '-', 'C6/34', NULL, NULL, 2, NULL, NULL, 'C4:0D:96:91:31:0D', '192.168.10.81', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (50, 'CUST1050', 'Dylan', NULL, '-', 'C5/15', NULL, NULL, 2, NULL, NULL, '98:C7:A4:21:64:43', '192.168.10.63', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (51, 'CUST1051', 'Mahsya', NULL, '-', 'D2/35', NULL, NULL, 2, NULL, NULL, '70:89:CC:9D:04:8E', '192.168.10.46', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (52, 'CUST1052', 'Widinazahra', NULL, '-', 'C7/8', NULL, NULL, 2, NULL, NULL, '1C:3D:2F:7B:54:11', '192.168.10.116', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (53, 'CUST1053', 'Artha', NULL, '-', 'C6/29', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1F:80:07', '192.168.10.117', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (54, 'CUST1054', 'SML', NULL, '-', 'B6/14', NULL, NULL, 2, NULL, NULL, 'C0:FF:A8:11:88:C1', '192.168.10.97', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (55, 'CUST1055', 'Bunga', NULL, '-', 'C5/12a', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:36:C0', '192.168.10.80', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (56, 'CUST1056', 'Devin', NULL, '-', 'D3/12', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:17:D8', '192.168.10.12', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (57, 'CUST1057', 'Mahira', NULL, '-', 'C4/14', NULL, NULL, 2, NULL, NULL, '18:69:DA:36:9F:A1', '192.168.10.61', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (58, 'CUST1058', 'Alea', NULL, '-', 'D1/06', NULL, NULL, 2, NULL, NULL, '98:C7:A4:5D:E2:E1', '192.168.10.108', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (59, 'CUST1059', 'FirzaLutfian', NULL, '-', 'B7/34', NULL, NULL, 2, NULL, NULL, '40:62:EA:DB:6A:F0', '192.168.10.85', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (60, 'CUST1060', 'Safania', NULL, '-', 'C7/30', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:04:38', '192.168.10.103', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (61, 'CUST1061', 'FamilyFarm', NULL, '-', 'C8/4', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1D:26:17', '192.168.10.121', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (62, 'CUST1062', 'TK.Yohan', NULL, '-', 'B6/27', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:34:80', '192.168.10.122', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (63, 'CUST1063', 'Okta', NULL, '-', 'D5/14', NULL, NULL, 2, NULL, NULL, '58:D0:61:CC:4D:8F', '192.168.10.124', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (64, 'CUST1064', 'Fathan', NULL, '-', 'C5/11', NULL, NULL, 2, NULL, NULL, '98:C7:A4:12:FD:BF', '192.168.10.125', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (65, 'CUST1065', 'Faaz', NULL, '-', 'C7/2', NULL, NULL, 2, NULL, NULL, '40:62:EA:EE:D0:70', '192.168.10.126', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (66, 'CUST1066', 'Bandi', NULL, '-', 'C5/20', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:D7:79:5A', '192.168.10.38', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (67, 'CUST1067', 'Ricky', NULL, '-', 'D2/18', NULL, NULL, 1, NULL, NULL, '98:C7:A4:22:2B:88', '192.168.10.67', NULL, 10, 'active', '2026-01-17 10:51:27', '2026-01-17 11:06:39');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (68, 'CUST1068', 'Ica', NULL, '-', 'D3/', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:14:AB:21', '192.168.10.127', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:03:20');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (69, 'CUST1069', 'Wanto', NULL, '-', 'D2/16', NULL, NULL, 2, NULL, NULL, '40:62:EA:F0:0D:F8', '192.168.10.60', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (70, 'CUST1070', '7mamora', NULL, '-', 'C5/35', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2B:F2:57', '192.168.10.123', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (71, 'CUST1071', 'Manda', NULL, '-', 'D4/16', NULL, NULL, 1, NULL, NULL, '18:69:DA:2F:E1:89', '192.168.10.37', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:03:37');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (72, 'CUST1072', 'Hadi', NULL, '-', 'C5/38', NULL, NULL, 1, NULL, NULL, '54:DF:24:FA:F5:E0', '192.168.10.66', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:03:39');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (73, 'CUST1073', 'Aruni', NULL, '-', 'C2/15', NULL, NULL, 2, NULL, NULL, '50:8C:F5:19:7A:A9', '192.168.10.119', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (74, 'CUST1074', 'PutriCeria', NULL, '-', 'C5/28', NULL, NULL, 2, NULL, NULL, '40:62:EA:63:B7:B0', '192.168.10.128', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (75, 'CUST1075', 'Yuska', NULL, '-', 'D2/29', NULL, NULL, 2, NULL, NULL, '98:C7:A4:17:00:0B', '192.168.10.130', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (76, 'CUST1076', 'Adawi', NULL, '-', 'D5/16', NULL, NULL, 1, NULL, NULL, '98:C7:A4:2B:FD:DB', '192.168.10.26', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:03:51');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (77, 'CUST1077', 'Mastom', NULL, '-', 'C3/27', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:DF:D7:7A', '192.168.10.131', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (78, 'CUST1078', 'AndreDwi', NULL, '-', 'C2/11', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:CF:10:1A', '192.168.10.132', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (79, 'CUST1079', 'Historia', NULL, '-', 'B6/23', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:E3:9C:0A', '192.168.10.118', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (80, 'CUST1080', 'Mahadika', NULL, '-', 'C2/23', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:D3:D9:02', '192.168.10.59', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:03:55');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (81, 'CUST1081', 'Jenna', NULL, '-', 'D2/21', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:0B:AC', '192.168.10.137', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (82, 'CUST1082', 'Awaludin', NULL, '-', 'B6/12a', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:E0:DD:C2', '192.168.10.138', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (83, 'CUST1083', 'Anisa', NULL, '-', 'C2/32', NULL, NULL, 1, NULL, NULL, '98:C7:A4:13:08:DB', '192.168.10.51', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:07');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (84, 'CUST1084', 'AndiKancil', NULL, '-', 'G1/2', NULL, NULL, 2, NULL, NULL, '70:5D:CC:9D:A4:7D', '192.168.10.142', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (85, 'CUST1085', 'TokoKurnia', NULL, '-', 'G1/1', NULL, NULL, 2, NULL, NULL, '14:4D:67:27:7B:91', '192.168.10.141', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (86, 'CUST1086', 'Azzahra', NULL, '-', 'C7/35', NULL, NULL, 2, NULL, NULL, '98:C7:A4:0D:43:8C', '192.168.10.57', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (87, 'CUST1087', 'Yuda', NULL, '-', 'D4/12', NULL, NULL, 1, NULL, NULL, 'E0:45:6D:8F:8B:82', '192.168.10.21', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:21');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (88, 'CUST1088', 'RevanBacok', NULL, '-', 'D3/', NULL, NULL, 1, NULL, NULL, '00:D4:8F:21:30:83', '192.168.10.82', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:23');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (89, 'CUST1089', 'Irfan', NULL, '-', 'D2/28', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:30:B4', '192.168.10.139', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (90, 'CUST1090', 'Abraham', NULL, '-', 'B7/12', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:30:9C', '192.168.10.110', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (91, 'CUST1091', 'Hanina', NULL, '-', 'C6/30', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:30:6C', '192.168.10.140', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (92, 'CUST1092', 'AzzamFZ', NULL, '-', 'C5/16', NULL, NULL, 2, NULL, NULL, '58:D0:61:CB:BF:EE', '192.168.10.143', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (93, 'CUST1093', 'HudaGudang1', NULL, '-', 'C5/22', NULL, NULL, 2, NULL, NULL, '98:C7:A4:12:DC:67', '192.168.10.144', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (94, 'CUST1094', 'Rahman', NULL, '-', 'D1/23', NULL, NULL, 1, NULL, NULL, '88:36:6C:49:5C:A3', '192.168.10.15', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:31');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (95, 'CUST1095', 'Ricky', NULL, '-', 'C2/28', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:25:78', '192.168.10.40', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (96, 'CUST1096', 'Suratin', NULL, '-', 'D3/', NULL, NULL, 1, NULL, NULL, '00:D4:8F:31:32:A4', '192.168.10.145', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:45');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (97, 'CUST1097', 'Alika', NULL, '-', 'D2/', NULL, NULL, 1, NULL, NULL, '40:62:EA:63:70:10', '192.168.10.135', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:50');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (98, 'CUST1098', 'Saeful', NULL, '-', 'C7/11', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:2C:28', '192.168.10.146', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (99, 'CUST1099', 'Khafabian', NULL, '-', 'D2/09', NULL, NULL, 2, NULL, NULL, '98:C7:A4:54:FF:DC', '192.168.10.147', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (100, 'CUST1100', 'Zainakhan', NULL, '-', 'D4/3', NULL, NULL, 1, NULL, NULL, '18:69:DA:15:53:71', '192.168.10.16', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:04:53');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (101, 'CUST1101', 'DaniToko', NULL, '-', 'D2/', NULL, NULL, 2, NULL, NULL, '74:E1:9A:2F:8E:20', '192.168.10.64', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (102, 'CUST1102', 'Fairuz', NULL, '-', 'C5/30', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1D:28:E3', '192.168.10.58', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (103, 'CUST1103', 'Riki', NULL, '-', 'C6/28', NULL, NULL, 2, NULL, NULL, '98:C7:A4:17:01:FB', '192.168.10.114', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (104, 'CUST1104', 'Haisha', NULL, '-', 'B6/37', NULL, NULL, 2, NULL, NULL, '40:62:EA:79:C5:29', '192.168.10.129', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (105, 'CUST1105', 'Unik', NULL, '-', 'D3/12A', NULL, NULL, 1, NULL, NULL, '98:C7:A4:17:01:BB', '192.168.10.34', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:05:04');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (106, 'CUST1106', 'Alda', NULL, '-', 'D2/12', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:36:50', '192.168.10.149', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (107, 'CUST1107', 'Khanza', NULL, '-', 'C7/18', NULL, NULL, 2, NULL, NULL, '98:C7:A4:22:34:1C', '192.168.10.151', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (108, 'CUST1108', 'Rizky', NULL, '-', 'C2/16', NULL, NULL, 2, NULL, NULL, '98:C7:A4:34:04:8F', '192.168.10.28', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (109, 'CUST1109', 'Ripai', NULL, '-', 'D1/20', NULL, NULL, 1, NULL, NULL, 'C8:3A:35:19:C8:D8', '192.168.10.152', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:05:15');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (111, 'CUST1111', 'ARS', NULL, '-', 'C8/03', NULL, NULL, 2, NULL, NULL, '98:C7:A4:34:0E:BB', '192.168.10.154', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (112, 'CUST1112', 'Dayuqi', NULL, '-', 'C3/14', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1F:9C:33', '192.168.10.157', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (113, 'CUST1113', 'Hendra', NULL, '-', 'D3', NULL, NULL, 1, NULL, NULL, 'C0:FF:A8:13:06:D5', '192.168.10.35', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:05:26');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (114, 'CUST1114', 'Jihan', NULL, '-', 'C5/12', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:8C:56:7A', '192.168.10.158', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (115, 'CUST1115', 'CakImam', NULL, '-', 'C5/32', NULL, NULL, 1, NULL, NULL, '98:C7:A4:2B:F2:63', '192.168.10.159', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 11:05:30');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (116, 'CUST1116', 'Bayu', NULL, '-', 'D4/15', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2B:F2:8F', '192.168.10.160', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (117, 'CUST1117', 'Khaizuran', NULL, '-', 'D2/14', NULL, NULL, 2, NULL, NULL, '70:89:CC:55:1A:15', '192.168.10.161', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (118, 'CUST1118', 'Ayudya', NULL, '-', 'C7/34', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2B:F2:4F', '192.168.10.162', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (119, 'CUST1119', 'Mikayla', NULL, '-', 'D4/14', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:27:12:69', '192.168.10.163', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (120, 'CUST1120', 'Kristian', NULL, '-', 'A2/10', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:39:0C', '192.168.10.54', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (121, 'CUST1121', 'Farhan', NULL, '-', 'C4/09', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:0F:20', '192.168.10.41', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (122, 'CUST1122', 'Hendrik', NULL, '-', 'C4/12a', NULL, NULL, 2, NULL, NULL, '98:C7:A4:12:B6:8B', '192.168.10.166', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (123, 'CUST1123', 'FikiDesta', NULL, '-', 'C3/12a', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:37:4C', '192.168.10.167', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (124, 'CUST1124', 'Safania3', NULL, '-', 'C4/01', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:17:F0', '192.168.10.168', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (125, 'CUST1125', 'HawaAdani', NULL, '-', 'C4/18', NULL, NULL, 2, NULL, NULL, '98:C7:A4:1F:8F:A7', '192.168.10.169', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (126, 'CUST1126', 'RIlis', NULL, '-', 'C5/7', NULL, NULL, 2, NULL, NULL, '98:C7:A4:12:EB:BB', '192.168.10.86', NULL, 10, 'suspended', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (127, 'CUST1127', 'SariAgustina', NULL, '-', 'C5/09', NULL, NULL, 2, NULL, NULL, '98:C7:A4:21:63:07', '192.168.10.95', NULL, 10, 'suspended', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (128, 'CUST1128', 'YanitaSari', NULL, '-', 'C5/19', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:34:8C', '192.168.10.164', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (129, 'CUST1129', 'Haiban', NULL, '-', 'B6/29', NULL, NULL, 2, NULL, NULL, '98:C7:A4:21:6A:59', '192.168.10.72', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (130, 'CUST1130', 'SetyoUtomo', NULL, '-', 'D2/22', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:08:D8', '192.168.10.172', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (131, 'CUST1131', 'SyilaAlbi', NULL, '-', 'C7/07', NULL, NULL, 2, NULL, NULL, '1C:3D:2F:7B:26:6A', '192.168.10.173', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (132, 'CUST1132', 'Iqbal', NULL, '-', 'C5/14', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:41:44:0B', '192.168.10.62', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (133, 'CUST1133', 'Fikri', NULL, '-', 'C8/5', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:B1:AC:D2', '192.168.10.150', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (134, 'CUST1134', 'Defanka', NULL, '-', 'C2/19', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:3D:B4:03', '192.168.10.78', NULL, 10, 'suspended', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (135, 'CUST1135', 'Duki', NULL, '-', 'D2/25', NULL, NULL, 2, NULL, NULL, '50:0F:F5:24:86:70', '192.168.10.120', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (136, 'CUST1136', 'Nadine', NULL, '-', 'D2/19', NULL, NULL, 2, NULL, NULL, '40:62:EA:EF:81:20', '192.168.10.115', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (137, 'CUST1137', 'Aniss', NULL, '-', 'B7/21', NULL, NULL, 2, NULL, NULL, '98:C7:A4:2F:31:44', '192.168.10.87', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (138, 'CUST1138', 'Yusuf', NULL, '-', 'C5/25', NULL, NULL, 2, NULL, NULL, '18:69:DA:C8:4B:E9', '192.168.10.88', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (139, 'CUST1139', 'Frabas', NULL, '-', 'D2/08', NULL, NULL, 2, NULL, NULL, '98:C7:A4:12:B8:E3', '192.168.10.89', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (140, 'CUST1140', 'HudaGudang2', NULL, '-', 'C5/37', NULL, NULL, 2, NULL, NULL, '98:C7:A4:21:68:2B', '192.168.10.136', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (141, 'CUST1141', 'Dirjo', NULL, '-', 'C5/06', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:0B:68', '192.168.10.148', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (142, 'CUST1142', 'Joyycell', NULL, '-', 'D3/01', NULL, NULL, 2, NULL, NULL, 'E0:45:6D:F3:B0:8C', '192.168.10.70', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (143, 'CUST1143', 'Tiara', NULL, '-', 'B7/05', NULL, NULL, 2, NULL, NULL, '98:C7:A4:55:09:D8', '192.168.10.65', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (144, 'CUST1144', 'Danish', NULL, '-', 'D2/32', NULL, NULL, 2, NULL, NULL, '98:C7:A4:5E:0C:21', '192.168.10.18', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
INSERT INTO `customers` (`id`, `customer_code`, `name`, `email`, `phone`, `address`, `latitude`, `longitude`, `package_id`, `odp_id`, `odp_port`, `mac_address`, `ip_address`, `installation_date`, `due_date_day`, `status`, `created_at`, `updated_at`) VALUES (145, 'CUST1145', 'Anton', NULL, '-', 'C6/08', NULL, NULL, 2, NULL, NULL, '98:C7:A4:21:63:35', '192.168.10.27', NULL, 10, 'active', '2026-01-17 10:51:28', '2026-01-17 10:59:34');
COMMIT;

-- ----------------------------
-- Table structure for expenses
-- ----------------------------
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text,
  `amount` decimal(10,2) NOT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of expenses
-- ----------------------------
BEGIN;
INSERT INTO `expenses` (`id`, `expense_date`, `category`, `description`, `amount`, `proof_file`, `created_at`) VALUES (1, '2025-12-31', 'Biaya Bulanan ISP', 'Periode desember', 7900000.00, 'exp_1768027815_(anonymous) - Proposal_WMS_TVS.pdf', '2026-01-10 13:50:15');
COMMIT;

-- ----------------------------
-- Table structure for internet_packages
-- ----------------------------
DROP TABLE IF EXISTS `internet_packages`;
CREATE TABLE `internet_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `speed_mbps` int NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of internet_packages
-- ----------------------------
BEGIN;
INSERT INTO `internet_packages` (`id`, `package_name`, `price`, `speed_mbps`, `description`, `is_active`) VALUES (1, 'Basic Home', 120000.00, 10, 'Cocok untuk penggunaan ringan', 1);
INSERT INTO `internet_packages` (`id`, `package_name`, `price`, `speed_mbps`, `description`, `is_active`) VALUES (2, 'Fast Stream', 150000.00, 20, 'Sempurna untuk streaming HD', 1);
INSERT INTO `internet_packages` (`id`, `package_name`, `price`, `speed_mbps`, `description`, `is_active`) VALUES (3, 'Ultra Gamer', 250000.00, 30, 'Koneksi ultra cepat dan stabil', 1);
INSERT INTO `internet_packages` (`id`, `package_name`, `price`, `speed_mbps`, `description`, `is_active`) VALUES (4, 'Custom Package (By Request)', 0.00, 0, 'Silakan hubungi admin untuk paket ini', 1);
COMMIT;

-- ----------------------------
-- Table structure for invoices
-- ----------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int NOT NULL,
  `period_month` int NOT NULL,
  `period_year` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','pending','paid','overdue','cancelled') DEFAULT 'unpaid',
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of invoices
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for login_attempts
-- ----------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of login_attempts
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for odp_points
-- ----------------------------
DROP TABLE IF EXISTS `odp_points`;
CREATE TABLE `odp_points` (
  `id` int NOT NULL AUTO_INCREMENT,
  `odp_name` varchar(50) NOT NULL,
  `zone_area` varchar(100) DEFAULT NULL,
  `total_ports` int DEFAULT '8',
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `odp_name` (`odp_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of odp_points
-- ----------------------------
BEGIN;
INSERT INTO `odp_points` (`id`, `odp_name`, `zone_area`, `total_ports`, `latitude`, `longitude`, `notes`, `created_at`, `updated_at`) VALUES (1, 'ODP-D1-01', 'BLOK D1-D3', 8, '', '', 'Tiang CCTV depan warung pika', '2026-01-12 22:48:17', '2026-01-12 22:48:17');
COMMIT;

-- ----------------------------
-- Table structure for payments
-- ----------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `proof_file` varchar(255) DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `status` enum('verified','rejected') DEFAULT 'verified',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of payments
-- ----------------------------
BEGIN;
COMMIT;

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of roles
-- ----------------------------
BEGIN;
INSERT INTO `roles` (`id`, `role_name`) VALUES (1, 'Administrator');
INSERT INTO `roles` (`id`, `role_name`) VALUES (2, 'Finance');
INSERT INTO `roles` (`id`, `role_name`) VALUES (3, 'Technician');
COMMIT;

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of settings
-- ----------------------------
BEGIN;
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('app_icon', 'netmanage_logo.png');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('app_name', 'PCA Net Bill');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_address', 'Jl. Internet No. 1');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_name', 'My ISP Company');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('company_phone', '08123456789');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('mikrotik_enabled', '0');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('mikrotik_host', '');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('mikrotik_password', '');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('mikrotik_port', '8729');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('mikrotik_user', '');
COMMIT;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Records of users
-- ----------------------------
BEGIN;
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `created_at`) VALUES (1, 1, 'Super Admin', 'admin@isp.com', '$2y$10$azMeP.vM6Aht1yg6gjFiHeCjPE7sxOC34xsSrt9k7I14LB0g3s4j2', '2026-01-07 20:45:39');
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `created_at`) VALUES (2, 2, 'Finance Staff', 'finance@isp.com', '$2y$10$CextlUmGtqH7iFlcywdr3uhSouDKgtNfadq4Nr8VKb8bLB1yuYXQq', '2026-01-07 20:45:39');
INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `created_at`) VALUES (3, 3, 'Field Tech', 'tech@isp.com', '$2y$10$GdVuTV7cGqbBcEQX5DOmE.fGH7d7qDFHSkcp8TWCgjamM8UqC0B8S', '2026-01-07 20:45:39');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
