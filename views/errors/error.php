<?php /** @var int $status @var string $title @var string $message */ ?>
<?php if (App\Auth\Auth::user() === null): ?>
  <div class="auth-card" role="alert">
    <div class="tag silk">ERR · <?= e($status) ?></div>
    <div class="auth-brand"><?= icon('chip', 36) ?><span class="brand-name"><?= e(strtoupper($title)) ?></span></div>
    <div class="trace-rule"></div>
    <p style="margin:0"><?= e($message) ?></p>
    <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Go to sign in</a>
  </div>
<?php else: ?>
  <div class="page-head">
    <div>
      <div class="tag">ERR · <?= e($status) ?></div>
      <h1><?= e($title) ?></h1>
      <p class="lede"><?= e($message) ?></p>
    </div>
  </div>
  <div><a class="btn" href="<?= e(url('/')) ?>">Back to the dashboard</a></div>
<?php endif; ?>
