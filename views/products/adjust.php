<?php
use App\Core\View;
use App\Services\StockService;

$mode = old('mode', 'set');
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/products')) ?>">Products</a> / <a href="<?= e(url('/products/' . $product['id'])) ?>"><?= e($product['sku']) ?></a> / Adjust stock</div>
    <h1>Adjust stock</h1>
    <p class="lede"><?= e($product['name']) ?> · <b class="mono"><?= e($product['qty_on_hand']) ?></b> on hand now. Every adjustment is recorded in the ledger with your name and the reason.</p>
  </div>
</div>

<form class="panel panel-pad form" method="post" action="<?= e(url('/products/' . $product['id'] . '/adjust')) ?>" style="max-width:640px">
  <?= csrf_field() ?>
  <fieldset>
    <legend>What happened?</legend>
    <div class="chips" role="radiogroup">
      <?php foreach (['set' => 'I counted the stock', 'add' => 'Add units', 'remove' => 'Remove units'] as $value => $label): ?>
        <label class="chip<?= $mode === $value ? ' on' : '' ?>"><input type="radio" name="mode" value="<?= e($value) ?>"<?= $mode === $value ? ' checked' : '' ?> style="margin-right:8px"><?= e($label) ?></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <div class="form-grid">
    <?= View::partial('partials/field', ['name' => 'qty', 'label' => 'Quantity', 'type' => 'number', 'value' => $mode === 'set' ? $product['qty_on_hand'] : '', 'attrs' => 'required min="0" step="1" class="mono"', 'hint' => 'For a count, enter how many you actually have.']) ?>
    <?= View::partial('partials/field', ['name' => 'reason', 'label' => 'Reason', 'type' => 'select', 'options' => array_combine(StockService::REASONS, StockService::REASONS), 'value' => 'Stock count']) ?>
    <?= View::partial('partials/field', ['name' => 'note', 'label' => 'Note (optional)', 'value' => '', 'attrs' => 'maxlength="200"', 'class' => 'span-2']) ?>
  </div>
  <div class="form-actions">
    <a class="btn" href="<?= e(url('/products/' . $product['id'])) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary">Save adjustment</button>
  </div>
</form>
