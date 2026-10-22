<?php
/** @var string $status */
$map = ['draft' => ['status-off', 'Draft'], 'ordered' => ['status-info', 'Ordered'], 'received' => ['status-ok', 'Received'], 'cancelled' => ['status-out', 'Cancelled']];
[$class, $label] = $map[$status] ?? ['status-off', ucfirst($status)];
?>
<span class="status <?= e($class) ?>"><span class="led"></span><?= e($label) ?></span>
