<?php
use App\Repositories\SaleRepository;
?>
<div class="page-head no-print">
  <div>
    <div class="tag">J2 · POS MODE · CASHIER <?= e(strtoupper($authUser['name'] ?? '')) ?></div>
    <h1 class="sr-only">Point of sale</h1>
  </div>
</div>

<div class="pos" id="pos">
  <section class="pos-products" aria-label="Parts">
    <label class="search pos-scan">
      <?= icon('barcode', 22) ?>
      <span class="sr-only">Scan a barcode or search for a part</span>
      <input type="search" id="pos-search" placeholder="Scan barcode or type SKU / name" autocomplete="off" autofocus>
      <kbd class="tag" style="border:1px solid var(--line-2);border-radius:3px;padding:2px 6px">F2</kbd>
    </label>
    <div class="chips" role="group" aria-label="Category" id="pos-categories">
      <button type="button" class="chip on" data-category="">All</button>
      <?php foreach ($categories as $name): ?>
        <button type="button" class="chip" data-category="<?= e($name) ?>"><?= e($name) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="pos-grid" id="pos-grid" aria-live="polite"></div>
    <p class="hint" id="pos-empty" hidden>No parts match. Check the spelling or scan the barcode again.</p>
    <p class="hint">Green outline = already in the cart. Parts with no stock can’t be added. Press Enter to add the first match.</p>
    <noscript><div class="flash flash-error">The point of sale needs JavaScript. Turn it on in your browser.</div></noscript>
  </section>

  <aside class="cart" aria-label="Current sale" id="cart">
    <div id="cart-form">
      <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
        <h2>Current sale</h2>
        <span class="tag" id="cart-count">0 ITEMS</span>
      </div>
      <div id="cart-lines"></div>
      <div class="empty" id="cart-empty"><?= icon('cart', 28) ?><b>The cart is empty</b>Scan or click a part to add it.</div>

      <div class="totals">
        <div><span class="muted">Subtotal</span><span class="mono num" id="t-subtotal">₱0.00</span></div>
        <?php if ($canDiscount): ?>
          <label class="field" style="grid-template-columns:1fr 140px;align-items:center;display:grid"><span>Discount (₱)</span>
            <input class="input mono r" type="text" inputmode="decimal" id="discount" value="0" autocomplete="off"></label>
        <?php endif; ?>
      </div>
      <div class="readout"><span class="tag">TOTAL DUE</span><span class="value" id="t-total">₱0.00</span></div>

      <div class="form-grid" style="margin-top:12px;grid-template-columns:1fr 1fr">
        <label class="field"><span>Payment</span>
          <select class="select" id="method">
            <?php foreach (SaleRepository::METHODS as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="field" id="paid-field"><span>Cash received (₱)</span>
          <input class="input mono r" type="text" inputmode="decimal" id="paid" autocomplete="off"></label>
        <label class="field" style="grid-column:1 / -1"><span>Customer name (optional)</span>
          <input class="input" type="text" id="customer" maxlength="150" autocomplete="off"></label>
      </div>
      <div class="change" id="change-row"><span>Change</span><span class="value" id="t-change">₱0.00</span></div>
      <div class="flash flash-error" id="pos-error" role="alert" hidden><?= icon('x', 16) ?><span></span></div>

      <button type="button" class="btn btn-primary btn-block" id="complete" style="min-height:56px;font-size:17px" disabled>Complete sale · F9</button>
      <button type="button" class="btn btn-block" id="clear" style="margin-top:8px">Clear cart</button>
      <p class="hint" style="margin:6px 0 0">Totals here are a preview. The server checks stock and recalculates the prices when you complete the sale.</p>
    </div>

    <div class="pos-done" id="pos-done" hidden>
      <?= icon('check', 40) ?>
      <div class="tag">SALE COMPLETE</div>
      <div class="mono" style="font-size:20px;font-weight:700" id="done-number"></div>
      <div class="readout" style="width:100%"><span class="tag">CHANGE</span><span class="value" id="done-change"></span></div>
      <div class="actions" style="justify-content:center">
        <a class="btn" id="done-receipt" href="#" target="_blank" rel="noopener"><?= icon('print', 16) ?>Print receipt</a>
        <a class="btn" id="done-sale" href="#">View sale</a>
      </div>
      <button type="button" class="btn btn-primary btn-block" id="new-sale">New sale</button>
    </div>
  </aside>
</div>

<script type="application/json" id="pos-data"><?= json_encode(['products' => $products, 'checkoutUrl' => url('/pos/checkout')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= e(asset('js/pos.js')) ?>"></script>
