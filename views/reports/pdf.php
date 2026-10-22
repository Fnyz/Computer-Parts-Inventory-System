<?php
use App\Repositories\SaleRepository;

$shop = (string) env('SHOP_NAME', 'PartsDesk Computer Parts');
$margin = $totals['revenue'] ? $totals['profit'] / $totals['revenue'] * 100 : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sales report</title>
  <style>
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #111A16; }
    h1 { font-size: 18px; margin: 0 0 2px; } h2 { font-size: 12px; margin: 18px 0 6px; border-bottom: 2px solid #9A5A1C; padding-bottom: 3px; }
    .muted { color: #52625A; } .r { text-align: right; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; background: #EEF2EF; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; padding: 4px 6px; }
    td { padding: 4px 6px; border-bottom: 1px solid #E4EAE6; }
    .kpis td { border: 1px solid #D5DDD8; padding: 8px; width: 25%; }
    .kpis b { display: block; font-size: 14px; margin-top: 2px; }
  </style>
</head>
<body>
  <h1><?= e($shop) ?> · Sales report</h1>
  <div class="muted"><?= e(format_date($from, 'M j, Y')) ?> – <?= e(format_date($to, 'M j, Y')) ?> · generated <?= e(date('M j, Y H:i')) ?> · completed sales only</div>

  <h2>Summary</h2>
  <table class="kpis"><tr>
    <td>Revenue<b><?= e(money($totals['revenue'])) ?></b></td>
    <td>Gross profit<b><?= e(money($totals['profit'])) ?></b><?= e(number_format($margin, 1)) ?>% margin</td>
    <td>Receipts<b><?= e(number_format($totals['receipts'])) ?></b></td>
    <td>Discounts<b><?= e(money($totals['discounts'])) ?></b></td>
  </tr></table>

  <h2>By day</h2>
  <table>
    <tr><th>Date</th><th class="r">Receipts</th><th class="r">Revenue</th><th class="r">Gross profit</th></tr>
    <?php foreach ($daily as $day => $d): ?>
      <tr><td><?= e(format_date($day, 'D M j, Y')) ?></td><td class="r"><?= e($d['receipts']) ?></td><td class="r"><?= e(money($d['revenue'])) ?></td><td class="r"><?= e(money($d['profit'])) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$daily): ?><tr><td colspan="4" class="muted">No sales in this period.</td></tr><?php endif; ?>
  </table>

  <h2>Top products</h2>
  <table>
    <tr><th>SKU</th><th>Product</th><th class="r">Units</th><th class="r">Revenue</th><th class="r">Gross profit</th></tr>
    <?php foreach ($top as $p): ?>
      <tr><td><?= e($p['sku']) ?></td><td><?= e($p['product_name']) ?></td><td class="r"><?= e($p['qty']) ?></td><td class="r"><?= e(money(db_cents($p['revenue']))) ?></td><td class="r"><?= e(money(db_cents($p['profit']))) ?></td></tr>
    <?php endforeach; ?>
  </table>

  <h2>By cashier and payment</h2>
  <table>
    <tr><th>Cashier</th><th class="r">Receipts</th><th class="r">Revenue</th></tr>
    <?php foreach ($cashiers as $c): ?><tr><td><?= e($c['cashier']) ?></td><td class="r"><?= e($c['receipts']) ?></td><td class="r"><?= e(money(db_cents($c['revenue']))) ?></td></tr><?php endforeach; ?>
    <?php foreach ($methods as $method => $m): ?><tr><td class="muted">Paid by <?= e(SaleRepository::METHODS[$method] ?? $method) ?></td><td class="r"><?= e($m['receipts']) ?></td><td class="r"><?= e(money($m['revenue'])) ?></td></tr><?php endforeach; ?>
  </table>
</body>
</html>
