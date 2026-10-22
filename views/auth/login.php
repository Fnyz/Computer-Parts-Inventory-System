<?php
$boot = $boot ?? [];
$error = error_for('username');
?>
<div class="boot-log" aria-hidden="true">
  <div class="title">PARTSDESK BIOS v2.0 · INVENTORY SYSTEM</div>
  <div class="muted" style="margin-bottom:14px">Computer Parts Inventory System</div>
  <div>CPU  : Inventory core ............... <span class="ok">OK</span></div>
  <div>DISK : Database ..................... <span class="ok">OK</span></div>
  <?php if (isset($boot['skus'])): ?>
    <div>MEM  : <?= e(str_pad(number_format($boot['skus']) . ' SKUs indexed ', 27, '.')) ?> <span class="ok">OK</span></div>
  <?php endif; ?>
  <?php if (!empty($boot['low'])): ?>
    <div>WARN : <?= e(str_pad($boot['low'] . ' parts below reorder level ', 27, '.')) ?> <span class="warn">CHECK</span></div>
  <?php endif; ?>
  <div style="margin-top:14px;color:var(--fg)">Boot device found. Waiting for operator sign-in<span class="cursor">_</span></div>
</div>

<form class="auth-card" method="post" action="<?= e(url('/login')) ?>" novalidate>
  <div class="tag silk">U1 · AUTH CONTROLLER</div>
  <div class="auth-brand">
    <?= icon('chip', 40) ?>
    <div>
      <div class="brand-name">PARTSDESK</div>
      <div class="tag" style="margin-top:4px">Computer parts inventory</div>
    </div>
  </div>
  <div class="trace-rule"></div>
  <?= App\Core\View::partial('partials/flash') ?>
  <?= csrf_field() ?>
  <label class="field<?= $error ? ' invalid' : '' ?>">
    <span>Username</span>
    <input class="mono" type="text" name="username" value="<?= e(old('username')) ?>" autocomplete="username" autofocus required>
  </label>
  <label class="field<?= $error ? ' invalid' : '' ?>">
    <span>Password</span>
    <input class="mono" type="password" name="password" autocomplete="current-password" required>
  </label>
  <button type="submit" class="btn btn-primary"><?= icon('power', 18) ?>Sign in</button>
  <p class="hint" style="margin:0">No account? Ask your administrator. Only an Admin can create accounts.</p>
  <div class="tag" style="display:flex;justify-content:space-between"><span>ENTER = sign in</span><span class="status-ok">● SYS ONLINE</span></div>
</form>
