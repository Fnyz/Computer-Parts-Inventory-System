-- Upgrades a database created by the ORIGINAL version of this app (before the 2026-06 refactor)
-- to the structure the current code expects. Back up the database first.
-- Usage: mysql -u root -p <your_database> < database/upgrade-legacy.sql
-- Then:  php database/hash-plaintext-passwords.php

-- The refactor renamed "customer" to "supplier"; the columns are the same
RENAME TABLE customer TO supplier;

-- Phone numbers are text: an INT column drops the leading 0 (0917… becomes 917…)
ALTER TABLE supplier MODIFY cus_phone VARCHAR(30) NOT NULL DEFAULT '';

-- Roles did not exist yet. The oldest account becomes the Admin; everyone else starts as Employee.
-- An Admin can change these later from the Accounts page.
ALTER TABLE user_account ADD COLUMN userType VARCHAR(20) NOT NULL DEFAULT 'Employee' AFTER user_pass;
UPDATE user_account SET userType = 'Admin' ORDER BY user_id LIMIT 1;

-- Room for password_hash() output
ALTER TABLE user_account MODIFY user_pass VARCHAR(255) NOT NULL;

-- Product images were added in the refactor
ALTER TABLE product ADD COLUMN prod_image VARCHAR(255) NULL AFTER prod_name;
