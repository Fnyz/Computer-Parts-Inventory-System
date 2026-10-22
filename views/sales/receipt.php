<?php
use App\Repositories\SaleRepository;

/** Receipt for printing (HTML) or as a PDF ($pdf = true, rendered by Dompdf). */
$pdf = $pdf ?? false;
$shop = (string) env('SHOP_NAME', 'PartsDesk Computer Parts');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php if (!$pdf): ?><meta name="viewport" content="width=device-width, initial-scale=1"><?php endif; ?>
  <title><?= e($sale['sale_number']) ?> · Receipt</title>
  <style>
    body { margin: 0; background: <?= $pdf ? '#FFFFFF' : '#E4EAE6' ?>; color: #111A16; font-family: <?= $pdf ? '"DejaVu Sans Mono", monospace' : '"JetBrains Mono", ui-monospace, Menlo, monospace' ?>; font-size: <?= $pdf ? '9px' : '13px' ?>; }
    .receipt { max-width: 380px; margin: <?= $pdf ? '0' : '24px auto' ?>; background: #FFFFFF; padding: <?= $pdf ? '10px' : '24px' ?>; }
    h1 { font-size: <?= $pdf ? '13px' : '18px' ?>; margin: 0; text-align: center; letter-spacing: .04em; }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 2px 0; vertical-align: top; }
    .rule { border-top: 1px dashed #52625A; margin: 8px 0; }
    .void { border: 2px solid #B42318; color: #B42318; text-align: center; font-weight: bold; padding: 4px; margin: 8px 0; }
    .actions { text-align: center; margin: 16px 0; }
    .actions button { font: inherit; padding: 8px 16px; cursor: pointer; }
    @media print { body { background: #FFFFFF; } .receipt { margin: 0; } .actions { display: none; } }
  </style>
</head>
<body>
<div class="receipt">
  <h1><?= e(strtoupper($shop)) ?></h1>
  <?php if (env('SHOP_ADDRESS')): ?><div class="c"><?= e(env('SHOP_ADDRESS')) ?></div><?php endif; ?>
  <?php if (env('SHOP_PHONE')): ?><div class="c"><?= e(env('SHOP_PHONE')) ?></div><?php endif; ?>
  <div class="rule"></div>
  <table>
    <tr><td>Receipt</td><td class="r b"><?= e($sale['sale_number']) ?></td></tr>
    <tr><td>Date</td><td class="r"><?= e(format_date($sale['created_at'], 'Y-m-d H:i')) ?></td></tr>
    <tr><td>Cashier</td><td class="r"><?= e($sale['cashier_name'] ?? '—') ?></td></tr>
    <?php if ($sale['customer_name']): ?><tr><td>Customer</td><td class="r"><?= e($sale['customer_name']) ?></td></tr><?php endif; ?>
  </table>
  <?php if ($sale['status'] === 'voided'): ?><div class="void">VOIDED · <?= e($sale['void_reason'] ?? '') ?></div><?php endif; ?>
  <div class="rule"></div>
  <table>
    <?php foreach ($items as $i): ?>
      <tr><td colspan="2"><?= e($i['product_name']) ?></td></tr>
      <tr><td>&nbsp;&nbsp;<?= e($i['qty']) ?> × <?= e(money(db_cents($i['unit_price']))) ?></td><td class="r"><?= e(money(db_cents($i['line_total']))) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <div class="rule"></div>
  <table>
    <tr><td>Subtotal</td><td class="r"><?= e(money(db_cents($sale['subtotal']))) ?></td></tr>
    <?php if (db_cents($sale['discount']) > 0): ?><tr><td>Discount</td><td class="r">-<?= e(money(db_cents($sale['discount']))) ?></td></tr><?php endif; ?>
    <tr class="b"><td>TOTAL</td><td class="r"><?= e(money(db_cents($sale['total']))) ?></td></tr>
    <tr><td><?= e(SaleRepository::METHODS[$sale['payment_method']] ?? $sale['payment_method']) ?></td><td class="r"><?= e(money(db_cents($sale['amount_paid']))) ?></td></tr>
    <?php if ($sale['payment_method'] === 'cash'): ?><tr><td>Change</td><td class="r"><?= e(money(db_cents($sale['change_due']))) ?></td></tr><?php endif; ?>
  </table>
  <div class="rule"></div>
  <div class="c">Thank you! Keep this receipt for warranty claims.</div>
</div>
<?php if (!$pdf): ?>
  <div class="actions"><button type="button" onclick="window.print()">Print</button></div>
<?php endif; ?>
</body>
</html>
