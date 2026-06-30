<?php
require_once __DIR__ . '/../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) {
 header('Location: ' . BASE_URL . '/login.php');
 exit;
}
require_once BASEPATH . '/app/Helpers/csrf.php';
require_once BASEPATH . '/app/Models/Product.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 header('Location: ' . BASE_URL . '/views/products.php');
 exit;
}

csrf_validate();
$_act    = $_POST['_action'] ?? '';
$product = new Product();

if ($_act === 'insert' && isset($_POST['save'])) {
 $product->prod     = $_POST['prod'] ?? '';
 $product->price    = $_POST['price'] ?? '';
 $product->quantity = $_POST['quan'] ?? '';
 $product->desc     = $_POST['desc'] ?? '';

 $uploadDir  = BASEPATH . '/public/assets/uploads/';
 $fullpath   = '';

 $image = $_FILES['image'] ?? null;
 if ($image && $image['tmp_name'] && $image['error'] === UPLOAD_ERR_OK) {
  $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
  $allowed_ext  = ['jpg','jpeg','png','gif','webp'];
  $finfo        = new finfo(FILEINFO_MIME_TYPE);
  $mime         = $finfo->file($image['tmp_name']);
  $ext          = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
  if (!in_array($mime, $allowed_mime, true) || !in_array($ext, $allowed_ext, true)) {
   $_SESSION['Error'] = 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.';
   header('Location: ' . BASE_URL . '/views/products.php');
   exit;
  }
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
  $folder = bin2hex(random_bytes(4)) . '/';
  mkdir($uploadDir . $folder, 0755, true);
  $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
  $fullpath = 'public/assets/uploads/' . $folder . $safeName;
  move_uploaded_file($image['tmp_name'], BASEPATH . '/' . $fullpath);
 }
 $product->image = $fullpath;

 if ($product->insertItem()) {
  $_SESSION['Saves'] = 'Product added successfully!';
 }
 header('Location: ' . BASE_URL . '/views/products.php');
 exit;
}

if ($_act === 'update' && isset($_POST['save'])) {
 $product->id       = (int)($_POST['id'] ?? 0);
 $product->prod     = $_POST['prod'] ?? '';
 $product->price    = $_POST['price'] ?? '';
 $product->quantity = $_POST['quan'] ?? '';
 $product->desc     = $_POST['desc'] ?? '';

 if ($product->update()) {
  $_SESSION['Saves'] = 'Product updated successfully!';
 }
 header('Location: ' . BASE_URL . '/views/products.php');
 exit;
}

header('Location: ' . BASE_URL . '/views/products.php');
