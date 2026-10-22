<?php
/**
 * One labelled form input.
 * @var string $name  @var string $label  @var mixed $value
 * Optional: $type (text), $hint, $attrs (extra HTML attributes), $options (for select: value => label), $class
 */
$type = $type ?? 'text';
$err = error_for($name);
$current = $type === 'password' ? '' : old($name, $value ?? '');
$attrs = $attrs ?? '';
?>
<label class="field<?= $err ? ' invalid' : '' ?><?= isset($class) ? ' ' . e($class) : '' ?>">
  <span><?= e($label) ?></span>
  <?php if ($type === 'select'): ?>
    <select name="<?= e($name) ?>" <?= $attrs ?>>
      <?php foreach ($options as $optValue => $optLabel): ?>
        <option value="<?= e($optValue) ?>"<?= (string) $optValue === (string) $current ? ' selected' : '' ?>><?= e($optLabel) ?></option>
      <?php endforeach; ?>
    </select>
  <?php elseif ($type === 'textarea'): ?>
    <textarea name="<?= e($name) ?>" <?= $attrs ?>><?= e($current) ?></textarea>
  <?php else: ?>
    <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($current) ?>" <?= $attrs ?>>
  <?php endif; ?>
  <?php if ($err): ?><span class="field-error"><?= e($err) ?></span><?php elseif (!empty($hint)): ?><span class="hint"><?= e($hint) ?></span><?php endif; ?>
</label>
