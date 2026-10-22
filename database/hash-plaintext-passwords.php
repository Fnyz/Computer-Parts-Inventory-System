<?php
// Hashes any password still stored as plain text (from the original version of the app).
// Each account keeps the same password; only the way it is stored changes.
// Usage: php database/hash-plaintext-passwords.php
if (PHP_SAPI !== 'cli') {
 exit('Run this from the command line.');
}
require_once __DIR__ . '/../app/Database/Database.php';

$pdo = (new Database())->getConnect();
$rows = $pdo->query("SELECT user_id, username, user_pass FROM user_account")->fetchAll();
$update = $pdo->prepare("UPDATE user_account SET user_pass = :hash WHERE user_id = :id");
$changed = 0;
foreach ($rows as $row) {
 // password_get_info() reports algo null for anything that is not a PHP password hash
 if (password_get_info($row['user_pass'])['algo'] === null) {
  $update->execute([':hash' => password_hash($row['user_pass'], PASSWORD_DEFAULT), ':id' => $row['user_id']]);
  echo "Hashed the password for #{$row['user_id']} {$row['username']}\n";
  $changed++;
 }
}
echo $changed ? "{$changed} password(s) hashed.\n" : "All passwords were already hashed.\n";
