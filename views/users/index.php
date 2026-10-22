<?php use App\Auth\Permissions; ?>
<div class="page-head">
  <div>
    <div class="tag">J3 · ADMIN</div>
    <h1>Users</h1>
    <p class="lede">Admins can do everything. Staff run inventory and purchasing. Employees sell at the counter and can view stock.</p>
  </div>
  <div class="actions"><a class="btn btn-primary" href="<?= e(url('/users/new')) ?>"><?= icon('plus', 16) ?>New user</a></div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Name</th><th>Username</th><th>Account type</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><a class="row-link" href="<?= e(url('/users/' . $u['id'] . '/edit')) ?>"><?= e($u['name']) ?></a><span class="sub"><?= e($u['email'] ?? '') ?></span></td>
          <td class="mono"><?= e($u['username']) ?></td>
          <td><span class="pill <?= $u['role'] === 'admin' ? 'pill-warn' : ($u['role'] === 'staff' ? 'pill-info' : 'pill-muted') ?>"><?= e(Permissions::label($u['role'])) ?></span></td>
          <td><?= (int) $u['is_active'] === 1 ? '<span class="status status-ok"><span class="led"></span>Active</span>' : '<span class="status status-off"><span class="led"></span>Inactive</span>' ?></td>
          <td class="mono small"><?= e(format_date($u['last_login_at'])) ?></td>
          <td class="r"><a class="btn btn-sm" href="<?= e(url('/users/' . $u['id'] . '/edit')) ?>"><?= icon('edit', 14) ?>Edit</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
