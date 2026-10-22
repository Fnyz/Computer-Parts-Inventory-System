<?php
use App\Auth\Auth;
use App\Auth\Permissions;

$user = Auth::user();
$path = $request->path ?? '/';

// [label, url, icon, ability, group]; links appear only when the user's account type allows them
$links = [
    ['Dashboard', '/', 'dashboard', 'dashboard.view', null],
    ['Products', '/products', 'cpu', 'products.view', 'J1 · INVENTORY'],
    ['Categories', '/categories', 'layers', 'categories.manage', 'J1 · INVENTORY'],
    ['Stock movements', '/stock', 'moves', 'stock.view', 'J1 · INVENTORY'],
    ['Point of sale', '/pos', 'cart', 'pos.use', 'J2 · TRANSACTIONS'],
    ['Sales', '/sales', 'receipt', 'sales.view', 'J2 · TRANSACTIONS'],
    ['Purchase orders', '/purchase-orders', 'clipboard', 'po.view', 'J2 · TRANSACTIONS'],
    ['Suppliers', '/suppliers', 'truck', 'suppliers.view', 'J3 · ADMIN'],
    ['Reports', '/reports', 'chart', 'reports.view', 'J3 · ADMIN'],
    ['Users', '/users', 'users', 'users.manage', 'J3 · ADMIN'],
    ['Activity log', '/activity', 'log', 'activity.view', 'J3 · ADMIN'],
];
$group = null;
?>
<aside class="sidebar">
  <div class="sidebar-head">
    <a class="brand" href="<?= e(url('/')) ?>"><?= icon('chip', 30) ?><span class="brand-name">PARTSDESK</span></a>
  </div>
  <nav aria-label="Main">
    <?php foreach ($links as [$label, $href, $iconName, $ability, $linkGroup]): ?>
      <?php if (!Auth::can($ability)) { continue; } ?>
      <?php if ($linkGroup !== $group && $linkGroup !== null): $group = $linkGroup; ?>
        <div class="nav-group"><?= e($linkGroup) ?></div>
      <?php endif; ?>
      <?php $active = $href === '/' ? $path === '/' : str_starts_with($path, $href); ?>
      <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= e(url($href)) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
        <span class="led"></span><?= icon($iconName) ?><?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <a class="sidebar-user" href="<?= e(url('/profile')) ?>">
      <b><?= e($user['name'] ?? '') ?></b>
      <span class="tag"><?= e(strtoupper(Permissions::label($user['role'] ?? ''))) ?> · <span class="status-ok">● ONLINE</span></span>
    </a>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-sm btn-block"><?= icon('power', 16) ?>Sign out</button>
    </form>
  </div>
</aside>
