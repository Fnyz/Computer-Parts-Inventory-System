<?php
require_once __DIR__ . '/../../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/Product.php';
require_once BASEPATH . '/app/Helpers/csrf.php';
$sale = new Sale();
$sale->user_id = $_SESSION['adminId'];
$r = $sale->userGet();
$productModel = new Product();
$products = $productModel->showAll();
$activePage = 'products';
$pageTitle = 'Products - Inventory Management System';
require_once BASEPATH . '/views/layouts/header.php';
require_once BASEPATH . '/views/layouts/nav.php';
?>

<div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
 <h1 class="text text-center text-primary" style="font-family:'Playfair Display',serif;">COMPUTER PRODUCTS</h1>
 <div class="mb-3">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
   <i class="fa-solid fa-plus"></i>
  </button>
  <label class="text text-primary fw-bold">ADD PRODUCT</label>

 </div>
 <!-- Add Product Modal -->
 <div class="modal fade" id="addProductModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
   <div class="modal-header bg-danger">
    <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;">
     <i class="fa-solid fa-cart-plus me-2"></i>New Product
    </h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

   </div>
   <form action="<?= BASE_URL ?>/app/Controllers/ProductController.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_action" value="insert">
    <div class="modal-body">
     <div class="form-floating mb-3">
      <input type="text" class="form-control" name="prod" required>
      <label>Product Name:</label>

     </div>
     <div class="form-floating mb-3">
      <input type="file" class="form-control" name="image">
      <label>Product Image:</label>

     </div>
     <div class="form-floating">
      <textarea class="form-control" name="desc"></textarea>
      <label>Description</label>

     </div>

    </div>
    <div class="modal-footer">
     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
     <button type="submit" class="btn btn-primary" name="save">Save</button>

    </div>

   </form>
  </div></div>

 </div>
 <!-- Edit Product Modal -->
 <div class="modal fade" id="editProductModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
   <div class="modal-header bg-success">
    <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;">
     <i class="fa-sharp fa-solid fa-pen-to-square me-2"></i>Edit Product
    </h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

   </div>
   <div id="productFormHolder"></div>
  </div></div>

 </div>
 <table id="example" class="table-strip" style="width:100%">
  <thead>
   <tr>
    <th>Image</th><th>Product Name</th><th>Price</th><th>Quantity</th>
    <th>Description</th><th>Date Created</th><th>Action</th><th>Status</th>
   </tr>

  </thead>
  <tbody>

   <?php foreach ($products as $val): extract($val); ?>
   <tr>
    <td><img src="<?= BASE_URL ?>/<?= htmlspecialchars($prod_image) ?>" alt="" style="width:50px;height:50px;border-radius:10%;"></td>
    <td><?= htmlspecialchars($prod_name) ?></td>
    <td><?= $prod_price ? htmlspecialchars($prod_price) : 'NULL' ?></td>
    <td><?= $prod_quan ? htmlspecialchars($prod_quan) : 'NULL' ?></td>
    <td><?= htmlspecialchars($prod_desc) ?></td>
    <td><?= htmlspecialchars($prod_created_at) ?></td>
    <td>
     <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal"
             onclick="loadProductForm(<?= (int)$prod_id ?>)">
      <i class="fa-regular fa-pen-to-square"></i>
     </button>
    </td>
    <td style="color:<?= !$prod_price ? 'red' : 'blue' ?>;" class="fw-semibold opacity-75">
     <?= !$prod_price ? 'NOT AVAILABLE' : 'AVAILABLE' ?>
    </td>
   </tr>

   <?php endforeach; ?>

  </tbody>

 </table>

</div>
<script>
$(document).ready(function() { $('#example').DataTable({ select: true, responsive: true }); });
function loadProductForm(id) {
 $.ajax({
  url: '<?= BASE_URL ?>/app/Controllers/TransactionController.php',
  type: 'POST',
  data: { ids: id, action: 'getId' },
  success: function(data) { $('#productFormHolder').html(data); }
 });
}

</script>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
