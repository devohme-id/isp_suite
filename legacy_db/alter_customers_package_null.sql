-- ===========================================
-- ALTER TABLE: Allow null package_id
-- ===========================================
-- Mengizinkan field package_id bernilai NULL
-- untuk customer yang di-import dari MikroTik
-- tanpa paket internet yang ditentukan
-- ===========================================

-- Step 1: Drop existing foreign key constraint
ALTER TABLE customers DROP FOREIGN KEY customers_ibfk_1;

-- Step 2: Modify column to allow NULL
ALTER TABLE customers MODIFY COLUMN package_id int(11) DEFAULT NULL;

-- Step 3: Re-add foreign key with ON DELETE SET NULL
ALTER TABLE customers 
ADD CONSTRAINT customers_ibfk_1 
FOREIGN KEY (package_id) REFERENCES internet_packages(id) 
ON DELETE SET NULL;

-- Verify
SELECT 'package_id now allows NULL' AS Status;
