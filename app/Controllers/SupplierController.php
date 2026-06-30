<?php
require_once __DIR__ . '/../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) {
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}
require_once BASEPATH . '/app/Helpers/csrf.php';
require_once BASEPATH . '/app/Models/Supplier.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 header('Location: ' . BASE_URL . '/views/suppliers.php');
 exit;
}

csrf_validate();
$_act     = $_POST['_action'] ?? '';
$supplier = new Supplier();

if ($_act === 'insert' && isset($_POST['save'])) {
 $supplier->fname = $_POST['cname'] ?? '';
 $supplier->lname = $_POST['lname'] ?? '';
 $supplier->phone = $_POST['phone'] ?? '';

 if ($supplier->insertItem()) {
  $_SESSION['Saves'] = 'Supplier added successfully!';
 }
 header('Location: ' . BASE_URL . '/views/suppliers.php');
 exit;
}

if ($_act === 'update' && isset($_POST['save'])) {
 $supplier->id    = (int)($_POST['id'] ?? 0);
 $supplier->fname = $_POST['cname'] ?? '';
 $supplier->lname = $_POST['lname'] ?? '';
 $supplier->phone = $_POST['phone'] ?? '';

 if ($supplier->update()) {
  $_SESSION['Saves'] = 'Supplier updated successfully!';
 }
 header('Location: ' . BASE_URL . '/views/suppliers.php');
 exit;
}

header('Location: ' . BASE_URL . '/views/suppliers.php');
