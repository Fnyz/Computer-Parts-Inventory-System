<div class="boot-log" aria-hidden="true">
  <div class="title">PARTSDESK BIOS v2.0 · FIRST BOOT</div>
  <div class="muted" style="margin-bottom:14px">No operator accounts found</div>
  <div>DISK : Database ..................... <span class="ok">OK</span></div>
  <div>AUTH : Admin account ................ <span class="warn">MISSING</span></div>
  <div style="margin-top:14px;color:var(--fg)">Create the first Admin to continue<span class="cursor">_</span></div>
</div>

<form class="auth-card" method="post" action="<?= e(url('/setup')) ?>" novalidate>
  <div class="tag silk">U1 · FIRST-RUN SETUP</div>
  <div class="auth-brand">
    <?= icon('chip', 40) ?>
    <div>
      <div class="brand-name">SET UP</div>
      <div class="tag" style="margin-top:4px">Create the Admin account</div>
    </div>
  </div>
  <div class="trace-rule"></div>
  <?= App\Core\View::partial('partials/flash') ?>
  <?= csrf_field() ?>
  <p class="hint" style="margin:0">This screen only appears while there are no accounts. The Admin then adds Staff and Employee accounts from Users.</p>
  <?php foreach ([['name', 'Your name', 'text', 'name'], ['username', 'Username', 'text', 'username'], ['password', 'Password (at least 8 characters)', 'password', 'new-password']] as [$field, $label, $type, $auto]): ?>
    <?php $err = error_for($field); ?>
    <label class="field<?= $err ? ' invalid' : '' ?>">
      <span><?= e($label) ?></span>
      <input type="<?= e($type) ?>" name="<?= e($field) ?>" value="<?= $type === 'password' ? '' : e(old($field)) ?>" autocomplete="<?= e($auto) ?>" required>
      <?php if ($err): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
    </label>
  <?php endforeach; ?>
  <button type="submit" class="btn btn-primary">Create Admin account</button>
</form>
