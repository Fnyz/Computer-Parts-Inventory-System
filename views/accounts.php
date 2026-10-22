<?php
require_once __DIR__ . '/../config.php';
session_start();
if (!isset($_SESSION['adminId'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }
require_once BASEPATH . '/app/Helpers/auth.php';
require_once BASEPATH . '/app/Helpers/csrf.php';
if (!is_admin()) {
 $_SESSION['Error'] = 'Only an Admin can manage accounts.';
 header('Location: ' . BASE_URL . '/views/dashboard.php');
 exit;
}
require_once BASEPATH . '/app/Models/Sale.php';
require_once BASEPATH . '/app/Models/User.php';
$sale = new Sale();
$sale->user_id = $_SESSION['adminId'];
$r = $sale->userGet();
$userModel = new User();
$acc = $userModel->showUser();
$activePage = 'accounts';
$pageTitle = 'Accounts - Inventory Management System';
require_once BASEPATH . '/views/layouts/header.php';
require_once BASEPATH . '/views/layouts/nav.php';
?>

<div class="m-3 p-3 rounded" style="border:1px solid gray;height:100%;">
 <h1 class="text text-center text-primary" style="font-family:'Playfair Display',serif;">ACCOUNTS</h1>
 <div class="mb-3">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
   <i class="fa-solid fa-plus"></i>
  </button>
  <label class="text text-primary fw-bold">ADD ACCOUNT</label>

 </div>
 <!-- Add Account Modal -->
 <div class="modal fade" id="addAccountModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
   <div class="modal-content">
    <div class="modal-header bg-primary">
     <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;">
      <i class="fa-sharp fa-solid fa-user-plus me-2"></i>New Account
     </h1>
     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

    </div>
    <form action="<?= BASE_URL ?>/app/Controllers/UserController.php" method="post">
     <input type="hidden" name="_action" value="insert">
     <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
     <div class="modal-body">
      <div class="form-floating mb-3">
       <select name="userType" id="newUserType" class="form-select" required>
        <?php foreach (USER_ROLES as $role): ?>
         <option value="<?= htmlspecialchars($role) ?>" <?= $role === 'Staff' ? 'selected' : '' ?>><?= htmlspecialchars($role) ?></option>

        <?php endforeach; ?>
       </select>
       <label for="newUserType">Account type:</label>
      </div>
      <div class="form-floating mb-3">
       <input type="text" class="form-control" id="newUsername" name="user" placeholder="Username" required>
       <label for="newUsername">Username:</label>
      </div>
      <div class="form-floating mb-3">
       <input type="password" class="form-control" id="newPassword" name="pass" placeholder="Password" minlength="8" required autocomplete="new-password">
       <label for="newPassword">Password (at least 8 characters):</label>
      </div>
      <hr>
      <div class="form-floating mb-3">
       <input type="text" class="form-control" id="newFirst" name="first" placeholder="First name">
       <label for="newFirst">First name:</label>
      </div>
      <div class="form-floating mb-3">
       <input type="text" class="form-control" id="newLast" name="last" placeholder="Last name">
       <label for="newLast">Last name:</label>
      </div>
      <div class="form-floating">
       <input type="text" class="form-control" id="newPhone" name="phone" placeholder="Phone number">
       <label for="newPhone">Phone number:</label>
      </div>

     </div>
     <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      <button type="submit" class="btn btn-success" name="save">Save Account</button>

     </div>
    </form>

   </div>

  </div>

 </div>
 <div class="modal fade" id="editAccountModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
   <div class="modal-content">
    <div class="modal-header bg-success">
     <h1 class="modal-title fs-5 text text-white fw-bold text-uppercase" style="font-family:'Playfair Display',serif;">
      <i class="fa-sharp fa-solid fa-pen-to-square me-2"></i>Edit Account
     </h1>
     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

    </div>
    <div id="accountFormHolder"></div>

   </div>

  </div>

 </div>

 <?php foreach ($r as $val): extract($val); break; endforeach; ?>
 <input type="hidden" id="idUser" value="<?= (int)($user_id ?? 0) ?>">
 <table id="example" class="table-strip" style="width:100%">
  <thead>
   <tr>
    <th>Account Name</th><th>First Name</th><th>Last Name</th>
    <th>Phone #</th><th>UserType</th><th>Created Date</th><th>Action</th>
   </tr>

  </thead>
  <tbody>

   <?php foreach ($acc as $val): extract($val); ?>
   <tr>
    <td><?= htmlspecialchars($username) ?></td>
    <td><?= htmlspecialchars($user_fname) ?></td>
    <td><?= htmlspecialchars($user_lname) ?></td>
    <td><?= htmlspecialchars($user_phone) ?></td>
    <td><?= htmlspecialchars($userType) ?></td>
    <td><?= htmlspecialchars($user_created_at) ?></td>
    <td>
     <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editAccountModal"
             onclick="loadAccountForm(<?= (int)$user_id ?>)">
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
function loadAccountForm(id) {
 var fill = $('#accountFormHolder');
 $.ajax({
  url: '<?= BASE_URL ?>/app/Controllers/TransactionController.php',
  type: 'post',
  data: { ids: id, action: 'viewUser' },
  success: function(data) { fill.html(data); }
 });
}

</script>

<?php require_once BASEPATH . '/views/layouts/footer.php'; ?>
