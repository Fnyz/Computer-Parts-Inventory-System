<?php
use App\Auth\Permissions;
use App\Core\View;
?>
<div class="page-head">
  <div>
    <div class="tag">ACCOUNT · <?= e(strtoupper(Permissions::label($user['role']))) ?></div>
    <h1>My account</h1>
  </div>
</div>

<form class="panel panel-pad form" method="post" action="<?= e(url('/profile')) ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="form-grid">
    <?= View::partial('partials/field', ['name' => 'name', 'label' => 'Full name', 'value' => $user['name'], 'attrs' => 'required']) ?>
    <?= View::partial('partials/field', ['name' => 'username', 'label' => 'Username', 'value' => $user['username'], 'attrs' => 'required class="mono"']) ?>
    <?= View::partial('partials/field', ['name' => 'email', 'label' => 'Email (optional)', 'type' => 'email', 'value' => $user['email'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'phone', 'label' => 'Phone (optional)', 'value' => $user['phone'] ?? '']) ?>
  </div>
  <fieldset>
    <legend>Change password</legend>
    <div class="form-grid">
      <?= View::partial('partials/field', ['name' => 'current_password', 'label' => 'Current password', 'type' => 'password', 'attrs' => 'autocomplete="current-password"']) ?>
      <?= View::partial('partials/field', ['name' => 'password', 'label' => 'New password', 'type' => 'password', 'attrs' => 'autocomplete="new-password" minlength="8"', 'hint' => 'Leave both blank to keep your current password.']) ?>
    </div>
  </fieldset>
  <div class="form-actions"><button type="submit" class="btn btn-primary">Save</button></div>
</form>
