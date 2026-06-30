<?php
require_once __DIR__ . '/../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/Supplier.php';
require_once BASEPATH . '/app/Helpers/csrf.php';
$sale = new Sale();
$sale->user_id = $_SESSION['adminId'];
$r = $sale->userGet();
$supplier = new Supplier();
$res = $supplier->showAll();
$activePage = 'suppliers';
$pageTitle = 'Suppliers - Inventory Management System';
require_once BASEPATH . '/views/layouts/header.php';
require_once BASEPATH . '/views/layouts/nav.php';
?>

<div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
 <h1 class="text text-center text-primary" style="font-family:'Playfair Display',serif;">SUPPLIER</h1>
 <div class="mb-3">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
   <i class="fa-solid fa-plus"></i>
  </button>
  <label class="text text-primary fw-bold">ADD SUPPLIER</label>

 </div>
 <!-- Add Supplier Modal -->
 <div class="modal fade" id="addSupplierModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
   <div class="modal-header bg-danger">
    <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;">Add Supplier</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

   </div>
   <form action="<?= BASE_URL ?>/app/Controllers/SupplierController.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_action" value="insert">
    <div class="modal-body">
     <div class="form-floating mb-3">
      <input type="text" class="form-control" name="cname" required>
      <label>Supplier Name:</label>

     </div>
     <div class="form-floating mb-3">
      <input type="text" class="form-control" name="lname">
      <label>Address:</label>

     </div>
     <div class="form-floating mb-3">
      <input type="number" class="form-control" name="phone">
      <label>Phone number:</label>

     </div>

    </div>
    <div class="modal-footer">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
     <button type="submit" class="btn btn-primary" name="save">Save</button>

    </div>

   </form>
  </div></div>

 </div>
 <!-- Edit Supplier Modal -->
 <div class="modal fade" id="editSupplierModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
   <div class="modal-header bg-success">
    <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;">Edit Supplier</h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

   </div>
   <div id="supplierFormHolder"></div>
  </div></div>

 </div>
 <table id="example" class="table-strip" style="width:100%">
  <thead>
   <tr><th>Supplier Name</th><th>Address</th><th>Phone number</th><th>Date added</th><th>Action</th></tr>

  </thead>
  <tbody>

   <?php foreach ($res as $val): extract($val); ?>
   <tr>
    <td><?= htmlspecialchars($cus_fname) ?></td>
    <td><?= htmlspecialchars($cus_lname) ?></td>
    <td><?= htmlspecialchars($cus_phone) ?></td>
    <td><?= htmlspecialchars($cus_created_at) ?></td>
    <td>
     <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editSupplierModal"
             onclick="loadSupplierForm(<?= (int)$cus_id ?>)">
      <i class="fa-regular fa-pen-to-square"></i>
     </button>
    </td>
   </tr>

   <?php endforeach; ?>

  </tbody>

 </table>

</div>
<script>
$(document).ready(function() { $('#example').DataTable({ select: true, responsive: true }); });
function loadSupplierForm(id) {
 $.ajax({
  url: '<?= BASE_URL ?>/app/Controllers/TransactionController.php',
  type: 'post',
  data: { ids: id, action: 'viewCust' },
  success: function(data) { $('#supplierFormHolder').html(data); }
 });
}

</script>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
