<?php
require_once __DIR__ . '/../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/Supplier.php';
require_once BASEPATH . '/app/Models/Product.php';
require_once BASEPATH . '/app/Helpers/csrf.php';
$sale = new Sale();
$sale->user_id = $_SESSION['adminId'];
$r = $sale->userGet();
$db = new Database();
$pdo = $db->getConnect();
$random_id = date('Ymd') . rand(10000, 99999);
$activePage = 'transactions';
$pageTitle = 'New Transaction - Inventory Management System';
$csrfMeta = true;
require_once BASEPATH . '/views/layouts/header.php';
require_once BASEPATH . '/views/layouts/nav.php';
?>

<div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
 <div class="container-fluid">
  <div class="card">
   <div class="card-header">
    <h1 class="text text-center text-primary" style="font-family:'Playfair Display',serif;">ORDER TRANSACTION</h1>

   </div>
   <div class="card-body">
    <form id="input_form">
     <input type="hidden" value="<?= htmlspecialchars($random_id) ?>" id="codess" name="tCode">
     <div class="d-flex justify-content-first mb-3 flex-column" style="gap:10px;">
      <label class="control-label">SUPPLIER:</label>
      <select name="cus_id" class="form-control text-center" style="width:200px;" id="cus_id">
       <option value="0">-- Choose here --</option>

       <?php
       $stmt = $pdo->query("SELECT * FROM supplier");
       while ($row = $stmt->fetch()):
       ?>

       <option value="<?= (int)$row['cus_id'] ?>"><?= htmlspecialchars($row['cus_fname']) ?></option>

       <?php endwhile; ?>

      </select>

      <?php foreach ($r as $val): extract($val); ?>
      <input type="hidden" value="<?= (int)$user_id ?>" id="user_id" name="user_id">
      <input type="hidden" id="userType" value="<?= htmlspecialchars($userType) ?>">
      Encoder: <input class="form-control text-center" value="<?= htmlspecialchars($username) ?>" style="width:200px;" readonly>

      <?php break; endforeach; ?>

     </div>
     <table class="table table-bordered mb-5" style="width:100%">
      <thead>
       <tr><th>Product Name</th><th>Price</th><th>Quantity</th><th>Sub_total</th><th></th></tr>

      </thead>
      <tbody>
       <tr>
        <td>
         <select id="prod_id" class="form-control text-center" style="width:200px;" name="prod_id">
          <option value="0">-- Choose here --</option>

          <?php
          $stmt2 = $pdo->query("SELECT * FROM product WHERE prod_quan IS NULL AND prod_price IS NULL");
          while ($row = $stmt2->fetch()):
          ?>

          <option value="<?= (int)$row['prod_id'] ?>"><?= htmlspecialchars($row['prod_name']) ?></option>

          <?php endwhile; ?>

         </select>
        </td>
        <td><input type="text" class="form-control" id="price"></td>
        <td><input type="text" class="form-control" id="onhand" name="onhand" onkeyup="mykey(this.value)"></td>
        <td><input type="text" class="form-control" id="total_price" name="total_price"></td>
        <td>
         <button type="button" class="btn btn-success" id="submit"><i class="fa-solid fa-plus"></i></button>
        </td>
       </tr>

      </tbody>

     </table>

    </form>
    <hr>
    <div class="container-fluid">
     <table id="example" class="table-strip" style="width:100%">
      <thead><tr><th>Product Name</th><th>Total Quantity</th><th>Total Price</th><th></th></tr></thead>
      <tbody id="apnd"></tbody>

     </table>
     <div class="mt-5 d-flex justify-content-end" style="width:100%;">
      <table class="table table-bordered" style="width:300px;">
       <tbody id="calcu"></tbody>

      </table>

     </div>
     <form id="goToTransRe" action="<?= BASE_URL ?>/views/transactions/receipt.php">
      <input type="hidden" value="<?= htmlspecialchars($random_id) ?>" name="code">
      <button type="submit" style="display:none;" class="btn btn-success" onclick="savePurchase()" id="savePur">Save Purchase</button>

     </form>

    </div>

   </div>

  </div>

 </div>

