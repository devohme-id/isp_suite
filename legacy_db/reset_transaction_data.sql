-- Script to truncate transaction data only
-- Preserves: roles, users, internet_packages
-- Clears: payments, invoices, customers

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE payments;
TRUNCATE TABLE invoices;
TRUNCATE TABLE customers;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Transaction data has been reset.' as status;
