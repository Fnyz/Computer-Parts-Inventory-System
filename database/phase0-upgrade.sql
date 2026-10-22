-- Run once on an EXISTING database after pulling the Phase 0 hotfixes.
-- Usage: mysql -u root -p inventorymanagementsystem < database/phase0-upgrade.sql

-- The old sign-up form saved both "Employee" and "Staff" as the typo "Emoloyee".
-- They can't be told apart any more, so they become Employee. An Admin can change this later.
UPDATE user_account SET userType = 'Employee' WHERE userType = 'Emoloyee';

-- Room for password_hash() output, in case the column was created shorter
ALTER TABLE user_account MODIFY user_pass VARCHAR(255) NOT NULL;

-- Accounts whose password was wiped by the old edit bug can no longer sign in, because empty
-- passwords are now rejected. Find them with:  php database/find-empty-passwords.php
-- then an Admin sets a new password for each one from the Accounts page.
