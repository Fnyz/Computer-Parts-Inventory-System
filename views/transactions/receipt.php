<?php
require_once __DIR__ . '/../../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Models/TransactionModel.php';
$trans = new TransactionModel();
$trans->code = $_GET['code'] ?? '';
$result = $trans->showTransaction();
$amount = $trans->getCalculation();
$pageTitle = 'Purchase Receipt - Inventory Management System';
require_once BASEPATH . '/views/layouts/header.php';
?>

 <div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
  <div class="card shadow mb-4">
   <div class="card-body">

    <?php foreach ($result as $val): extract($val); ?>
    <div class="form-group row text-left mb-0">
     <div class="col-sm-9">
      <h1 class="text text-start text-secondary" style="font-family:'Playfair Display',serif;">Transaction</h1>

     </div>
     <div class="col-sm-3 py-1"><h6>Date: <?= htmlspecialchars($created_date) ?></h6></div>

    </div>
    <hr>
    <div class="form-group row text-left mb-0 py-2">
     <div class="col-sm-4 py-1">
      <h6 class="font-weight-bold">Customer: <?= htmlspecialchars($cus_fname) ?></h6>
      <h6>Phone: <?= htmlspecialchars($cus_phone) ?></h6>

     </div>
     <div class="col-sm-4 py-1"></div>
     <div class="col-sm-4 py-1">
      <h6>Transaction # <?= htmlspecialchars($trans_code) ?></h6>
      <h6 class="font-weight-bold">Encoder: <?= htmlspecialchars($username ?? '') ?></h6>

     </div>

    </div>

    <?php break; endforeach; ?>
    <table class="table table-bordered" width="100%" cellspacing="0">
     <thead><tr><th>Products</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
     <tbody>

      <?php foreach ($result as $val): extract($val); ?>
      <tr>
       <td><?= htmlspecialchars($prod_name) ?></td>
       <td><?= htmlspecialchars($sub_quantity) ?></td>
       <td><?= htmlspecialchars($prod_price) ?></td>
       <td><?= htmlspecialchars($sub_price) ?></td>
      </tr>

      <?php endforeach; ?>

     </tbody>

    </table>

    <?php foreach ($amount as $val): extract($val); ?>
    <div class="form-group row text-left mb-0 py-2">
     <div class="col-sm-5"></div>
     <div class="col-sm-6 py-1">
      <h4>Cash Amount: <b class="text text-danger"><?= htmlspecialchars($transac_payment) ?></b></h4>
      <table width="100%">
       <tr><td class="font-weight-bold">Total:</td><td class="text-right text-primary font-weight-bold">&#8369; <?= htmlspecialchars($transac_total) ?></td></tr>
       <tr><td class="font-weight-bold">Change:</td><td class="text-right text-primary font-weight-bold">&#8369; <?= htmlspecialchars($total_change) ?></td></tr>

      </table>

     </div>

    </div>

    <?php endforeach; ?>

   </div>

  </div>

 <a href="<?= BASE_URL ?>/views/transactions/create.php" class="btn btn-danger">Purchase Again</a>

</div>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
