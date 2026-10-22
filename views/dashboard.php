<?php
require_once __DIR__ . '/../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/TransactionModel.php';
$sale = new Sale();
$sale->user_id = $_SESSION['adminId'];
$r = $sale->userGet();
$trans = new TransactionModel();
$totalProducts = $trans->getAllProduct();
$totalTransacs = $trans->getAllTotalTransactions();
$activePage = 'dashboard';
$pageTitle = 'Dashboard - Inventory Management System';
require_once BASEPATH . '/views/layouts/header.php';
require_once BASEPATH . '/views/layouts/nav.php';
?>

<div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
 <h1 class="text text-center mb-3 text-primary" style="font-family:'Playfair Display',serif;">DASHBOARD</h1>
 <div class="col-md-3 gap-2 d-flex">
  <div class="col-md-12 mb-3">
   <div class="card border-left-success shadow h-100 py-2">
    <div class="card-body">
     <div class="row no-gutters align-items-center">
      <div class="col mr-0">
       <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Products</div>
       <div class="h6 mb-0 text-gray-800">* <?= (int)$totalProducts['total_count'] ?></div>

      </div>
      <div class="col-auto"><i class="fa-solid fa-box fs-2"></i></div>

     </div>

    </div>

   </div>

  </div>
  <div class="col-md-12 mb-3">
   <div class="card border-left-success shadow h-100 py-2">
    <div class="card-body">
     <div class="row no-gutters align-items-center">
      <div class="col mr-0">
       <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Transactions</div>
       <div class="h6 mb-0 text-gray-800">* <?= (int)$totalTransacs['total_count'] ?></div>

      </div>
      <div class="col-auto"><i class="fa-solid fa-receipt fs-2"></i></div>

     </div>

    </div>

   </div>

  </div>

 </div>

</div>
<script>
$(document).ready(function() { $('#example').DataTable({ select: true, responsive: true }); });

</script>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
