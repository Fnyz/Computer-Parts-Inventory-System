<?php
use App\Core\View;
use App\Repositories\SaleRepository;

$avg = $totals['receipts'] ? intdiv($totals['revenue'], $totals['receipts']) : 0;
$margin = $totals['revenue'] ? $totals['profit'] / $totals['revenue'] * 100 : 0;
$chartDays = [];
for ($t = strtotime($from); $t <= strtotime($to) && count($chartDays) < 92; $t = strtotime('+1 day', $t)) {
    $d = date('Y-m-d', $t);
    $chartDays[] = ['date' => $d, 'sales' => $daily[$d]['revenue'] ?? 0, 'purchases' => $received[$d] ?? 0];
}
$range = 'from=' . urlencode($from) . '&to=' . urlencode($to);
$presets = [
    'Today' => [date('Y-m-d'), date('Y-m-d')],
    'Last 7 days' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    'This month' => [date('Y-m-01'), date('Y-m-d')],
    'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'This year' => [date('Y-01-01'), date('Y-m-d')],
];
?>
<div class="page-head">
  <div>
    <div class="tag">J3 · REPORTS</div>
    <h1>Reports</h1>
    <p class="lede"><?= e(format_date($from, 'M j, Y')) ?> – <?= e(format_date($to, 'M j, Y')) ?>. Completed sales only; voided sales are left out.</p>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('/reports?' . $range . '&export=pdf')) ?>"><?= icon('download', 16) ?>Sales report PDF</a>
  </div>
</div>

<form class="filters" method="get" action="<?= e(url('/reports')) ?>">
  <div class="chips" role="group" aria-label="Quick ranges">
    <?php foreach ($presets as $label => [$pf, $pt]): ?>
      <a class="chip<?= $pf === $from && $pt === $to ? ' on' : '' ?>" href="<?= e(url('/reports?from=' . $pf . '&to=' . $pt)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>From</span><input class="input" type="date" name="from" value="<?= e($from) ?>" style="width:auto"></label>
  <label class="field" style="display:flex;align-items:center;gap:6px"><span>To</span><input class="input" type="date" name="to" value="<?= e($to) ?>" style="width:auto"></label>
  <button type="submit" class="btn">Show</button>
</form>

<div class="kpis">
  <div class="panel kpi"><div class="tag">REVENUE</div><div class="kpi-value"><?= e(money($totals['revenue'])) ?></div><div class="kpi-note"><?= e(number_format($totals['receipts'])) ?> receipts · avg <?= e(money($avg)) ?></div></div>
  <div class="panel kpi"><div class="tag">GROSS PROFIT</div><div class="kpi-value <?= $totals['profit'] >= 0 ? '' : 'down' ?>"><?= e(money($totals['profit'])) ?></div><div class="kpi-note"><?= e(number_format($margin, 1)) ?>% margin, after discounts</div></div>
  <div class="panel kpi"><div class="tag">DISCOUNTS GIVEN</div><div class="kpi-value"><?= e(money($totals['discounts'])) ?></div></div>
  <div class="panel kpi"><div class="tag">STOCK VALUE · NOW</div><div class="kpi-value"><?= e(money($stock['cost_value'])) ?></div><div class="kpi-note"><?= e(number_format($stock['units'])) ?> units · <?= e($stock['skus']) ?> SKUs</div></div>
</div>

<section class="panel">
  <div class="panel-head"><div><div class="tag">SALES · PURCHASES RECEIVED</div><h2>By day</h2></div><a class="small" href="<?= e(url('/reports?' . $range . '&export=daily')) ?>">Export CSV</a></div>
  <div class="panel-body"><?= View::partial('partials/chart', ['days' => $chartDays, 'showPurchases' => true]) ?>
    <?php if (count($chartDays) >= 92): ?><p class="hint">The chart shows the first 92 days. The CSV has every day.</p><?php endif; ?></div>
</section>

