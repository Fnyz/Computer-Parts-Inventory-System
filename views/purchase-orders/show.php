<?php
use App\Auth\Auth;
use App\Core\View;

$canManage = Auth::can('po.manage');
$status = $po['status'];
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/purchase-orders')) ?>">Purchase orders</a> / <?= e($po['po_number']) ?></div>
    <h1 class="mono"><?= e($po['po_number']) ?></h1>
    <div class="actions" style="margin-top:8px;gap:14px">
      <?= View::partial('purchase-orders/status', ['status' => $status]) ?>
      <span>Supplier: <a href="<?= e(url('/suppliers/' . $po['supplier_id'])) ?>"><?= e($po['supplier_name']) ?></a></span>
    </div>
  </div>
  <?php if ($canManage): ?>
    <div class="actions">
      <?php if ($status === 'draft'): ?>
        <a class="btn" href="<?= e(url('/purchase-orders/' . $po['id'] . '/edit')) ?>"><?= icon('edit', 16) ?>Edit draft</a>
        <form class="inline" method="post" action="<?= e(url('/purchase-orders/' . $po['id'] . '/order')) ?>"><?= csrf_field() ?>
          <button type="submit" class="btn btn-primary">Mark as ordered</button></form>
      <?php endif; ?>
      <?php if (in_array($status, ['draft', 'ordered'], true)): ?>
        <form class="inline" method="post" action="<?= e(url('/purchase-orders/' . $po['id'] . '/cancel')) ?>" data-confirm="Cancel <?= e($po['po_number']) ?>? This can't be undone.">
          <?= csrf_field() ?><button type="submit" class="btn btn-danger">Cancel order</button></form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="cols">
  <section class="sheet wide">
    <div class="sec"><b>§1</b><h2><?= $status === 'ordered' && $canManage ? 'Receive the delivery' : 'Parts' ?></h2>
      <?php if ($status === 'ordered' && $canManage): ?><span class="end tag">Enter what actually arrived</span><?php endif; ?></div>
    <?php if ($status === 'ordered' && $canManage): ?><form method="post" action="<?= e(url('/purchase-orders/' . $po['id'] . '/receive')) ?>" data-confirm="Add the received parts to stock?"><?= csrf_field() ?><?php endif; ?>
    <div style="overflow-x:auto">
      <table class="table">
        <thead><tr><th>Part</th><th class="r">Ordered</th><th class="r"><?= $status === 'received' ? 'Received' : ($status === 'ordered' && $canManage ? 'Arrived' : 'On hand') ?></th><th class="r">Unit cost</th><th class="r">Line total</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><a class="row-link" href="<?= e(url('/products/' . $item['product_id'])) ?>"><?= e($item['product_name']) ?></a><span class="sub sku"><?= e($item['sku']) ?></span></td>
              <td class="r mono num"><?= e($item['qty_ordered']) ?></td>
              <td class="r mono num">
                <?php if ($status === 'ordered' && $canManage): ?>
                  <input class="input mono r" type="number" min="0" max="<?= e($item['qty_ordered']) ?>" step="1" name="received[<?= e($item['id']) ?>]" value="<?= e($item['qty_ordered']) ?>" style="width:90px" aria-label="Arrived quantity for <?= e($item['sku']) ?>">
                <?php elseif ($status === 'received'): ?>
                  <span class="<?= (int) $item['qty_received'] < (int) $item['qty_ordered'] ? 'status-low' : '' ?>"><?= e($item['qty_received']) ?></span>
                <?php else: ?>
                  <?= e($item['qty_on_hand']) ?>
                <?php endif; ?>
              </td>
              <td class="r mono num"><?= e(money(db_cents($item['unit_cost']))) ?></td>
              <td class="r mono num"><?= e(money(db_cents($item['line_total']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4" class="r tag">ORDER TOTAL</td><td class="r mono num" style="font-weight:700"><?= e(money(db_cents($po['total']))) ?></td></tr></tfoot>
      </table>
    </div>
    <?php if ($status === 'ordered' && $canManage): ?>
      <div style="padding:14px 16px;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;border-top:1px solid var(--line)">
        <label class="check"><input type="checkbox" name="update_costs" value="1" checked> Update each part’s cost price to this order’s unit cost</label>
        <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?>Receive into stock</button>
      </div>
    </form>
    <?php endif; ?>
  </section>

  <section class="sheet">
    <div class="sec"><b>§2</b><h2>Details</h2></div>
    <dl class="kv" style="margin:0">
      <dt>Created</dt><dd class="small"><?= e(format_date($po['created_at'])) ?><br><?= e($po['created_by_name'] ?? '') ?></dd>
      <dt>Ordered</dt><dd class="small"><?= e(format_date($po['ordered_at'])) ?></dd>
      <dt>Expected</dt><dd class="small"><?= e($po['expected_on'] ? format_date($po['expected_on'], 'M j, Y') : '—') ?></dd>
      <dt>Received</dt><dd class="small"><?= e(format_date($po['received_at'])) ?><?= $po['received_by_name'] ? '<br>' . e($po['received_by_name']) : '' ?></dd>
      <?php if ($po['cancelled_at']): ?><dt>Cancelled</dt><dd class="small"><?= e(format_date($po['cancelled_at'])) ?></dd><?php endif; ?>
      <dt style="border-bottom:0">Notes</dt><dd style="border-bottom:0;white-space:pre-line"><?= e($po['notes'] ?? '—') ?></dd>
    </dl>
  </section>
</div>
