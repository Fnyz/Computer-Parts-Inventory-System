<?php
require_once __DIR__ . '/../../config.php';
session_start();
require_once BASEPATH . '/app/Helpers/csrf.php';
require_once BASEPATH . '/app/Helpers/auth.php';
require_once BASEPATH . '/app/Models/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}

csrf_validate();
$_act = $_POST['_action'] ?? '';
$user = new User();

if ($_act === 'insert' && isset($_POST['save'])) {
 // The very first account is created from the login page and is always an Admin.
 // After that, only a signed-in Admin can create accounts.
 $firstRun = $user->countUsers() === 0;
 if (!$firstRun && !is_admin()) {
  http_response_code(403);
  exit('Only an Admin can create accounts.');
 }

 $user->fname    = trim($_POST['first'] ?? '');
 $user->lname    = trim($_POST['last']  ?? '');
 $user->phone    = trim($_POST['phone'] ?? '');
 $user->username = trim($_POST['user']  ?? '');
 $user->pass     = $_POST['pass'] ?? '';
 $user->userType = $firstRun ? 'Admin' : ($_POST['userType'] ?? '');

 if ($user->username === '') {
  $_SESSION['Error'] = 'Enter a username.';
 } elseif (strlen($user->pass) < 8) {
  $_SESSION['Error'] = 'Use a password with at least 8 characters.';
 } elseif (!in_array($user->userType, USER_ROLES, true)) {
  $_SESSION['Error'] = 'Choose an account type: Admin, Staff or Employee.';
 } elseif ($user->usernameExists($user->username)) {
  $_SESSION['Error'] = 'That username is already taken.';
 } elseif ($user->insertUser()) {
  $_SESSION['Saves'] = 'Account created successfully!';
 }
 header('Location: ' . BASE_URL . ($firstRun ? '/login.php' : '/views/accounts.php'));
 exit;
}

// Update requires auth
$me = current_user();
if ($me === null) {
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}

if ($_act === 'update' && isset($_POST['save'])) {
 $user->id = (int)($_POST['id'] ?? 0);
 // Users may edit their own account; only an Admin may edit someone else's
 if ($user->id !== (int)$me['user_id'] && !is_admin()) {
  http_response_code(403);
  exit('You can only edit your own account.');
 }

 $user->fname    = trim($_POST['name']  ?? '');
 $user->lname    = trim($_POST['lname'] ?? '');
 $user->username = trim($_POST['user']  ?? '');
 $user->pass     = $_POST['pass'] ?? '';

 if ($user->username === '') {
  $_SESSION['Error'] = 'Enter a username.';
 } elseif ($user->pass !== '' && strlen($user->pass) < 8) {
  $_SESSION['Error'] = 'Use a password with at least 8 characters, or leave it blank to keep the current one.';
 } elseif ($user->usernameExists($user->username, $user->id)) {
  $_SESSION['Error'] = 'That username is already taken.';
 } elseif ($user->updateUser()) {
  $_SESSION['Saves'] = 'Account updated successfully!';
 }
 header('Location: ' . BASE_URL . (is_admin() ? '/views/accounts.php' : '/views/dashboard.php'));
 exit;
}

header('Location: ' . BASE_URL . (is_admin() ? '/views/accounts.php' : '/views/dashboard.php'));
