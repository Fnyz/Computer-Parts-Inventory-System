<?php
use App\Auth\Auth;
use App\Repositories\StockMovementRepository;
use App\Services\StockService;

$cost = db_cents($product['cost_price']);
$price = db_cents($product['selling_price']);
$qty = (int) $product['qty_on_hand'];
$margin = $price > 0 ? ($price - $cost) / $price * 100 : 0;
$pillFor = ['sale' => 'pill-ok', 'purchase' => 'pill-warn', 'opening' => 'pill-info', 'adjustment' => 'pill-info', 'return' => 'pill-info', 'void' => 'pill-muted'];
?>
<div class="breadcrumb"><a href="<?= e(url('/products')) ?>">Products</a> / <?= e($product['category_name'] ?? 'Uncategorized') ?> / <?= e($product['sku']) ?></div>

<div class="page-head" style="padding-bottom:16px;border-bottom:1px solid var(--line)">
  <div>
    <div class="tag">DATASHEET · REV <?= e(date('Y-m-d', (int) strtotime($product['updated_at']))) ?></div>
    <h1><?= e($product['name']) ?></h1>
    <div class="actions" style="margin-top:8px;gap:14px">
      <span class="sku-badge"><?= e($product['sku']) ?></span>
      <?= stock_badge($qty, (int) $product['reorder_level']) ?>
      <span class="mono small"><?= e($qty) ?> on hand · reorder at <?= e($product['reorder_level']) ?></span>
      <?php if ($product['archived_at']): ?><span class="pill pill-muted">Archived</span><?php endif; ?>
    </div>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('/products/' . $product['id'] . '/label')) ?>"><?= icon('barcode', 16) ?>Print label</a>
    <?php if (Auth::can('po.manage') && !$product['archived_at']): ?>
      <a class="btn" href="<?= e(url('/purchase-orders/new?product=' . $product['id'] . ($product['supplier_id'] ? '&supplier=' . $product['supplier_id'] : ''))) ?>"><?= icon('clipboard', 16) ?>Reorder</a>
    <?php endif; ?>
    <?php if (Auth::can('stock.adjust')): ?>
      <a class="btn" href="<?= e(url('/products/' . $product['id'] . '/adjust')) ?>"><?= icon('moves', 16) ?>Adjust stock</a>
    <?php endif; ?>
    <?php if (Auth::can('products.manage')): ?>
      <a class="btn btn-primary" href="<?= e(url('/products/' . $product['id'] . '/edit')) ?>"><?= icon('edit', 16) ?>Edit product</a>
    <?php endif; ?>
  </div>
</div>

<div class="cols">
  <div class="form">
    <div class="sheet photo">
      <?php if ($product['image_url']): ?>
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
      <?php else: ?>
        <?= icon(category_icon($product['category_name']), 96) ?>
      <?php endif; ?>
    </div>
    <div class="sheet" style="padding:14px 16px;display:grid;justify-items:center;gap:8px">
      <div class="label-box"><?= App\Services\BarcodeService::svg($product['sku']) ?><span class="mono small" style="letter-spacing:.1em"><?= e($product['sku']) ?></span></div>
    </div>
    <?php if (Auth::can('products.manage')): ?>
      <section class="sheet">
        <div class="sec"><b>§3</b><h2>Pricing</h2></div>
        <dl class="kv" style="margin:0">
          <dt>Cost price</dt><dd class="r mono num"><?= e(money($cost)) ?></dd>
          <dt>Selling price</dt><dd class="r mono num" style="font-weight:700"><?= e(money($price)) ?></dd>
          <dt>Margin</dt><dd class="r mono num <?= $margin >= 0 ? 'up' : 'down' ?>"><?= e(number_format($margin, 1)) ?>%</dd>
          <dt style="border-bottom:0">Stock value at cost</dt><dd class="r mono num" style="border-bottom:0"><?= e(money($cost * $qty)) ?></dd>
        </dl>
      </section>
    <?php else: ?>
      <section class="sheet"><div class="sec"><b>§3</b><h2>Price</h2><span class="end mono" style="font-weight:700"><?= e(money($price)) ?></span></div></section>
    <?php endif; ?>
  </div>

  <div class="wide form">
    <section class="sheet">
      <div class="sec"><b>§1</b><h2>Specifications</h2><span class="tag end">CATEGORY · <?= e(strtoupper($product['category_name'] ?? 'Uncategorized')) ?></span></div>
      <dl class="kv" style="margin:0">
        <dt>Brand</dt><dd><?= e($product['brand'] ?? '—') ?></dd>
        <dt>Model</dt><dd class="mono"><?= e($product['model'] ?? '—') ?></dd>
        <?php foreach ($specs as $field => $value): ?>
          <dt><?= e($field) ?></dt><dd class="mono"><?= e($value) ?></dd>
        <?php endforeach; ?>
        <dt>Usual supplier</dt><dd><?= $product['supplier_id'] ? '<a href="' . e(url('/suppliers/' . $product['supplier_id'])) . '">' . e($product['supplier_name']) . '</a>' : '—' ?></dd>
        <dt style="border-bottom:0">Description</dt><dd style="border-bottom:0;white-space:pre-line"><?= e($product['description'] ?? '—') ?></dd>
      </dl>
    </section>

    <section class="sheet">
      <div class="sec"><b>§2</b><h2>Stock ledger</h2><a class="end small" href="<?= e(url('/stock?product=' . $product['id'])) ?>">Full history</a></div>
      <?php if ($moves): ?>
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Reference</th><th class="r">Change</th><th class="r">Balance</th><th>By</th></tr></thead>
            <tbody>
              <?php foreach ($moves as $m): ?>
                <?php $ref = StockMovementRepository::reference($m); ?>
                <tr>
                  <td class="mono small nowrap"><?= e(format_date($m['created_at'], 'M j · H:i')) ?></td>
                  <td><span class="pill <?= e($pillFor[$m['type']] ?? 'pill-muted') ?>"><?= e(StockService::TYPES[$m['type']] ?? $m['type']) ?></span></td>
                  <td class="small"><?= $ref ? '<a class="mono" href="' . e(url($ref[0])) . '">' . e($ref[1]) . '</a>' : e($m['note'] ?? '') ?></td>
                  <td class="r mono num <?= (int) $m['qty_change'] >= 0 ? 'up' : 'down' ?>"><?= (int) $m['qty_change'] > 0 ? '+' : '' ?><?= e($m['qty_change']) ?></td>
                  <td class="r mono num"><?= e($m['balance_after']) ?></td>
                  <td class="small"><?= e($m['user_name'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty">No stock movements yet.</div>
      <?php endif; ?>
    </section>

    <?php if (Auth::can('products.manage')): ?>
      <form method="post" action="<?= e(url('/products/' . $product['id'] . '/archive')) ?>"<?= $product['archived_at'] ? '' : ' data-confirm="Archive ' . e($product['name']) . '? It disappears from the point of sale and purchase orders, and keeps its history."' ?>>
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm<?= $product['archived_at'] ? '' : ' btn-danger' ?>"><?= icon('archive', 14) ?><?= $product['archived_at'] ? 'Restore product' : 'Archive product' ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>
