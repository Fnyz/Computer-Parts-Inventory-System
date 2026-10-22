<?php
use App\Auth\Auth;
use App\Core\View;
use App\Repositories\ActivityRepository;
use App\Services\StockService;

$lowTotal = $stock['low'] + $stock['out'];
?>
<div class="page-head">
  <div>
    <div class="tag">SYS MONITOR · <?= e(strtoupper(date('D j M Y'))) ?></div>
    <h1>Dashboard</h1>
  </div>
  <div class="actions">
    <?php if (Auth::can('po.manage')): ?><a class="btn" href="<?= e(url('/purchase-orders?status=ordered')) ?>"><?= icon('clipboard', 16) ?>Receive PO</a><?php endif; ?>
    <?php if (Auth::can('pos.use')): ?><a class="btn btn-primary" href="<?= e(url('/pos')) ?>"><?= icon('cart', 16) ?>New sale</a><?php endif; ?>
  </div>
</div>

<div class="kpis">
  <?php if (Auth::can('products.manage')): ?>
    <div class="panel kpi">
      <div class="tag">STOCK VALUE · AT COST</div>
      <div class="kpi-value"><?= e(money($stock['cost_value'])) ?></div>
      <div class="kpi-note"><?= e(money($stock['retail_value'])) ?> at selling price</div>
    </div>
  <?php endif; ?>
  <div class="panel kpi">
    <div class="tag">UNITS ON HAND</div>
    <div class="kpi-value"><?= e(number_format($stock['units'])) ?></div>
    <div class="kpi-note">across <?= e(number_format($stock['skus'])) ?> SKUs</div>
  </div>
  <a class="panel kpi<?= $lowTotal ? ' warn' : '' ?>" href="<?= e(url('/products?status=low&sort=stock')) ?>" style="text-decoration:none;color:inherit">
    <div class="tag" style="display:flex;align-items:center;gap:8px"><span class="led" style="<?= $lowTotal ? 'background:var(--warn-led);box-shadow:0 0 calc(8px * var(--glow)) var(--warn-led)' : 'background:var(--ok-led)' ?>"></span>LOW / OUT OF STOCK</div>
    <div class="kpi-value"><?= e($lowTotal) ?></div>
    <div class="kpi-note"><?= e($stock['out']) ?> out of stock · <?= e($stock['low']) ?> low</div>
  </a>
  <?php if (Auth::can('sales.view')): ?>
    <div class="panel kpi">
      <div class="tag">SALES TODAY</div>
      <div class="kpi-value"><?= e(money($today['revenue'])) ?></div>
      <div class="kpi-note"><?= e($today['receipts']) ?> receipt<?= $today['receipts'] === 1 ? '' : 's' ?><?= $weekChange !== null ? ' · last 7 days <span class="' . ($weekChange >= 0 ? 'up' : 'down') . '">' . ($weekChange >= 0 ? '▲' : '▼') . ' ' . e(number_format(abs($weekChange), 1)) . '%</span> vs the 7 before' : '' ?></div>
    </div>
  <?php endif; ?>
</div>

<div class="cols">
  <?php if (Auth::can('sales.view')): ?>
    <section class="panel wide">
      <div class="panel-head">
        <div><div class="tag">BUS TRAFFIC · LAST <?= count($days) ?> DAYS</div><h2>Sales<?= $showPurchases ? ' vs. purchases' : '' ?></h2></div>
        <div class="actions small muted" style="gap:16px">
          <span style="display:flex;align-items:center;gap:6px"><span style="width:14px;height:3px;background:var(--ok-led)"></span>Sales</span>
          <?php if ($showPurchases): ?><span style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;background:var(--copper)"></span>Purchases received</span><?php endif; ?>
        </div>
      </div>
      <div class="panel-body"><?= View::partial('partials/chart', ['days' => $days, 'showPurchases' => $showPurchases]) ?></div>
    </section>
  <?php endif; ?>

  <section class="panel">
    <div class="panel-head"><div><div class="tag">VOLTAGE RAILS · SKUS ABOVE REORDER LEVEL</div><h2>Stock health by category</h2></div></div>
    <div class="panel-body" style="display:grid;gap:10px">
      <?php foreach (array_slice($categories, 0, 8) as $c): ?>
        <div class="meter-row">
          <span><?= e($c['name']) ?></span>
          <span class="meter<?= $c['health'] < 60 ? ' low' : '' ?>"><span style="width:<?= e($c['health']) ?>%"></span></span>
          <span class="mono num r<?= $c['health'] < 60 ? ' status-low' : '' ?>"><?= e($c['health']) ?>%</span>
        </div>
      <?php endforeach; ?>
      <?php if (!$categories): ?><div class="empty">No products yet.</div><?php else: ?><div class="hint">Amber below 60%. Restock soon.</div><?php endif; ?>
    </div>
  </section>
</div>

<div class="cols">
  <section class="panel">
    <div class="panel-head"><h2>Needs restocking</h2><?php if (Auth::can('po.manage') && $restock): ?><a class="small" href="<?= e(url('/purchase-orders/new')) ?>">Create PO</a><?php endif; ?></div>
    <div class="panel-body">
      <?php if ($restock): ?>
        <div class="list">
          <?php foreach ($restock as $p): $s = stock_status((int) $p['qty_on_hand'], (int) $p['reorder_level']); ?>
            <div class="list-item">
              <span class="status status-<?= e($s['key']) ?>"><span class="led"></span></span>
              <span style="min-width:0"><a class="row-link" href="<?= e(url('/products/' . $p['id'])) ?>" style="color:var(--fg);text-decoration:none"><?= e($p['name']) ?></a><span class="sku" style="display:block;color:var(--muted);font-size:11px"><?= e($p['sku']) ?></span></span>
              <span class="mono num status-<?= e($s['key']) ?>"><?= e($p['qty_on_hand']) ?> / <?= e($p['reorder_level']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="tag" style="margin-top:8px">ON HAND / REORDER LEVEL</div>
      <?php else: ?>
        <div class="empty"><?= icon('check', 28) ?><b>All parts are above their reorder level</b></div>
      <?php endif; ?>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head"><h2><?= $activity ? 'Activity log' : 'Latest stock movements' ?></h2>
      <a class="small" href="<?= e(url($activity ? '/activity' : '/stock')) ?>">View all</a></div>
    <div class="panel-body log">
      <?php foreach ($activity as $a): ?>
        <div><span class="t"><?= e(format_date($a['created_at'], 'M j H:i')) ?></span> <span class="status-info"><?= e(strtoupper($a['action'])) ?></span> <?= e(ActivityRepository::details($a['details'])) ?> <span class="t"><?= e($a['user_name'] ?? 'system') ?></span></div>
      <?php endforeach; ?>
      <?php foreach ($moves as $m): ?>
        <div><span class="t"><?= e(format_date($m['created_at'], 'M j H:i')) ?></span> <span class="<?= (int) $m['qty_change'] >= 0 ? 'up' : 'down' ?>"><?= e(strtoupper(StockService::TYPES[$m['type']] ?? $m['type'])) ?></span> <?= (int) $m['qty_change'] > 0 ? '+' : '' ?><?= e($m['qty_change']) ?> <?= e($m['sku']) ?> <span class="t"><?= e($m['user_name'] ?? '') ?></span></div>
      <?php endforeach; ?>
      <?php if (!$activity && !$moves): ?><div class="empty">Nothing yet.</div><?php endif; ?>
    </div>
  </section>
</div>
