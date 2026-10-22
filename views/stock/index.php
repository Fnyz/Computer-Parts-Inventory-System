<?php
use App\Core\View;
use App\Repositories\StockMovementRepository;
use App\Services\StockService;

$pillFor = ['sale' => 'pill-ok', 'purchase' => 'pill-warn', 'opening' => 'pill-info', 'adjustment' => 'pill-info', 'return' => 'pill-info', 'void' => 'pill-muted'];
?>
<div class="page-head">
  <div>
    <div class="tag">J1 · LEDGER</div>
    <h1>Stock movements</h1>
    <p class="lede">Every change to stock: purchases received, sales, voids and adjustments.<?= $product ? ' Showing ' . e($product['name']) . '.' : '' ?></p>
  </div>
  <div class="actions"><a class="btn" href="<?= e(url('/stock' . query_with(['export' => 'csv', 'page' => null]))) ?>"><?= icon('download', 16) ?>Export CSV</a></div>
</div>

<form class="filters" method="get" action="<?= e(url('/stock')) ?>">
  <label class="search" style="flex:1 1 240px"><?= icon('search', 16) ?><span class="sr-only">Search</span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="SKU, product or note">
  </label>
  <label class="sr-only" for="m-type">Type</label>
  <select id="m-type" class="select" name="type" style="width:auto">
    <option value="">All types</option>
    <?php foreach (StockService::TYPES as $value => $label): ?>
      <option value="<?= e($value) ?>"<?= $filters['type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>From</span><input class="input" type="date" name="from" value="<?= e($filters['from']) ?>" style="width:auto"></label>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>To</span><input class="input" type="date" name="to" value="<?= e($filters['to']) ?>" style="width:auto"></label>
  <?php if ($filters['product']): ?><input type="hidden" name="product" value="<?= e($filters['product']) ?>"><?php endif; ?>
  <button type="submit" class="btn">Filter</button>
  <?php if (array_filter($filters)): ?><a class="btn" href="<?= e(url('/stock')) ?>">Clear</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Date</th><th>Part</th><th>Type</th><th>Reference / note</th><th class="r">Change</th><th class="r">Balance</th><th>By</th></tr></thead>
    <tbody>
      <?php foreach ($result['rows'] as $m): ?>
        <?php $ref = StockMovementRepository::reference($m); ?>
        <tr>
          <td class="mono small nowrap"><?= e(format_date($m['created_at'])) ?></td>
          <td><a class="row-link" href="<?= e(url('/products/' . $m['product_id'])) ?>"><?= e($m['product_name']) ?></a><span class="sub sku"><?= e($m['sku']) ?></span></td>
          <td><span class="pill <?= e($pillFor[$m['type']] ?? 'pill-muted') ?>"><?= e(StockService::TYPES[$m['type']] ?? $m['type']) ?></span></td>
          <td class="small"><?= $ref ? '<a class="mono" href="' . e(url($ref[0])) . '">' . e($ref[1]) . '</a> ' : '' ?><?= e($m['note'] ?? '') ?></td>
          <td class="r mono num <?= (int) $m['qty_change'] >= 0 ? 'up' : 'down' ?>"><?= (int) $m['qty_change'] > 0 ? '+' : '' ?><?= e($m['qty_change']) ?></td>
          <td class="r mono num"><?= e($m['balance_after']) ?></td>
          <td class="small"><?= e($m['user_name'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$result['rows']): ?>
        <tr><td colspan="7"><div class="empty"><?= icon('moves', 28) ?><b>No stock movements<?= array_filter($filters) ? ' match these filters' : ' yet' ?></b></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= View::partial('partials/pagination', ['result' => $result, 'noun' => 'movements']) ?>
