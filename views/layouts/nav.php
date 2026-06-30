<?php
// $activePage must be set by including view before requiring this file
$activePage = $activePage ?? '';
$r = $r ?? [];
$nav = [
 'dashboard'    => ['url' => BASE_URL . '/views/dashboard.php',              'icon' => 'fa-house-user',        'label' => 'Home'],
 'accounts'     => ['url' => BASE_URL . '/views/accounts.php',               'icon' => 'fa-key',               'label' => 'Registered Accounts'],
 'suppliers'    => ['url' => BASE_URL . '/views/suppliers.php',              'icon' => 'fa-users',             'label' => 'Supplier'],
 'products'     => ['url' => BASE_URL . '/views/products.php',               'icon' => 'fa-cart-shopping',     'label' => 'Product'],
 'transactions' => ['url' => BASE_URL . '/views/transactions/create.php',    'icon' => 'fa-brands fa-gg-circle','label' => 'Transactions'],
 'history'      => ['url' => BASE_URL . '/views/transactions/history.php',   'icon' => 'fa-money-bill-1-wave', 'label' => 'History'],
 'logout'       => ['url' => BASE_URL . '/logout.php',                        'icon' => 'fa-right-from-bracket','label' => 'Log-Out'],
];
?>

<!-- Top bar -->
<div class="mw-100 d-flex justify-content-end p-2" style="border-bottom:2px solid gray; box-shadow:0px 0px 3px 0px;">

 <?php foreach ($r as $val): extract($val); break; endforeach; ?>

 <?php if (!empty($user_id)): ?>
  <button class="btn btn-success btn-sm" style="outline:none;background:none;border:none;" data-bs-toggle="modal" data-bs-target="#editUserModal">
   <i class="fa-solid fa-user-pen" style="color:blue;font-size:25px;" onclick="getEditUser(<?= (int)$user_id ?>)"></i>
  </button>

 <?php endif; ?>
 <button class="btn shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNav">
  <i class="fa-solid fa-bars fs-2 text-secondary"></i>
 </button>

</div>

<!-- Edit user modal (shared) -->
<div class="modal fade" id="editUserModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog">
  <div class="modal-content">
   <div class="modal-header bg-success">
    <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;letter-spacing:2px;">
     <i class="fa-sharp fa-solid fa-pen-to-square me-2"></i>Edit Account
    </h1>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

   </div>
   <div id="editUserFormHolder"></div>

  </div>

 </div>

</div>

<!-- Offcanvas sidebar -->
<div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="mainNav">
 <div class="offcanvas-header">
  <h5 class="offcanvas-title text-center text-secondary">INVENTORY MANAGEMENT SYSTEM</h5>
  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>

 </div>
 <div class="offcanvas-body">
  <div class="card mb-3">
   <div class="card-header"></div>
   <div class="card-body d-flex align-items-center flex-column">

    <?php foreach ($r as $val): extract($val); ?>
     <input type="hidden" id="idUser" value="<?= (int)$user_id ?>">
     <h5 class="card-title" style="font-family:'Playfair Display',serif;">
      <i class="fa-sharp fa-solid fa-user me-2"></i>Welcome <?= htmlspecialchars($username) ?>
     </h5>

    <?php break; endforeach; ?>

   </div>

  </div>
  <ul class="list-group">

   <?php foreach ($nav as $key => $item): ?>
    <li class="list-group-item <?= $activePage === $key ? 'active' : '' ?>">
     <a href="<?= htmlspecialchars($item['url']) ?>"
        class="text text-decoration-none <?= $activePage === $key ? 'text-white' : 'text-secondary' ?>">
      <i class="fa-solid <?= $item['icon'] ?> me-2"></i><?= htmlspecialchars($item['label']) ?>
     </a>
    </li>

   <?php endforeach; ?>

  </ul>

 </div>

</div>
