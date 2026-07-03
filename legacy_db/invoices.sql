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

 Date: 09/01/2026 20:44:46
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
