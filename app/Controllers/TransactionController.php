<?php
require_once __DIR__ . '/../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) {
 http_response_code(403);
 exit('Unauthorized.');
}
require_once BASEPATH . '/app/Helpers/csrf.php';
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/Product.php';
require_once BASEPATH . '/app/Models/Supplier.php';
require_once BASEPATH . '/app/Models/User.php';
require_once BASEPATH . '/app/Models/TransactionModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 http_response_code(405);
 exit();
}

$action  = $_POST['action'] ?? '';
$sale    = new Sale();
$product = new Product();
$supplier = new Supplier();
$user    = new User();
$trans   = new TransactionModel();

// ── Read-only AJAX actions (no CSRF needed) ──────────────────────────────────

if ($action === 'getData') {
 $sale->trans_code = $_POST['id'] ?? '';
 foreach ($sale->showTrans() as $val) {
  extract($val);
  echo '<tr>';
  echo '<td>' . htmlspecialchars($prod_name, ENT_QUOTES, 'UTF-8') . '</td>';
  echo '<td>' . htmlspecialchars($sub_quantity, ENT_QUOTES, 'UTF-8') . '</td>';
  echo '<td>&#8369;' . htmlspecialchars($sub_price, ENT_QUOTES, 'UTF-8') . '</td>';
  echo '<td>
   <div class="btn-group">
    <button type="button" class="btn btn-danger" onclick="DeletMe(' . (int)$trans_id . ')">
     <i class="fa-sharp fa-solid fa-xmark"></i>
    </button>

   </div>
  </td>';
  echo '</tr>';
 }
 exit;
}

if ($action === 'getTotal') {
 $sale->trans_code = $_POST['code'] ?? '';
 foreach ($sale->totalPrice() as $val) {
  extract($val);
  echo '<tr><td>
   Total Quantity: <input type="number" class="form-control text-end" id="allquan" disabled value="' . (int)$total_quan . '">
   Payment: <input type="text" class="form-control text-end" onkeyup="mykey2(this.value)" id="payment" placeholder="payment">
   Total Price: <input type="text" class="form-control text-end" id="priceTotal" value="&#8369;' . htmlspecialchars($total_price, ENT_QUOTES, 'UTF-8') . '" disabled>
   Change: <input type="text" class="form-control text-end" id="change" disabled>
  </td></tr>';
 }
 exit;
}

if ($action === 'getId') {
 $product->id = (int)($_POST['ids'] ?? 0);
 foreach ($product->getSingle() as $res) {
  extract($res);
  echo '
  <form action="' . BASE_URL . '/app/Controllers/ProductController.php" method="post">
   <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">
   <input type="hidden" name="_action" value="update">
   <div class="modal-body">
    <input type="hidden" value="' . (int)$prod_id . '" name="id">
    <div class="d-flex justify-content-center">
     <img src="' . BASE_URL . '/' . htmlspecialchars($prod_image, ENT_QUOTES, 'UTF-8') . '" alt="" style="width:150px;height:150px;border-radius:10%;">

    </div>
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($prod_name, ENT_QUOTES, 'UTF-8') . '" name="prod">
     <label>Product Name:</label>

    </div>
    <div class="form-floating mb-3">
     <input type="number" class="form-control" value="' . htmlspecialchars($prod_price, ENT_QUOTES, 'UTF-8') . '" name="price">
     <label>Price:</label>

    </div>
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($prod_quan, ENT_QUOTES, 'UTF-8') . '" name="quan">
     <label>Quantity:</label>

    </div>
    <div class="form-floating">
     <input class="form-control" name="desc" value="' . htmlspecialchars($prod_desc, ENT_QUOTES, 'UTF-8') . '">
     <label>Description</label>

    </div>

   </div>
   <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary" name="save">Save</button>

   </div>
  </form>';
  break;
 }
 exit;
}

