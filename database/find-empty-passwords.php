<?php
// Lists accounts whose password was wiped by the old "edit user" bug (saved as a hash of an empty string).
// Usage: php database/find-empty-passwords.php
// An Admin should then set a new password for each listed account from the Accounts page.
if (PHP_SAPI !== 'cli') {
 exit('Run this from the command line.');
}
require_once __DIR__ . '/../app/Database/Database.php';

$rows = (new Database())->getConnect()->query("SELECT user_id, username, userType, user_pass FROM user_account")->fetchAll();
$found = 0;
foreach ($rows as $row) {
 if (password_verify('', $row['user_pass'])) {
  echo "#{$row['user_id']}  {$row['username']}  ({$row['userType']})\n";
  $found++;
 }
}
echo $found ? "{$found} account(s) need a new password.\n" : "No accounts with an empty password.\n";
