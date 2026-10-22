<?php
use App\Core\View;

$isNew = $po === null;
$oldLines = App\Core\Session::get('_old_now', [])['lines'] ?? null;
$rows = is_array($oldLines) ? array_values($oldLines) : array_map(static fn (array $i) => [
    'product_id' => $i['product_id'], 'qty' => $i['qty_ordered'], 'unit_cost' => db_money(db_cents($i['unit_cost'])),
], $items);
if ($rows === []) {
    $rows[] = ['product_id' => '', 'qty' => '1', 'unit_cost' => ''];
}
$productOptions = array_map(static fn (array $p) => [
    'id' => (int) $p['id'], 'label' => $p['sku'] . ' · ' . $p['name'], 'cost' => db_money(db_cents($p['cost_price'])), 'stock' => (int) $p['qty_on_hand'],
], $products);
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/purchase-orders')) ?>">Purchase orders</a> / <?= $isNew ? 'New' : e($po['po_number']) ?></div>
    <h1><?= $isNew ? 'New purchase order' : 'Edit ' . e($po['po_number']) ?></h1>
    <p class="lede">Saved as a draft. Nothing changes in stock until the order is received.</p>
  </div>
</div>

<?php if ($suppliers === []): ?>
  <div class="panel panel-pad empty"><?= icon('truck', 28) ?><b>Add a supplier first</b><a href="<?= e(url('/suppliers/new')) ?>">New supplier</a></div>
<?php else: ?>
<form class="form" method="post" action="<?= e(url($isNew ? '/purchase-orders' : '/purchase-orders/' . $po['id'])) ?>" id="po-form">
  <?= csrf_field() ?>
  <div class="panel panel-pad form-grid">
    <?= View::partial('partials/field', ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'select', 'options' => ['' => 'Choose a supplier'] + $suppliers, 'value' => $supplierId ?: '', 'attrs' => 'required']) ?>
    <?= View::partial('partials/field', ['name' => 'expected_on', 'label' => 'Expected delivery (optional)', 'type' => 'date', 'value' => $po['expected_on'] ?? '']) ?>
    <?= View::partial('partials/field', ['name' => 'notes', 'label' => 'Notes for the supplier (optional)', 'type' => 'textarea', 'value' => $po['notes'] ?? '', 'class' => 'span-2', 'attrs' => 'rows="2"']) ?>
  </div>

  <section class="sheet">
    <div class="sec"><b>§1</b><h2>Parts to order</h2><span class="end tag">Unit cost defaults to the part’s current cost</span></div>
    <?php if ($err = error_for('lines')): ?><div class="flash flash-error" style="margin:12px 16px 0"><?= icon('x', 16) ?><span><?= e($err) ?></span></div><?php endif; ?>
    <div style="overflow-x:auto">
      <table class="table" id="po-lines">
        <thead><tr><th style="min-width:280px">Part</th><th class="r">Qty</th><th class="r">Unit cost (₱)</th><th class="r">Line total</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $i => $row): ?>
            <tr data-line>
              <td>
                <label class="sr-only" for="line-product-<?= $i ?>">Part</label>
                <select class="select" id="line-product-<?= $i ?>" name="lines[<?= $i ?>][product_id]" data-product>
                  <option value="">Choose a part</option>
                  <?php foreach ($productOptions as $p): ?>
                    <option value="<?= e($p['id']) ?>" data-cost="<?= e($p['cost']) ?>"<?= (string) $p['id'] === (string) $row['product_id'] ? ' selected' : '' ?>><?= e($p['label']) ?> (<?= e($p['stock']) ?> in stock)</option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="r"><input class="input mono r" type="number" min="1" step="1" name="lines[<?= $i ?>][qty]" value="<?= e($row['qty']) ?>" data-qty style="width:90px" aria-label="Quantity"></td>
              <td class="r"><input class="input mono r" type="text" inputmode="decimal" name="lines[<?= $i ?>][unit_cost]" value="<?= e($row['unit_cost']) ?>" data-cost style="width:130px" aria-label="Unit cost"></td>
              <td class="r mono num" data-total>—</td>
              <td class="r"><button type="button" class="btn btn-sm btn-danger" data-remove aria-label="Remove line"><?= icon('x', 14) ?></button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3"><button type="button" class="btn btn-sm" id="add-line"><?= icon('plus', 14) ?>Add a part</button></td>
            <td class="r mono num" style="font-weight:700" id="po-total">—</td><td></td></tr>
        </tfoot>
      </table>
    </div>
  </section>

  <div class="form-actions">
    <a class="btn" href="<?= e(url($isNew ? '/purchase-orders' : '/purchase-orders/' . $po['id'])) ?>">Cancel</a>
    <button type="submit" class="btn btn-primary">Save draft</button>
  </div>
</form>
<?php endif; ?>

<script>
  // Purchase-order lines: add/remove rows, fill the unit cost from the part, show totals (preview only)
  (function () {
    var table = document.getElementById('po-lines');
    if (!table) { return; }
    var body = table.querySelector('tbody');
    var peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    var next = body.querySelectorAll('[data-line]').length;
    function cents(v) { var n = parseFloat(String(v).replace(/[,₱\s]/g, '')); return isNaN(n) ? 0 : Math.round(n * 100); }
    function recalc() {
      var sum = 0;
      body.querySelectorAll('[data-line]').forEach(function (row) {
        var t = cents(row.querySelector('[data-cost]').value) * (parseInt(row.querySelector('[data-qty]').value, 10) || 0);
        row.querySelector('[data-total]').textContent = row.querySelector('[data-product]').value ? peso.format(t / 100) : '—';
        sum += row.querySelector('[data-product]').value ? t : 0;
      });
      document.getElementById('po-total').textContent = peso.format(sum / 100);
    }
    body.addEventListener('change', function (e) {
      if (e.target.matches('[data-product]')) {
        var opt = e.target.selectedOptions[0];
        var cost = e.target.closest('tr').querySelector('[data-cost]');
        if (opt && opt.dataset.cost && !cost.value) { cost.value = opt.dataset.cost; }
      }
      recalc();
    });
    body.addEventListener('input', recalc);
    body.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-remove]');
      if (!btn) { return; }
      if (body.querySelectorAll('[data-line]').length > 1) { btn.closest('tr').remove(); } else {
        btn.closest('tr').querySelectorAll('input, select').forEach(function (el) { el.value = el.matches('[data-qty]') ? '1' : ''; });
      }
      recalc();
    });
    document.getElementById('add-line').addEventListener('click', function () {
      var row = body.querySelector('[data-line]').cloneNode(true);
      row.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/lines\[\d+\]/, 'lines[' + next + ']');
        el.value = el.matches('[data-qty]') ? '1' : '';
        if (el.id) { el.id = el.id.replace(/\d+$/, String(next)); }
      });
      next++;
      body.appendChild(row);
      row.querySelector('[data-product]').focus();
      recalc();
    });
    recalc();
  })();
</script>
