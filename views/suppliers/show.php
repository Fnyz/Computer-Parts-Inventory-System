<?php use App\Auth\Auth; ?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/suppliers')) ?>">Suppliers</a> / <?= e($supplier['name']) ?></div>
    <h1><?= e($supplier['name']) ?></h1>
    <?php if ($supplier['archived_at']): ?><p class="lede"><span class="pill pill-muted">Archived</span> since <?= e(format_date($supplier['archived_at'], 'M j, Y')) ?></p><?php endif; ?>
  </div>
  <?php if (Auth::can('suppliers.manage')): ?>
    <div class="actions">
      <?php if (Auth::can('po.manage') && !$supplier['archived_at']): ?>
        <a class="btn btn-primary" href="<?= e(url('/purchase-orders/new?supplier=' . $supplier['id'])) ?>"><?= icon('clipboard', 16) ?>New purchase order</a>
      <?php endif; ?>
      <a class="btn" href="<?= e(url('/suppliers/' . $supplier['id'] . '/edit')) ?>"><?= icon('edit', 16) ?>Edit</a>
      <form class="inline" method="post" action="<?= e(url('/suppliers/' . $supplier['id'] . '/archive')) ?>"<?= $supplier['archived_at'] ? '' : ' data-confirm="Archive ' . e($supplier['name']) . '? It disappears from new purchase orders but keeps its history."' ?>>
        <?= csrf_field() ?>
        <button type="submit" class="btn<?= $supplier['archived_at'] ? '' : ' btn-danger' ?>"><?= icon('archive', 16) ?><?= $supplier['archived_at'] ? 'Restore' : 'Archive' ?></button>
      </form>
    </div>
  <?php endif; ?>
</div>

<div class="cols">
  <section class="sheet">
    <div class="sec"><b>§1</b><h2>Contact</h2></div>
    <dl class="kv" style="margin:0">
      <dt>Contact person</dt><dd><?= e($supplier['contact_person'] ?? '—') ?></dd>
      <dt>Phone</dt><dd class="mono"><?= e($supplier['phone'] ?? '—') ?></dd>
      <dt>Email</dt><dd><?= $supplier['email'] ? '<a href="mailto:' . e($supplier['email']) . '">' . e($supplier['email']) . '</a>' : '—' ?></dd>
      <dt>Address</dt><dd><?= e($supplier['address'] ?? '—') ?></dd>
      <dt style="border-bottom:0">Notes</dt><dd style="border-bottom:0;white-space:pre-line"><?= e($supplier['notes'] ?? '—') ?></dd>
    </dl>
  </section>

  <section class="sheet wide">
    <div class="sec"><b>§2</b><h2>Products from this supplier</h2><span class="tag end"><?= count($products) ?> active</span></div>
    <?php if ($products): ?>
      <div style="overflow-x:auto">
        <table class="table">
          <thead><tr><th>Part</th><th class="r">Cost</th><th class="r">On hand</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($products as $p): ?>
              <tr>
                <td><a class="row-link" href="<?= e(url('/products/' . $p['id'])) ?>"><?= e($p['name']) ?></a><span class="sub sku"><?= e($p['sku']) ?></span></td>
                <td class="r mono num"><?= e(money(db_cents($p['cost_price']))) ?></td>
                <td class="r mono num"><?= e($p['qty_on_hand']) ?></td>
                <td><?= stock_badge((int) $p['qty_on_hand'], (int) $p['reorder_level']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty">No products list this as their supplier yet. Choose it as the supplier when you add or edit a product.</div>
    <?php endif; ?>
  </section>
</div>

<?php if ($orders): ?>
  <section class="sheet">
    <div class="sec"><b>§3</b><h2>Purchase orders</h2></div>
    <div style="overflow-x:auto">
      <table class="table">
        <thead><tr><th>PO</th><th>Status</th><th>Date</th><th class="r">Total</th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><a class="row-link mono" href="<?= e(url('/purchase-orders/' . $o['id'])) ?>"><?= e($o['po_number']) ?></a></td>
              <td><?= App\Core\View::partial('purchase-orders/status', ['status' => $o['status']]) ?></td>
              <td class="mono small"><?= e(format_date($o['created_at'], 'M j, Y')) ?></td>
              <td class="r mono num"><?= e(money(db_cents($o['total']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>
