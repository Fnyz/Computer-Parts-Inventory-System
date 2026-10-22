<?php
use App\Core\View;

$isNew = $product === null;
$action = url($isNew ? '/products' : '/products/' . $product['id']);
$categoryId = (int) old('category_id', $product['category_id'] ?? 0);
$oldSpecs = App\Core\Session::get('_old_now', [])['specs'] ?? null;
$specValues = is_array($oldSpecs) ? $oldSpecs : $specs;
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/products')) ?>">Products</a> / <?= $isNew ? 'New' : e($product['sku']) ?></div>
    <h1><?= $isNew ? 'New product' : 'Edit ' . e($product['name']) ?></h1>
  </div>
</div>

<form class="form" method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="cols">
    <div class="wide form">
      <fieldset>
        <legend>§1 · Identity</legend>
        <div class="form-grid">
          <?= View::partial('partials/field', ['name' => 'sku', 'label' => 'SKU', 'value' => $product['sku'] ?? '', 'attrs' => 'required maxlength="64" class="mono" autocapitalize="characters" placeholder="CPU-AMD-R5-7600"', 'hint' => 'Unique code on the label and barcode. Letters, numbers, - . _ /']) ?>
          <?= View::partial('partials/field', ['name' => 'name', 'label' => 'Product name', 'value' => $product['name'] ?? '', 'attrs' => 'required maxlength="150"']) ?>
          <?= View::partial('partials/field', ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => ['' => 'Uncategorized'] + $categories, 'value' => $categoryId ?: '', 'attrs' => 'data-spec-select']) ?>
          <?= View::partial('partials/field', ['name' => 'supplier_id', 'label' => 'Usual supplier', 'type' => 'select', 'options' => ['' => '—'] + $suppliers, 'value' => $product['supplier_id'] ?? '']) ?>
          <?= View::partial('partials/field', ['name' => 'brand', 'label' => 'Brand', 'value' => $product['brand'] ?? '', 'attrs' => 'maxlength="80"']) ?>
          <?= View::partial('partials/field', ['name' => 'model', 'label' => 'Model', 'value' => $product['model'] ?? '', 'attrs' => 'maxlength="80"']) ?>
          <?= View::partial('partials/field', ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'value' => $product['description'] ?? '', 'class' => 'span-2']) ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>§2 · Specifications</legend>
        <p class="hint" style="margin-top:0">Fields come from the category. Leave any blank that don’t apply.</p>
        <div class="form-grid" id="spec-fields">
          <?php foreach ($specFields as $catId => $fields): ?>
            <?php foreach ($fields as $field): ?>
              <label class="field" data-spec-for="<?= e($catId) ?>"<?= (int) $catId !== $categoryId ? ' hidden' : '' ?>>
                <span><?= e($field) ?></span>
                <input type="text" name="specs[<?= e($field) ?>]" value="<?= e($specValues[$field] ?? '') ?>" maxlength="120"<?= (int) $catId !== $categoryId ? ' disabled' : '' ?>>
              </label>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <?php foreach ($specValues as $field => $value): ?>
            <?php if ($categoryId && in_array($field, $specFields[$categoryId] ?? [], true)) { continue; } ?>
            <label class="field" data-spec-for="extra"><span><?= e($field) ?> <span class="tag">(not in category)</span></span>
              <input type="text" name="specs[<?= e($field) ?>]" value="<?= e($value) ?>" maxlength="120">
            </label>
          <?php endforeach; ?>
        </div>
        <p class="hint" id="spec-empty"<?= !empty($specFields[$categoryId]) ? ' hidden' : '' ?>>Choose a category to see its spec fields.</p>
      </fieldset>
    </div>

    <div class="form">
      <fieldset>
        <legend>§3 · Pricing &amp; stock</legend>
        <div class="form">
          <?= View::partial('partials/field', ['name' => 'cost_price', 'label' => 'Cost price (₱)', 'value' => isset($product) ? db_money(db_cents($product['cost_price'])) : '', 'attrs' => 'required inputmode="decimal" class="mono"']) ?>
          <?= View::partial('partials/field', ['name' => 'selling_price', 'label' => 'Selling price (₱)', 'value' => isset($product) ? db_money(db_cents($product['selling_price'])) : '', 'attrs' => 'required inputmode="decimal" class="mono"']) ?>
          <?= View::partial('partials/field', ['name' => 'reorder_level', 'label' => 'Reorder level', 'type' => 'number', 'value' => $product['reorder_level'] ?? 5, 'attrs' => 'required min="0" step="1" class="mono"', 'hint' => 'Shown as Low at or below this quantity.']) ?>
          <?php if ($isNew): ?>
            <?= View::partial('partials/field', ['name' => 'opening_qty', 'label' => 'Opening stock', 'type' => 'number', 'value' => '0', 'attrs' => 'min="0" step="1" class="mono"', 'hint' => 'Units you already have. Recorded in the stock ledger.']) ?>
          <?php else: ?>
            <div class="field"><span>On hand</span><div class="mono" style="font-size:20px;color:var(--fg)"><?= e($product['qty_on_hand']) ?> <?= stock_badge((int) $product['qty_on_hand'], (int) $product['reorder_level']) ?></div>
              <span class="hint">Change it with <a href="<?= e(url('/products/' . $product['id'] . '/adjust')) ?>">Adjust stock</a>, a purchase order or a sale.</span></div>
          <?php endif; ?>
        </div>
      </fieldset>

      <fieldset>
        <legend>§4 · Photo</legend>
        <div class="form">
          <?php if (!$isNew && $product['image_url']): ?>
            <img src="<?= e($product['image_url']) ?>" alt="Current photo of <?= e($product['name']) ?>" style="max-width:160px;border-radius:4px;border:1px solid var(--line-2)">
            <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove the photo</label>
          <?php endif; ?>
          <?= View::partial('partials/field', ['name' => 'image', 'label' => $isNew || !$product['image_url'] ? 'Upload a photo (optional)' : 'Replace the photo', 'type' => 'file', 'value' => '', 'attrs' => 'accept="image/jpeg,image/png,image/webp,image/gif"', 'hint' => 'JPG, PNG, WEBP or GIF, up to 5 MB.']) ?>
        </div>
      </fieldset>
    </div>
  </div>

  <div class="form-actions">
    <a class="btn" href="<?= e(url($isNew ? '/products' : '/products/' . $product['id'])) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary"><?= $isNew ? 'Add product' : 'Save product' ?></button>
  </div>
</form>

<script>
  // Show the spec fields for the chosen category; hidden fields are disabled so they aren't submitted
  (function () {
    var select = document.querySelector('[data-spec-select]');
    if (!select) { return; }
    function update() {
      var shown = 0;
      document.querySelectorAll('[data-spec-for]').forEach(function (label) {
        var key = label.getAttribute('data-spec-for');
        var on = key === 'extra' || key === select.value;
        label.hidden = !on;
        label.querySelector('input').disabled = !on;
        if (on && key !== 'extra') { shown++; }
      });
      document.getElementById('spec-empty').hidden = shown > 0;
    }
    select.addEventListener('change', update);
  })();
</script>
