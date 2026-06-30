<?php
require_once __DIR__ . '/../../config.php';
session_start();
require_once BASEPATH . '/app/Helpers/csrf.php';
require_once BASEPATH . '/app/Models/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}

csrf_validate();
$_act = $_POST['_action'] ?? '';
$user = new User();

// Insert is open (registration from login page — no auth guard)
if ($_act === 'insert' && isset($_POST['save'])) {
 $user->fname    = $_POST['first'] ?? '';
 $user->lname    = $_POST['last']  ?? '';
 $user->phone    = $_POST['phone'] ?? '';
 $user->username = $_POST['user']  ?? '';
 $user->pass     = $_POST['pass']  ?? '';
 $user->userType = $_POST['userType'] ?? '';

 if ($user->insertUser()) {
  $_SESSION['Saves'] = 'Account created successfully!';
 }
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}

// Update requires auth
if (!isset($_SESSION['adminId'])) {
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}

if ($_act === 'update' && isset($_POST['save'])) {
 $user->id       = (int)($_POST['id']   ?? 0);
 $user->fname    = $_POST['name']  ?? '';
 $user->lname    = $_POST['lname'] ?? '';
 $user->username = $_POST['user']  ?? '';
 $user->pass     = $_POST['pass']  ?? '';

 if ($user->updateUser()) {
  $_SESSION['Saves'] = 'Account updated successfully!';
 }
 header('Location: ' . BASE_URL . '/views/accounts.php');
 exit;
}

header('Location: ' . BASE_URL . '/views/accounts.php');
