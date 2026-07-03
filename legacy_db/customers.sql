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

 Date: 09/01/2026 19:41:07
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
  `package_id` int NOT NULL,
  `installation_date` date NOT NULL,
  `due_date_day` int DEFAULT '10',
  `status` enum('active','suspended','terminated') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `internet_packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
