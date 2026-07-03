-- ===========================================
-- RESET DATABASE ISP_BILLING
-- ===========================================
-- Script ini akan menghapus data dari tabel-tabel transaksional
-- TANPA menghapus tabel-tabel krusial berikut:
--   - users
--   - settings
--   - roles
--   - odp_points
--   - internet_packages
--   - expenses
-- ===========================================
-- PERINGATAN: Jalankan script ini dengan hati-hati!
-- Data yang dihapus TIDAK BISA dikembalikan.
-- ===========================================

USE isp_billing;

SET FOREIGN_KEY_CHECKS = 0;

-- ===========================================
-- 1. TRUNCATE TABEL TRANSAKSIONAL
-- ===========================================

-- Hapus data payments (harus duluan karena FK ke invoices)
TRUNCATE TABLE payments;

-- Hapus data invoices (harus sebelum customers karena FK)
TRUNCATE TABLE invoices;

-- Hapus data customers
TRUNCATE TABLE customers;

-- Hapus data login_attempts
TRUNCATE TABLE login_attempts;

-- ===========================================
-- 2. RESET AUTO INCREMENT (Opsional)
-- ===========================================

ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE invoices AUTO_INCREMENT = 1;
ALTER TABLE customers AUTO_INCREMENT = 1;
ALTER TABLE login_attempts AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================
-- RANGKUMAN
-- ===========================================
-- Tabel yang DI-RESET (data dihapus):
--   ✓ customers
--   ✓ invoices
--   ✓ payments
--   ✓ login_attempts
--
-- Tabel yang TIDAK disentuh (data dipertahankan):
--   ✓ users
--   ✓ settings
--   ✓ roles
--   ✓ odp_points
--   ✓ internet_packages
--   ✓ expenses
-- ===========================================

SELECT 'Database reset completed!' AS Status;
SELECT 
    (SELECT COUNT(*) FROM customers) AS customers_count,
    (SELECT COUNT(*) FROM invoices) AS invoices_count,
    (SELECT COUNT(*) FROM payments) AS payments_count,
    (SELECT COUNT(*) FROM users) AS users_count,
    (SELECT COUNT(*) FROM settings) AS settings_count;
