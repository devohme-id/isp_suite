SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `isp_billing`
--

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Administrator'),
(2, 'Finance'),
(3, 'Technician');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--
-- Password is 'admin123' (hashed)
INSERT INTO `users` (`role_id`, `name`, `email`, `password`) VALUES
(1, 'Super Admin', 'admin@isp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'Finance Staff', 'finance@isp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'Field Tech', 'tech@isp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------

--
-- Table structure for table `internet_packages`
--

CREATE TABLE `internet_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `speed_mbps` int(11) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `internet_packages`
--

INSERT INTO `internet_packages` (`package_name`, `price`, `speed_mbps`, `description`) VALUES
('Basic Home', 150000.00, 10, 'Cocok untuk penggunaan ringan'),
('Fast Stream', 250000.00, 30, 'Sempurna untuk streaming HD'),
('Ultra Gamer', 450000.00, 100, 'Koneksi ultra cepat dan stabil');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `installation_date` date NOT NULL,
  `due_date_day` int(2) DEFAULT 10,
  `status` enum('active','suspended','terminated') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `internet_packages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `period_month` int(2) NOT NULL,
  `period_year` int(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','pending','paid','overdue','cancelled') DEFAULT 'unpaid',
  `generated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `due_date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `proof_file` varchar(255) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `status` enum('verified','rejected') DEFAULT 'verified',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text,
  `amount` decimal(10,2) NOT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('app_name', 'ISP Billing'),
('app_icon', 'default_icon.png'),
('company_name', 'My ISP Company'),
('company_address', 'Jl. Internet No. 1'),
('company_phone', '08123456789');
