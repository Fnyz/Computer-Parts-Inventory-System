<?php
require_once __DIR__ . '/../../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/TransactionModel.php';
$sale = new Sale();
$sale->user_id = $_SESSION['adminId'];
$r = $sale->userGet();
$trans = new TransactionModel();
$res = $trans->showAll();
$activePage = 'history';
$pageTitle = 'Transaction History - Inventory Management System';
require_once BASEPATH . '/views/layouts/header.php';
require_once BASEPATH . '/views/layouts/nav.php';
?>

<div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
 <h1 class="text text-center text-primary" style="font-family:'Playfair Display',serif;">TRANSACTIONS HISTORY</h1>
 <table id="example" class="table-strip" style="width:100%">
  <thead>
   <tr><th>Transac #</th><th>Supplier</th><th>No. of items</th><th>Action</th></tr>

  </thead>
  <tbody>

   <?php foreach ($res as $val): extract($val); ?>
   <tr>
    <td><?= htmlspecialchars($transac_code) ?></td>
    <td><?= htmlspecialchars($cus_fname) ?></td>
    <td><?= htmlspecialchars($total_quan) ?></td>
    <td>
     <a href="<?= BASE_URL ?>/views/transactions/view.php?code=<?= urlencode($transac_code) ?>"
        class="btn btn-danger"><i class="fa-solid fa-list"></i></a>
    </td>
   </tr>

   <?php endforeach; ?>

  </tbody>

 </table>

</div>
<script>
$(document).ready(function() { $('#example').DataTable({ select: true, responsive: true }); });

</script>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
