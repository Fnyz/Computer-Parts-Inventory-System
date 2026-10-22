<?php $messages = App\Core\Session::flashes(); ?>
<?php if ($messages): ?>
  <div class="flashes" role="status">
    <?php foreach ($messages as $type => $message): ?>
      <div class="flash flash-<?= e($type) ?>">
        <?= icon($type === 'success' ? 'check' : ($type === 'error' ? 'x' : 'bell'), 16) ?>
        <span><?= e($message) ?></span>
        <button type="button" data-dismiss aria-label="Dismiss"><?= icon('x', 14) ?></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