<div class="cols">
  <section class="sheet wide">
    <div class="sec"><b>§1</b><h2>Top products</h2><a class="end small" href="<?= e(url('/reports?' . $range . '&export=top')) ?>">Export CSV</a></div>
    <?php if ($top): ?>
      <div style="overflow-x:auto"><table class="table">
        <thead><tr><th>Part</th><th class="r">Units</th><th class="r">Revenue</th><th class="r">Gross profit</th></tr></thead>
        <tbody>
          <?php foreach ($top as $p): ?>
            <tr>
              <td><a class="row-link" href="<?= e(url('/products/' . $p['product_id'])) ?>"><?= e($p['product_name']) ?></a><span class="sub sku"><?= e($p['sku']) ?></span></td>
              <td class="r mono num"><?= e($p['qty']) ?></td>
              <td class="r mono num"><?= e(money(db_cents($p['revenue']))) ?></td>
              <td class="r mono num"><?= e(money(db_cents($p['profit']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php else: ?><div class="empty">No sales in this period.</div><?php endif; ?>
  </section>

  <div class="form">
    <section class="sheet">
      <div class="sec"><b>§2</b><h2>By cashier</h2></div>
      <?php if ($cashiers): ?>
        <dl class="kv" style="margin:0">
          <?php foreach ($cashiers as $c): ?><dt><?= e($c['cashier']) ?> <span class="tag">· <?= e($c['receipts']) ?></span></dt><dd class="r mono num"><?= e(money(db_cents($c['revenue']))) ?></dd><?php endforeach; ?>
        </dl>
      <?php else: ?><div class="empty">No sales in this period.</div><?php endif; ?>
    </section>
    <section class="sheet">
      <div class="sec"><b>§3</b><h2>By payment</h2></div>
      <?php if ($methods): ?>
        <dl class="kv" style="margin:0">
          <?php foreach ($methods as $method => $m): ?><dt><?= e(SaleRepository::METHODS[$method] ?? $method) ?> <span class="tag">· <?= e($m['receipts']) ?></span></dt><dd class="r mono num"><?= e(money($m['revenue'])) ?></dd><?php endforeach; ?>
        </dl>
      <?php else: ?><div class="empty">No sales in this period.</div><?php endif; ?>
    </section>
  </div>
</div>

<div class="cols">
  <section class="sheet wide">
    <div class="sec"><b>§4</b><h2>Stock valuation by category</h2><span class="tag end">AS OF NOW</span><a class="small" href="<?= e(url('/reports?export=valuation')) ?>">Export CSV</a></div>
    <div style="overflow-x:auto"><table class="table">
      <thead><tr><th>Category</th><th class="r">SKUs</th><th class="r">Units</th><th class="r">At cost</th><th class="r">At retail</th><th class="r">Healthy</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr><td><?= e($c['name']) ?></td><td class="r mono num"><?= e($c['skus']) ?></td><td class="r mono num"><?= e(number_format($c['units'])) ?></td>
            <td class="r mono num"><?= e(money($c['cost_value'])) ?></td><td class="r mono num"><?= e(money($c['retail_value'])) ?></td>
            <td class="r mono num<?= $c['health'] < 60 ? ' status-low' : '' ?>"><?= e($c['health']) ?>%</td></tr>
        <?php endforeach; ?>
        <tr><td><b>Total</b></td><td class="r mono num"><b><?= e($stock['skus']) ?></b></td><td class="r mono num"><b><?= e(number_format($stock['units'])) ?></b></td>
          <td class="r mono num"><b><?= e(money($stock['cost_value'])) ?></b></td><td class="r mono num"><b><?= e(money($stock['retail_value'])) ?></b></td><td></td></tr>
      </tbody>
    </table></div>
  </section>

  <section class="sheet">
    <div class="sec"><b>§5</b><h2>Low stock</h2><a class="end small" href="<?= e(url('/products?status=low&sort=stock')) ?>">All</a></div>
    <?php if ($low): ?>
      <div class="list" style="padding:0 16px">
        <?php foreach ($low as $p): $s = stock_status((int) $p['qty_on_hand'], (int) $p['reorder_level']); ?>
          <div class="list-item"><span class="status status-<?= e($s['key']) ?>"><span class="led"></span></span>
            <a href="<?= e(url('/products/' . $p['id'])) ?>" style="color:var(--fg);text-decoration:none;min-width:0"><?= e($p['name']) ?></a>
            <span class="mono num status-<?= e($s['key']) ?>"><?= e($p['qty_on_hand']) ?> / <?= e($p['reorder_level']) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php else: ?><div class="empty">Nothing is low.</div><?php endif; ?>
  </section>
</div>
