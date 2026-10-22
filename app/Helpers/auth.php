<?php
require_once BASEPATH . '/app/Models/User.php';

const USER_ROLES = ['Admin', 'Staff', 'Employee'];

// The signed-in user's row, or null when nobody is signed in
function current_user(): ?array {
 static $user = false;
 if ($user === false) {
  $user = null;
  if (isset($_SESSION['adminId'])) {
   $model = new User();
   $model->id = (int)$_SESSION['adminId'];
   $user = $model->getSingleUser()[0] ?? null;
  }
 }
 return $user;
}

function is_admin(): bool {
 $user = current_user();
 return $user !== null && $user['userType'] === 'Admin';
}
