<?php
use App\Auth\Auth;
use App\Core\View;
use App\Repositories\SaleRepository;
?>
<div class="page-head">
  <div>
    <div class="tag">J2 · STOCK OUT</div>
    <h1>Sales</h1>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('/sales' . query_with(['export' => 'csv', 'page' => null]))) ?>"><?= icon('download', 16) ?>Export CSV</a>
    <?php if (Auth::can('pos.use')): ?><a class="btn btn-primary" href="<?= e(url('/pos')) ?>"><?= icon('cart', 16) ?>New sale</a><?php endif; ?>
  </div>
</div>

<div class="kpis">
  <div class="panel kpi"><div class="tag">REVENUE · COMPLETED SALES</div><div class="kpi-value"><?= e(money($summary['revenue'])) ?></div><div class="kpi-note"><?= $filters['from'] || $filters['to'] ? 'For the dates chosen' : 'All time' ?></div></div>
  <div class="panel kpi"><div class="tag">RECEIPTS</div><div class="kpi-value"><?= e(number_format($summary['receipts'])) ?></div><div class="kpi-note">avg <?= e(money($summary['receipts'] ? intdiv($summary['revenue'], $summary['receipts']) : 0)) ?></div></div>
  <div class="panel kpi"><div class="tag">DISCOUNTS GIVEN</div><div class="kpi-value"><?= e(money($summary['discounts'])) ?></div></div>
</div>

<form class="filters" method="get" action="<?= e(url('/sales')) ?>">
  <label class="search" style="flex:1 1 220px"><?= icon('search', 16) ?><span class="sr-only">Search</span>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Receipt number or customer">
  </label>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>From</span><input class="input" type="date" name="from" value="<?= e($filters['from']) ?>" style="width:auto"></label>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>To</span><input class="input" type="date" name="to" value="<?= e($filters['to']) ?>" style="width:auto"></label>
  <label class="sr-only" for="s-user">Cashier</label>
  <select id="s-user" class="select" name="user" style="width:auto">
    <option value="">All cashiers</option>
    <?php foreach ($cashiers as $id => $name): ?><option value="<?= e($id) ?>"<?= (int) $filters['user'] === (int) $id ? ' selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="s-method">Payment</label>
  <select id="s-method" class="select" name="method" style="width:auto">
    <option value="">Any payment</option>
    <?php foreach (SaleRepository::METHODS as $value => $label): ?><option value="<?= e($value) ?>"<?= $filters['method'] === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
  </select>
  <label class="sr-only" for="s-status">Status</label>
  <select id="s-status" class="select" name="status" style="width:auto">
    <option value="">Completed and voided</option>
    <option value="completed"<?= $filters['status'] === 'completed' ? ' selected' : '' ?>>Completed</option>
    <option value="voided"<?= $filters['status'] === 'voided' ? ' selected' : '' ?>>Voided</option>
  </select>
  <button type="submit" class="btn">Filter</button>
  <?php if (array_filter($filters)): ?><a class="btn" href="<?= e(url('/sales')) ?>">Clear</a><?php endif; ?>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Customer</th><th>Payment</th><th class="r">Items</th><th class="r">Total</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($result['rows'] as $s): ?>
        <tr>
          <td><a class="row-link mono" href="<?= e(url('/sales/' . $s['id'])) ?>"><?= e($s['sale_number']) ?></a></td>
          <td class="mono small nowrap"><?= e(format_date($s['created_at'])) ?></td>
          <td><?= e($s['cashier_name'] ?? '—') ?></td>
          <td><?= e($s['customer_name'] ?? '—') ?></td>
          <td><?= e(SaleRepository::METHODS[$s['payment_method']] ?? $s['payment_method']) ?></td>
          <td class="r mono num"><?= e($s['item_count']) ?></td>
          <td class="r mono num"<?= $s['status'] === 'voided' ? ' style="text-decoration:line-through"' : '' ?>><?= e(money(db_cents($s['total']))) ?></td>
          <td><?= $s['status'] === 'voided' ? '<span class="pill pill-bad">Voided</span>' : '<span class="pill pill-ok">Completed</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$result['rows']): ?>
        <tr><td colspan="8"><div class="empty"><?= icon('receipt', 28) ?><b>No sales<?= array_filter($filters) ? ' match these filters' : ' yet' ?></b><?= Auth::can('pos.use') && !array_filter($filters) ? '<a href="' . e(url('/pos')) . '">Open the point of sale</a>' : '' ?></div></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?= View::partial('partials/pagination', ['result' => $result, 'noun' => 'sales']) ?>
