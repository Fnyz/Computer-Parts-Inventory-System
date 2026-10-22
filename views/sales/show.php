<?php
use App\Auth\Auth;
use App\Repositories\SaleRepository;

$voided = $sale['status'] === 'voided';
?>
<div class="page-head">
  <div>
    <div class="breadcrumb"><a href="<?= e(url('/sales')) ?>">Sales</a> / <?= e($sale['sale_number']) ?></div>
    <h1 class="mono"><?= e($sale['sale_number']) ?></h1>
    <div class="actions" style="margin-top:8px;gap:14px">
      <?= $voided ? '<span class="pill pill-bad">Voided</span>' : '<span class="pill pill-ok">Completed</span>' ?>
      <span class="small"><?= e(format_date($sale['created_at'])) ?> · <?= e($sale['cashier_name'] ?? '—') ?></span>
    </div>
  </div>
  <div class="actions">
    <a class="btn" href="<?= e(url('/sales/' . $sale['id'] . '/receipt')) ?>" target="_blank" rel="noopener"><?= icon('print', 16) ?>Print receipt</a>
    <a class="btn" href="<?= e(url('/sales/' . $sale['id'] . '/receipt.pdf')) ?>"><?= icon('download', 16) ?>PDF</a>
  </div>
</div>

<?php if ($voided): ?>
  <div class="flash flash-error"><?= icon('x', 16) ?><span>Voided <?= e(format_date($sale['voided_at'])) ?> by <?= e($sale['voided_by_name'] ?? '—') ?>: <?= e($sale['void_reason'] ?? '') ?>. The stock was put back.</span></div>
<?php endif; ?>

<div class="cols">
  <section class="sheet wide">
    <div class="sec"><b>§1</b><h2>Items</h2></div>
    <div style="overflow-x:auto">
      <table class="table">
        <thead><tr><th>Part</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Line total</th></tr></thead>
        <tbody>
          <?php foreach ($items as $i): ?>
            <tr>
              <td><a class="row-link" href="<?= e(url('/products/' . $i['product_id'])) ?>"><?= e($i['product_name']) ?></a><span class="sub sku"><?= e($i['sku']) ?></span></td>
              <td class="r mono num"><?= e($i['qty']) ?></td>
              <td class="r mono num"><?= e(money(db_cents($i['unit_price']))) ?></td>
              <td class="r mono num"><?= e(money(db_cents($i['line_total']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="sheet">
    <div class="sec"><b>§2</b><h2>Payment</h2></div>
    <dl class="kv" style="margin:0">
      <dt>Subtotal</dt><dd class="r mono num"><?= e(money(db_cents($sale['subtotal']))) ?></dd>
      <dt>Discount</dt><dd class="r mono num"><?= e(money(db_cents($sale['discount']))) ?></dd>
      <dt><b>Total</b></dt><dd class="r mono num" style="font-weight:700"><?= e(money(db_cents($sale['total']))) ?></dd>
      <dt>Paid by</dt><dd class="r"><?= e(SaleRepository::METHODS[$sale['payment_method']] ?? $sale['payment_method']) ?></dd>
      <dt>Amount paid</dt><dd class="r mono num"><?= e(money(db_cents($sale['amount_paid']))) ?></dd>
      <dt>Change</dt><dd class="r mono num"><?= e(money(db_cents($sale['change_due']))) ?></dd>
      <dt style="border-bottom:0">Customer</dt><dd class="r" style="border-bottom:0"><?= e($sale['customer_name'] ?? '—') ?></dd>
    </dl>
  </section>
</div>

<?php if (!$voided && Auth::can('sales.void')): ?>
  <form class="panel panel-pad form" method="post" action="<?= e(url('/sales/' . $sale['id'] . '/void')) ?>" data-confirm="Void <?= e($sale['sale_number']) ?>? Its parts go back into stock." style="max-width:560px">
    <?= csrf_field() ?>
    <div class="tag">VOID THIS SALE</div>
    <?= App\Core\View::partial('partials/field', ['name' => 'reason', 'label' => 'Reason', 'value' => '', 'attrs' => 'required maxlength="200" placeholder="Wrong item rung up, customer cancelled…"']) ?>
    <div class="form-actions"><button type="submit" class="btn btn-danger">Void sale and return stock</button></div>
  </form>
<?php endif; ?>