</div>
<script>
var csrfToken = $('meta[name="csrf-token"]').attr('content');
var BASE_URL = '<?= BASE_URL ?>';
$(document).ready(function() {
 $('#example').DataTable({ select: true, responsive: true });
 window.onbeforeunload = function() { return "Are you sure you want to leave?"; };
});
let prodId;
function getData() {
 var code = $('#codess').val();
 $.ajax({
  type: 'POST', url: BASE_URL + '/app/Controllers/TransactionController.php',
  data: { id: code, action: 'getData' },
  success: function(response) {
   setTimeout(function() { $('#apnd').html(response); }, 1);
   $('#savePur').show();
   getTOTAL(code);
  }
 });
}
function getTOTAL(code) {
 $.ajax({
  type: 'POST', url: BASE_URL + '/app/Controllers/TransactionController.php',
  data: { code: code, action: 'getTotal' },
  success: function(response) { setTimeout(function() { $('#calcu').html(response); }, 1); }
 });
}
function dataUpdatequan() {
 var b = $('#onhand').val(), d = $('#price').val(), id = $('#prod_id').val();
 prodId = id;
 $.ajax({
  type: 'post', url: BASE_URL + '/app/Controllers/TransactionController.php',
  data: { quan: b, price: d, id: id, action: 'upQuan', csrf_token: csrfToken }
 });
}
function dataUpdatequan1() {
 $.ajax({
  type: 'post', url: BASE_URL + '/app/Controllers/TransactionController.php',
  data: { id: prodId, action: 'updateToZero', csrf_token: csrfToken }
 });
}
$('#submit').on('click', function() {
 var a = $('#userType').val(), b = $('#total_price').val(), c = $('#onhand').val();
 if (a === 'Employee') { alert('You are not allowed to purchase orders. Contact the admin or staff.'); return; }
 if (!b || !c) { alert('Please input all fields'); return; }
 $.ajax({
  type: 'post', url: BASE_URL + '/app/Controllers/TransactionController.php',
  data: Object.assign($('#input_form').serializeArray().reduce(function(o,i){o[i.name]=i.value;return o;},{}), {action:'insertTransaction', csrf_token:csrfToken}),
  success: function(response) {
   getData(); dataUpdatequan();
   $('#prod_id').val('0'); $('#price').val(''); $('#total_price').val(''); $('#onhand').val('');
   var res = JSON.parse(response);
   if (res.error) alert(res.error);
  }
 });
});
function mykey(value) {
 var p = parseInt($('#price').val()), q = parseInt(value);
 if (!isNaN(p) && !isNaN(q)) $('#total_price').val(p * q);
}
function mykey2(value) {
 var price = parseFloat($('#priceTotal').val().replace('\u20b1','')), pay = parseFloat(value);
 if (!isNaN(price) && !isNaN(pay)) $('#change').val(pay - price);
}
function savePurchase() {
 $.ajax({
  url: BASE_URL + '/app/Controllers/TransactionController.php',
  type: 'post',
  data: { code: $('#codess').val(), pay: $('#payment').val(), alltotal: $('#priceTotal').val().replace('\u20b1',''), allquan: $('#allquan').val(), cus: $('#cus_id').val(), action: 'saveTransac', csrf_token: csrfToken },
  success: function() { setTimeout(function() { alert('Transaction saved successfully!'); }, 1); }
 });
}
function DeletMe(id) {
 dataUpdatequan1();
 $.ajax({
  type: 'POST', url: BASE_URL + '/app/Controllers/TransactionController.php',
  data: { id: id, action: 'deleteTransaction', csrf_token: csrfToken },
  success: function() { setTimeout(function() { getData(); }, 1); }
 });
}

</script>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
