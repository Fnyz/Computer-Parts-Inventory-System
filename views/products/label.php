<?php
use App\Services\BarcodeService;

$barcode = BarcodeService::svg($product['sku'], 40);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Labels · <?= e($product['sku']) ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=IBM+Plex+Sans:wght@500;600&display=swap">
  <style>
    body { margin: 0; background: #E4EAE6; color: #111A16; font-family: "IBM Plex Sans", system-ui, sans-serif; }
    .bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 14px 20px; background: #FFFFFF; border-bottom: 1px solid #C3CEC8; }
    .bar a, .bar button, .bar input { font: 14px inherit; }
    .sheet { display: grid; grid-template-columns: repeat(auto-fill, 62mm); gap: 4mm; padding: 10mm; }
    .label { width: 62mm; height: 32mm; box-sizing: border-box; background: #FFFFFF; border: 1px dashed #C3CEC8; padding: 2.5mm 3mm; display: grid; grid-template-rows: auto 1fr auto; overflow: hidden; }
    .name { font-weight: 600; font-size: 9pt; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .code svg { width: 100%; height: 13mm; }
    .foot { display: flex; justify-content: space-between; font: 500 8pt "JetBrains Mono", monospace; letter-spacing: .04em; }
    .price { font-weight: 700; font-size: 10pt; }
    @media print { body { background: #FFFFFF; } .bar { display: none; } .sheet { padding: 0; } .label { border-color: transparent; break-inside: avoid; } }
  </style>
</head>
<body>
  <form class="bar" method="get">
    <a href="<?= e(url('/products/' . $product['id'])) ?>">← Back to <?= e($product['sku']) ?></a>
    <label>Copies <input type="number" name="copies" min="1" max="60" value="<?= e($copies) ?>" style="width:70px"></label>
    <label>Price <select name="price" onchange="this.form.submit()">
      <option value="1"<?= $showPrice ? ' selected' : '' ?>>Show</option>
      <option value="0"<?= $showPrice ? '' : ' selected' ?>>Hide</option>
    </select></label>
    <button type="submit">Update</button>
    <button type="button" onclick="window.print()">Print labels</button>
    <span style="color:#52625A;font-size:13px">62 × 32 mm labels. Any USB barcode scanner can read them at the point of sale.</span>
  </form>
  <div class="sheet">
    <?php for ($i = 0; $i < $copies; $i++): ?>
      <div class="label">
        <div class="name"><?= e($product['name']) ?></div>
        <div class="code"><?= $barcode ?></div>
        <div class="foot"><span><?= e($product['sku']) ?></span><?php if ($showPrice): ?><span class="price"><?= e(money(db_cents($product['selling_price']))) ?></span><?php endif; ?></div>
      </div>
    <?php endfor; ?>
  </div>
</body>
</html>
