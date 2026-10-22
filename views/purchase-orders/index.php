<?php
use App\Auth\Auth;
use App\Core\View;
use App\Repositories\PurchaseOrderRepository;
?>
<div class="page-head">
  <div>
    <div class="tag">J2 · STOCK IN</div>
    <h1>Purchase orders</h1>
    <p class="lede">Draft → Ordered → Received. Receiving an order adds its parts to stock.</p>
  </div>
  <?php if (Auth::can('po.manage')): ?>
    <div class="actions"><a class="btn btn-primary" href="<?= e(url('/purchase-orders/new')) ?>"><?= icon('plus', 16) ?>New purchase order</a></div>
  <?php endif; ?>
</div>

<form class="filters" method="get" action="<?= e(url('/purchase-orders')) ?>">
  <label class="search" style="flex:1 1 240px"><?= icon('search', 16) ?><span class="sr-only">Search</span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="PO number or supplier">
  </label>
  <div class="chips" role="group" aria-label="Status">
    <a class="chip<?= $filters['status'] === '' ? ' on' : '' ?>" href="<?= e(url('/purchase-orders' . query_with(['status' => null, 'page' => null]))) ?>">All</a>
    <?php foreach (PurchaseOrderRepository::STATUSES as $value => $label): ?>
      <a class="chip<?= $filters['status'] === $value ? ' on' : '' ?>" href="<?= e(url('/purchase-orders' . query_with(['status' => $value, 'page' => null]))) ?>"><?= e($label) ?> <span class="tag" style="margin-left:6px"><?= e($counts[$value] ?? 0) ?></span></a>
    <?php endforeach; ?>
  </div>
  <?php if ($filters['status']): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif; ?>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>PO</th><th>Supplier</th><th>Status</th><th class="r">Lines</th><th class="r">Total</th><th>Created</th><th>Expected</th></tr></thead>
    <tbody>
      <?php foreach ($result['rows'] as $po): ?>
        <tr>
          <td><a class="row-link mono" href="<?= e(url('/purchase-orders/' . $po['id'])) ?>"><?= e($po['po_number']) ?></a></td>
          <td><?= e($po['supplier_name']) ?></td>
          <td><?= View::partial('purchase-orders/status', ['status' => $po['status']]) ?></td>
          <td class="r mono num"><?= e($po['line_count']) ?></td>
          <td class="r mono num"><?= e(money(db_cents($po['total']))) ?></td>
          <td class="mono small"><?= e(format_date($po['created_at'], 'M j, Y')) ?><span class="sub"><?= e($po['created_by_name'] ?? '') ?></span></td>
          <td class="mono small"><?= e($po['expected_on'] ? format_date($po['expected_on'], 'M j, Y') : '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$result['rows']): ?>
        <tr><td colspan="7"><div class="empty"><?= icon('clipboard', 28) ?><b>No purchase orders<?= $filters['status'] || $filters['q'] ? ' match these filters' : ' yet' ?></b><?= Auth::can('po.manage') && !$filters['status'] && !$filters['q'] ? 'Create one when you need to restock parts.' : '' ?></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= View::partial('partials/pagination', ['result' => $result, 'noun' => 'orders']) ?>
