ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'suspended', 'terminated', 'pending') DEFAULT 'pending';
