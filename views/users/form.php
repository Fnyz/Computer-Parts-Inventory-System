<?php
use App\Auth\Permissions;
use App\Core\View;

$isNew = $user === null;
$action = $isNew ? url('/users') : url('/users/' . $user['id']);
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/users')) ?>">Users</a> / <?= $isNew ? 'New' : e($user['username']) ?></div>
    <h1><?= $isNew ? 'New user' : 'Edit ' . e($user['name']) ?></h1>
  </div>
</div>

<form class="panel panel-pad form" method="post" action="<?= e($action) ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <div class="form-grid">
    <?= View::partial('partials/field', ['name' => 'name', 'label' => 'Full name', 'value' => $user['name'] ?? '', 'attrs' => 'required autocomplete="off"']) ?>
    <?= View::partial('partials/field', ['name' => 'username', 'label' => 'Username', 'value' => $user['username'] ?? '', 'attrs' => 'required autocomplete="off" class="mono"', 'hint' => '3–50 letters, numbers, dots, dashes or underscores.']) ?>
    <?= View::partial('partials/field', ['name' => 'email', 'label' => 'Email (optional)', 'type' => 'email', 'value' => $user['email'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'phone', 'label' => 'Phone (optional)', 'value' => $user['phone'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'role', 'label' => 'Account type', 'type' => 'select', 'options' => Permissions::ROLES, 'value' => $user['role'] ?? 'employee']) ?>
    <?= View::partial('partials/field', [
        'name' => 'password', 'label' => $isNew ? 'Password' : 'New password', 'type' => 'password',
        'attrs' => 'autocomplete="new-password"' . ($isNew ? ' required minlength="8"' : ' minlength="8"'),
        'hint' => $isNew ? 'At least 8 characters.' : 'Leave blank to keep the current password.',
    ]) ?>
  </div>
  <?php if (!$isNew): ?>
    <div>
      <label class="check"><input type="checkbox" name="is_active" value="1"<?= (int) $user['is_active'] === 1 ? ' checked' : '' ?>> Account is active</label>
      <?php if ($err = error_for('is_active')): ?><div class="field-error"><?= e($err) ?></div><?php endif; ?>
      <div class="hint">Inactive accounts can’t sign in. Their history stays.</div>
    </div>
  <?php endif; ?>
  <div class="form-actions">
    <a class="btn" href="<?= e(url('/users')) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Create user' : 'Save user' ?></button>
  </div>
</form>
