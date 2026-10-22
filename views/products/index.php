<?php
use App\Auth\Auth;
use App\Core\View;

$statusChips = ['' => 'All', 'ok' => 'In stock', 'low' => 'Low', 'out' => 'Out'];
?>
<div class="page-head">
  <div>
    <div class="tag">SLOT_02 · INVENTORY</div>
    <h1>Products</h1>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('/products' . query_with(['export' => 'csv', 'page' => null]))) ?>"><?= icon('download', 16) ?>Export CSV</a>
    <?php if (Auth::can('products.manage')): ?>
      <a class="btn btn-primary" href="<?= e(url('/products/new')) ?>"><?= icon('plus', 16) ?>New product</a>
    <?php endif; ?>
  </div>
</div>

<form class="filters" method="get" action="<?= e(url('/products')) ?>">
  <label class="search" style="flex:1 1 260px"><?= icon('search', 16) ?><span class="sr-only">Search products</span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by SKU, name, brand or model">
  </label>
  <label class="sr-only" for="f-category">Category</label>
  <select id="f-category" class="select" name="category" data-autosubmit style="width:auto;min-width:160px">
    <option value="">All categories</option>
    <?php foreach ($categories as $id => $name): ?>
      <option value="<?= e($id) ?>"<?= (int) $filters['category'] === (int) $id ? ' selected' : '' ?>><?= e($name) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="sr-only" for="f-sort">Sort</label>
  <select id="f-sort" class="select" name="sort" data-autosubmit style="width:auto">
    <?php foreach (['name' => 'Sort: name', 'sku' => 'Sort: SKU', 'stock' => 'Sort: lowest stock', 'price' => 'Sort: highest price', 'newest' => 'Sort: newest'] as $value => $label): ?>
      <option value="<?= e($value) ?>"<?= $filters['sort'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="chips" role="group" aria-label="Stock status">
    <?php foreach ($statusChips as $value => $label): ?>
      <a class="chip<?= $filters['status'] === $value ? ' on' : '' ?>" href="<?= e(url('/products' . query_with(['status' => $value, 'page' => null]))) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
    <a class="chip<?= $filters['archived'] ? ' on' : '' ?>" href="<?= e(url('/products' . query_with(['show' => $filters['archived'] ? null : 'archived', 'page' => null]))) ?>">Archived</a>
  </div>
  <?php foreach (['status', 'show'] as $keep): ?>
    <?php if (!empty($_GET[$keep])): ?><input type="hidden" name="<?= e($keep) ?>" value="<?= e($_GET[$keep]) ?>"><?php endif; ?>
  <?php endforeach; ?>
  <noscript><button type="submit" class="btn">Filter</button></noscript>
</form>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr><th>Part</th><th>SKU</th><th>Category</th><?php if (Auth::can('products.manage')): ?><th class="r">Cost</th><?php endif; ?><th class="r">Price</th><th class="r">On hand</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php foreach ($result['rows'] as $p): ?>
        <?php $status = stock_status((int) $p['qty_on_hand'], (int) $p['reorder_level']); ?>
        <tr>
          <td>
            <div class="part">
              <span class="thumb"><?= $p['image_url'] ? '<img src="' . e($p['image_url']) . '" alt="" loading="lazy">' : icon(category_icon($p['category_name']), 20) ?></span>
              <div><a class="row-link" href="<?= e(url('/products/' . $p['id'])) ?>"><?= e($p['name']) ?></a><span class="sub"><?= e(trim(($p['brand'] ?? '') . ' ' . ($p['model'] ?? ''))) ?></span></div>
            </div>
          </td>
          <td class="sku"><?= e($p['sku']) ?></td>
          <td><?= e($p['category_name'] ?? 'Uncategorized') ?></td>
          <?php if (Auth::can('products.manage')): ?><td class="r mono num"><?= e(money(db_cents($p['cost_price']))) ?></td><?php endif; ?>
          <td class="r mono num"><?= e(money(db_cents($p['selling_price']))) ?></td>
          <td class="r mono num status-<?= e($status['key']) ?>" style="font-weight:600"><?= e($p['qty_on_hand']) ?></td>
          <td><?= stock_badge((int) $p['qty_on_hand'], (int) $p['reorder_level']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$result['rows']): ?>
        <tr><td colspan="7">
          <div class="empty"><?= icon('cpu', 28) ?>
            <?php if ($filters['q'] !== '' || $filters['category'] || $filters['status'] !== ''): ?>
              <b>No products match these filters</b><a href="<?= e(url('/products')) ?>">Clear the filters</a>
            <?php else: ?>
              <b>No products yet</b><?= Auth::can('products.manage') ? '<a href="' . e(url('/products/new')) . '">Add the first product</a>' : 'An Admin or Staff member can add products.' ?>
            <?php endif; ?>
          </div>
        </td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= View::partial('partials/pagination', ['result' => $result, 'noun' => 'SKUs']) ?>