if ($action === 'viewCust') {
 $supplier->id = (int)($_POST['ids'] ?? 0);
 foreach ($supplier->getSingle() as $result) {
  extract($result);
  echo '
  <form action="' . BASE_URL . '/app/Controllers/SupplierController.php" method="POST">
   <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">
   <input type="hidden" name="_action" value="update">
   <div class="modal-body">
    <input type="hidden" value="' . (int)$cus_id . '" name="id">
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($cus_fname, ENT_QUOTES, 'UTF-8') . '" name="cname">
     <label>Supplier Name:</label>

    </div>
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($cus_lname, ENT_QUOTES, 'UTF-8') . '" name="lname">
     <label>Address:</label>

    </div>
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($cus_phone, ENT_QUOTES, 'UTF-8') . '" name="phone">
     <label>Phone number:</label>

    </div>

   </div>
   <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary" name="save">Save Changes</button>

   </div>
  </form>';
  break;
 }
 exit;
}

if ($action === 'viewUser') {
 $user->id   = (int)($_POST['ids'] ?? 0);
 $currentUser = (int)($_POST['currentUser'] ?? 0);
 foreach ($user->getSingleUser() as $result) {
  extract($result);
  $form  = '
  <form action="' . BASE_URL . '/app/Controllers/UserController.php" method="POST">
   <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">
   <input type="hidden" name="_action" value="update">
   <div class="modal-body">
    <input type="hidden" name="id" value="' . (int)$user_id . '">
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '" name="user">
     <label>Account Name:</label>

    </div>
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($user_fname, ENT_QUOTES, 'UTF-8') . '" name="name">
     <label>First Name:</label>

    </div>
    <div class="form-floating mb-3">
     <input type="text" class="form-control" value="' . htmlspecialchars($user_lname, ENT_QUOTES, 'UTF-8') . '" name="lname">
     <label>Last Name:</label>
    </div>';
  if ($currentUser === (int)$user_id) {
   $form .= '
    <div class="form-floating mb-3">
     <input type="password" class="form-control" value="" name="pass" placeholder="Enter new password">
     <label>New Password:</label>
    </div>';
  }
  $form .= '

   </div>
   <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary" name="save">Save</button>

   </div>
  </form>';
  echo $form;
  break;
 }
 exit;
}

// ── State-changing actions (CSRF required) ────────────────────────────────────

csrf_validate();

if ($action === 'insertTransaction') {
 $error    = [];
 $response = ['error' => null];
 $sale->prod_id    = $_POST['prod_id'] ?? '';
 $sale->trans_code = $_POST['tCode'] ?? '';
 if ($sale->searchDuplicate() > 0) {
  $error[] = 'This product is already added!';
 } else {
  $sale->cus_id     = $_POST['cus_id'] ?? '';
  $sale->sub_price  = $_POST['total_price'] ?? 0;
  $sale->total_quan = $_POST['onhand'] ?? 0;
  $sale->user_id    = $_POST['user_id'] ?? '';
  $sale->insertData();
 }
 foreach ($error as $e) {
  $response = ['error' => $e];
 }
 header('Content-Type: application/json');
 echo json_encode($response);
 exit;
}

if ($action === 'saveTransac') {
 $trans->code       = $_POST['code'] ?? '';
 $trans->trans_pay  = $_POST['pay'] ?? 0;
 $trans->trans_total = $_POST['alltotal'] ?? 0;
 $trans->trans_quan = $_POST['allquan'] ?? 0;
 $trans->cus_id     = $_POST['cus'] ?? '';
 $trans->insertTransact();
 exit;
}

if ($action === 'upQuan') {
 $sale->total_quan = $_POST['quan'] ?? 0;
 $sale->sub_price  = $_POST['price'] ?? 0;
 $sale->prod_id    = $_POST['id'] ?? 0;
 $sale->updateProduct();
 exit;
}

if ($action === 'updateToZero') {
 $sale->prod_id = $_POST['id'] ?? 0;
 $sale->updateToZero();
 exit;
}

if ($action === 'deleteTransaction') {
 $sale->id = (int)($_POST['id'] ?? 0);
 $sale->deleteData();
 exit;
}

http_response_code(400);
exit('Unknown action.');
