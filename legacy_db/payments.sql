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

 Date: 09/01/2026 20:44:39
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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

SET FOREIGN_KEY_CHECKS = 1;
